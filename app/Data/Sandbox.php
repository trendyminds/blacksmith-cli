<?php

namespace App\Data;

use App\Helpers\EnvironmentVariables;
use App\Helpers\Nginx;
use Exception;
use Illuminate\Support\Facades\Validator;
use Laravel\Forge\Forge;
use Laravel\Forge\Resources\Backup;
use Laravel\Forge\Resources\BackupConfiguration;
use Laravel\Forge\Resources\Command;
use Laravel\Forge\Resources\Database;
use Laravel\Forge\Resources\Domain;
use Laravel\Forge\Resources\Site;

class Sandbox
{
    /**
     * The URL for the sandbox
     */
    public string $url;

    /**
     * The full URL (including protocol) for the sandbox
     */
    public string $fullUrl;

    /**
     * The name of the sandbox's database
     */
    public ?string $databaseName;

    /**
     * The dedicated system user the isolated site runs as
     */
    public string $isolatedUser;

    /**
     * The Forge SDK client
     */
    public Forge $forge;

    public function __construct()
    {
        $this->validate(config('forge'));
    }

    /**
     * Creates a new site on the Forge server.
     *
     * The git repository, composer install, and push-to-deploy are all configured
     * as part of site creation. Site creation returns before Forge finishes
     * provisioning, so we poll until the site has finished installing.
     */
    public function createSite(): void
    {
        $data = [
            'type' => 'php',
            'name' => $this->url,
            // Treat the site name as our own custom domain rather than a Forge vanity domain
            'domain_mode' => 'custom',
            // Run the site as its own dedicated system user, isolated from other sites
            'is_isolated' => true,
            'isolated_user' => $this->isolatedUser,
            'php_version' => config('forge.php_version'),
            'web_directory' => config('forge.web_directory'),
            'source_control_provider' => 'github',
            'repository' => config('forge.repo'),
            'branch' => config('forge.branch'),
            'install_composer_dependencies' => (bool) config('forge.composer_install_on_mount'),
            'push_to_deploy' => true,
            // Explicitly opt out of zero-downtime deployments
            'zero_downtime_deployments' => false,
        ];

        // A database can only be attached by ID, so create it first
        if ($this->databaseName) {
            $data['database_id'] = $this->createDatabase()->id;
        }

        $site = $this->forge->createSite(config('forge.organization'), config('forge.server'), $data);

        // Wait for the site (and its repository) to finish provisioning before
        // any subsequent steps interact with it.
        $this->forge->retry(600, function () use ($site) {
            $fresh = $this->forge->organizationSite(config('forge.organization'), $site->id);

            if ($fresh->status === 'failed') {
                throw new Exception('Site provisioning failed on Forge');
            }

            return in_array($fresh->status, ['installed', 'never-deployed', 'deployed'], true)
                ? $fresh
                : null;
        });

        $this->runPostMountCommands();
    }

    /**
     * Creates the sandbox's database on the Forge server
     */
    public function createDatabase(): Database
    {
        return $this->forge->createDatabase(config('forge.organization'), config('forge.server'), [
            'name' => $this->databaseName,
        ]);
    }

    /**
     * Runs any post-mount commands defined in the config against the new site
     */
    private function runPostMountCommands(): void
    {
        if (! config('forge.post_mount_commands')) {
            return;
        }

        $siteId = $this->getSite()->id;

        $commandString = str(config('forge.post_mount_commands'))
            ->explode(';')
            ->filter()
            ->map(fn ($command) => str($command)->trim()->value())
            ->join(' && ');

        $this->forge->createCommand(
            config('forge.organization'),
            config('forge.server'),
            $siteId,
            ['command' => $commandString],
        );

        // createCommand does not return the command, so grab the one we just
        // queued and wait for it to finish before moving on.
        $command = $this->latestCommand($siteId);

        $this->forge->retry(300, function () use ($siteId, $command) {
            $fresh = $this->forge->command(
                config('forge.organization'),
                config('forge.server'),
                $siteId,
                $command->id
            );

            return in_array($fresh->status, ['finished', 'timeout', 'failed'], true) ? $fresh : null;
        });
    }

    /**
     * Returns the most recently created command for a site
     */
    private function latestCommand(int $siteId): Command
    {
        return $this->forge->retry(60, function () use ($siteId) {
            $commands = $this->forge->commands(config('forge.organization'), config('forge.server'), $siteId);

            return collect($commands->items())->sortByDesc('id')->first();
        });
    }

    /**
     * Returns the site on the Forge server
     */
    public function getSite(): ?Site
    {
        $sites = $this->forge->serverSites(config('forge.organization'), config('forge.server'));

        // List endpoints are paginated, so iterate lazily across all pages
        foreach ($sites->lazy() as $site) {
            if ($site->name === $this->url) {
                return $site;
            }
        }

        return null;
    }

