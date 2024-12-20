<?php

namespace App\Data;

use App\Helpers\EnvironmentVariables;
use Exception;
use Illuminate\Support\Facades\Http;
use Laravel\Forge\Forge;
use Laravel\Forge\Resources\Database;
use Laravel\Forge\Resources\Site;

class Sandbox
{
    /**
     * The Forge API token
     */
    protected string $token;

    /**
     * The Forge server ID
     */
    public string $server;

    /**
     * The PHP version to use
     */
    public string $php_version;

    /**
     * The Git repository to mount
     */
    public string $git_repo;

    /**
     * The Git branch to mount
     */
    public string $git_branch;

    /**
     * The subdomain of the sandbox
     */
    protected string $subdomain;

    /**
     * The primary domain
     */
    protected string $domain;

    /**
     * The document root for web requests
     */
    public string $web_directory;

    /**
     * The Forge API client
     */
    private Forge $forge;

    public function __construct()
    {
        $this->token = config('forge.token');
        $this->server = config('forge.server');
        $this->php_version = config('forge.php_version');
        $this->git_repo = config('github.repo');
        $this->git_branch = config('github.branch');
        $this->subdomain = config('forge.subdomain');
        $this->domain = config('forge.domain');
        $this->web_directory = config('forge.web_directory');
        $this->forge = new Forge($this->token);
    }

    /**
     * Returns the full URL of the sandbox using the subdomain and domain
     */
    public function getUrl(): string
    {
        return $this->subdomain.'.'.$this->domain;
    }

    /**
     * Returns the database name for the sandbox if one is required
     */
    public function getDatabaseName(): ?string
    {
        if (! config('forge.enable_db')) {
            return null;
        }

        $repo = str($this->git_repo)->explode('/')->last();

        return str($repo)->append('-'.$this->git_branch)->slug('_')->value();
    }

    /**
     * Returns the sandbox's database from Forge
     */
    public function getDatabase(): ?Database
    {
        $allDatabases = $this->forge->databases($this->server);

        return collect($allDatabases)
            ->filter(fn ($db) => $db->name === $this->getDatabaseName())
            ->first();
    }

    /**
     * Returns the site from Forge
     */
    public function getSite(): ?Site
    {
        $allSites = $this->forge->sites($this->server);

        return collect($allSites)
            ->filter(fn ($site) => $site->name === $this->getUrl())
            ->first();
    }

    /**
     * Adds a new site to the server
     */
    public function addSite(): void
    {
        if ($this->getSite()) {
            throw new Exception('The sandbox already exists');
        }

        $this->forge->createSite($this->server, [
            'domain' => $this->getUrl(),
            'project_type' => 'php',
            'php_version' => $this->php_version,
            'directory' => $this->web_directory,
            'database' => $this->getDatabaseName(),
        ]);
    }

    /**
     * Mounts the Git repository to the site
     */
    public function mountRepository(): void
    {
        $this->getSite()->installGitRepository([
            'provider' => 'github',
            'repository' => $this->git_repo,
            'branch' => $this->git_branch,
            'composer' => true,
            'database' => $this->getDatabaseName(),
            'migrate' => false,
        ])->enableQuickDeploy();
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
            '# Default Blacksmith commands',
            'cd $FORGE_SITE_PATH',
            'git pull origin $FORGE_SITE_BRANCH',
            '$FORGE_COMPOSER install --no-dev --no-interaction --prefer-dist --optimize-autoloader',
        ];

        $userCommands = str(config('forge.deploy_script'))
            ->explode(';')
            ->filter()
            ->map(fn ($command) => str($command)->trim()->value())
            ->whenNotEmpty(fn ($commands) => $commands->prepend('# Via FORGE_DEPLOY_SCRIPT')->prepend('')
            );

        $allCommands = collect($defaultCommands)
            ->when($userCommands->isNotEmpty(), fn ($commands) => $commands->concat($userCommands))
            ->join("\n");

        $this->getSite()->updateDeploymentScript($allCommands);
    }

    /**
     * Ensures common development environment variables are set to avoid putting the sandbox in production modes
     */
    public function updateEnvironmentVars(): void
    {
        $envFile = $this->forge->siteEnvironmentFile($this->server, $this->getSite()->id);

        // Ensure APP_ENV and ENVIRONMENT are always set to dev
        // Replace or append user-supplied environment variables
        $newEnv = EnvironmentVariables::setDev($envFile);
        $newEnv = EnvironmentVariables::updateOrAppend($newEnv, config('forge.env_vars'));

        if (config('forge.enable_db')) {
            $newEnv = EnvironmentVariables::setDB($newEnv, $this);
        }

        $this->forge->updateSiteEnvironmentFile($this->server, $this->getSite()->id, $newEnv);
    }

    /**
     * Deploys the site
     */
    public function deploy(): void
    {
        $this->getSite()->deploySite(false);
    }

    public function createDbBackup(): void
    {
        // Only run if a database is enabled
        if (! $this->getDatabase()) {
            return;
        }

        $backup = $this->forge->createBackupConfiguration($this->server, [
            'provider' => config('forge.backup_provider'),
            'credentials' => [
                'region' => config('forge.backup_region'),
                'bucket' => config('forge.backup_bucket'),
                'access_key' => config('forge.backup_access_key'),
                'secret_key' => config('forge.backup_secret_key'),
            ],
            'frequency' => [
                'type' => 'weekly',
                'time' => '01:00',
                'day' => 0,
            ],
            'directory' => 'blacksmith-backups',
            'retention' => 7,
            'databases' => [
                $this->getDatabase()->id,
            ],
        ]);

        // Wait 30 seconds before starting the backup
        // Unfortunately these are all async processes
        sleep(30);

        // Initiate backup
        // The Forge SDK does not have a method for dealing with this
        Http::withHeaders([
            'Authorization' => 'Bearer '.config('forge.token'),
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->post("https://forge.laravel.com/api/v1/servers/{$this->server}/backup-configs/{$backup->id}");

        // Wait 60 seconds before starting the backup
        // Unfortunately these are all async processes
        sleep(60);

        // Delete the backup configuration after the backup is complete
        $backup->delete();
    }

    /**
     * Removes the sandbox from Forge
     */
    public function destroy(): void
    {
        if (! $this->getSite()) {
            throw new Exception('There is no sandbox to destroy');
        }

        if ($this->getDatabase()) {
            $this->getDatabase()->delete();
        }

        $this->getSite()->delete();
    }
}