    /**
     * Returns the sandbox's database on the Forge server
     */
    public function getDatabase(): ?Database
    {
        // No database is provisioned when the DB feature is disabled, so avoid an
        // unnecessary API call (and the server-level scope it would require).
        if (! $this->databaseName) {
            return null;
        }

        $databases = $this->forge->databases(config('forge.organization'), config('forge.server'));

        foreach ($databases->lazy() as $database) {
            if ($database->name === $this->databaseName) {
                return $database;
            }
        }

        return null;
    }

    /**
     * Installs a Let's Encrypt SSL certificate on the site
     */
    public function installSSL(): void
    {
        $site = $this->getSite();

        // Certificates are managed per-domain, so resolve the site's domain
        $domain = $this->getSiteDomain($site);

        $this->forge->createCertificate(
            config('forge.organization'),
            config('forge.server'),
            $site->id,
            $domain->id,
            ['type' => 'letsencrypt'],
        );
    }

    /**
     * Returns the domain record matching the sandbox URL for a given site
     */
    private function getSiteDomain(Site $site): Domain
    {
        $domains = $this->forge->domains(config('forge.organization'), config('forge.server'), $site->id);

        foreach ($domains->lazy() as $domain) {
            if ($domain->name === $this->url) {
                return $domain;
            }
        }

        throw new Exception("Unable to find the domain \"{$this->url}\" on the site");
    }

    /**
     * Restricts the sandbox to specific IP addresses via Nginx
     */
    public function addIpRestrictions(): void
    {
        $server = $this->forge->server(config('forge.organization'), config('forge.server'));

        $currentNginx = $this->forge->siteNginx(
            config('forge.organization'),
            config('forge.server'),
            $this->getSite()->id
        );

        $newNginxFile = Nginx::setAllowedIps($currentNginx, $server->ipAddress);

        $this->forge->updateSiteNginx(
            config('forge.organization'),
            config('forge.server'),
            $this->getSite()->id,
            $newNginxFile
        );

        // Updating the Nginx file triggers an asynchronous reload on the server with
        // no status we can poll, so give it a moment to take effect before moving on.
        sleep(5);
    }

    /**
     * Replaces the default Forge deployment script with default and user-supplied commands
     */
    public function updateDeployScript(): void
    {
        $defaultCommands = [
            '# Ignore bot-based commits to the repo',
            '[[ $FORGE_DEPLOY_MESSAGE =~ "[BOT]" ]] && echo "Skipping bot-based deploy" && exit 0',
            '',
            '# Start Blacksmith deployment scripts',
            'cd $FORGE_SITE_PATH',
            '',
            '# Remove local changes to package file (the name change that Forge makes)',
            'git checkout -- package-lock.json',
            '',
            'git pull origin $FORGE_SITE_BRANCH',
        ];

        // Setup composer install command and append the working directory flag if necessary
        $composerCmd = '$FORGE_COMPOSER install --no-dev --no-interaction --prefer-dist --optimize-autoloader';

        if (config('forge.path_to_composer_file')) {
            $composerCmd .= ' --working-dir='.config('forge.path_to_composer_file');
        }

        $defaultCommands[] = $composerCmd;

        $userCommands = str(config('forge.deploy_script'))
            ->explode(';')
            ->filter()
            ->map(fn ($command) => str($command)->trim()->value())
            ->whenNotEmpty(fn ($commands) => $commands->prepend('# Via FORGE_DEPLOY_SCRIPT')->prepend('')
            );

        $allCommands = collect($defaultCommands)
            ->when($userCommands->isNotEmpty(), fn ($commands) => $commands->concat($userCommands))
            ->join("\n");

        $this->forge->updateDeploymentScript(
            config('forge.organization'),
            config('forge.server'),
            $this->getSite()->id,
            ['content' => $allCommands],
        );
    }

    /**
     * Ensures common development environment variables are set to avoid putting the sandbox in production modes
     */
    public function updateEnvironmentVars(): void
    {
        $siteId = $this->getSite()->id;

        // If environment variables are supplied use them as the new starting point,
        // otherwise start from the environment file Forge generated for the site.
        $envFile = config('forge.env_vars') ?: $this->forge->siteEnvironment(
            config('forge.organization'),
            config('forge.server'),
            $siteId
        );

        // Ensure APP_ENV and ENVIRONMENT are always set to dev
        // Attempt to set the URL for the sandbox
        $newEnv = EnvironmentVariables::setDev($envFile);
        $newEnv = EnvironmentVariables::setUrl($newEnv, $this);

        if (config('forge.enable_db')) {
            $newEnv = EnvironmentVariables::setDB($newEnv, $this);
        }

        $this->forge->updateSiteEnvironment(
            config('forge.organization'),
            config('forge.server'),
            $siteId,
            $newEnv
        );
    }

    /**
     * Deploys the site
     */
    public function deploy(): void
    {
        $this->forge->createDeployment(
            config('forge.organization'),
            config('forge.server'),
            $this->getSite()->id
        );
    }

    /**
     * Creates a database backup via Forge's backup system
     */
    public function createDbBackup(): void
    {
        // Only run if the sandbox actually has a database to back up
        if (! $database = $this->getDatabase()) {
            return;
        }

        $name = 'blacksmith-'.$this->databaseName;

        $this->forge->createBackupConfiguration(config('forge.organization'), config('forge.server'), [
            'storage_provider_id' => (int) config('forge.storage_provider_id'),
            'name' => $name,
            'directory' => 'blacksmith-backups',
            'frequency' => 'weekly',
            'day' => '0',
            'time' => '01:00',
            'retention' => 7,
            'database_ids' => [$database->id],
        ]);

        // The create call does not return the new configuration, so fetch it back
        // by name to get the ID needed to trigger and later delete it.
        $backupConfiguration = $this->getBackupConfiguration($name);

        // Initiate the backup
        $this->forge->createBackup(
            config('forge.organization'),
            config('forge.server'),
            $backupConfiguration->id
        );

        // Wait for the backup to actually finish before removing its configuration
        // (and, ultimately, destroying the database). Backups are asynchronous and
        // vary wildly in duration by database size, so poll rather than guess.
        $backup = $this->latestBackup($backupConfiguration->id);

        $this->forge->retry(1800, function () use ($backupConfiguration, $backup) {
            $fresh = $this->forge->backup(
                config('forge.organization'),
                config('forge.server'),
                $backupConfiguration->id,
                $backup->id
            );

            return $fresh->finishedAt !== null ? $fresh : null;
        });

        // Delete the backup configuration now that the backup is complete
        $this->forge->deleteBackupConfiguration(
            config('forge.organization'),
            config('forge.server'),
            $backupConfiguration->id
        );
    }

    /**
     * Returns a backup configuration on the server by name.
     *
     * The configuration is created asynchronously, so poll until it appears.
     */
    private function getBackupConfiguration(string $name): BackupConfiguration
    {
        return $this->forge->retry(120, function () use ($name) {
            $configurations = $this->forge->backupConfigurations(config('forge.organization'), config('forge.server'));

            foreach ($configurations->lazy() as $configuration) {
                if ($configuration->name === $name) {
                    return $configuration;
                }
            }

            return null;
        });
    }

    /**
     * Returns the most recently created backup instance for a configuration
     */
    private function latestBackup(int $backupConfigurationId): Backup
    {
        return $this->forge->retry(120, function () use ($backupConfigurationId) {
            $backups = $this->forge->backups(
                config('forge.organization'),
                config('forge.server'),
                $backupConfigurationId
            );

            return collect($backups->items())->sortByDesc('id')->first();
        });
    }

    /**
     * Removes the sandbox from Forge
     */
    public function destroy(): void
    {
        // Delete the database first if it exists
        if ($database = $this->getDatabase()) {
            $this->forge->deleteDatabase(
                config('forge.organization'),
                config('forge.server'),
                $database->id
            );
        }

        $this->forge->deleteSite(
            config('forge.organization'),
            config('forge.server'),
            $this->getSite()->id
        );
    }

    /**
     * Verify the config options are all valid before instantiation
     */
    private function validate(array $config): void
    {
        $validator = Validator::make($config, [
            'token' => 'required|string',
            'organization' => 'required|string',
            'server' => 'required|integer',
            'app_id' => 'required|string',
            'pr_number' => 'required|integer',
            'domain' => 'required|string',
            'php_version' => 'string|in:php73,php74,php80,php81,php82,php83,php84',
            'repo' => 'required|string',
            'branch' => 'required|string',
            'deploy_script' => 'nullable|string',
            'env_vars' => 'nullable|string',
            'db_password' => 'nullable|string',
            'github_token' => 'required|string',
            'storage_provider_id' => 'nullable|integer',
        ]);

        $validator->validate();

        // Initialize variables
        $identifier = config('forge.app_id').'_'.config('forge.pr_number');

        $this->url = config('forge.app_id').'-'.config('forge.pr_number').'.'.config('forge.domain');
        $this->fullUrl = config('forge.install_ssl') ? 'https://'.$this->url : 'http://'.$this->url;
        $this->databaseName = config('forge.enable_db') ? $identifier : null;
        $this->isolatedUser = $identifier;

        $this->forge = new Forge(config('forge.token'));
    }
}
