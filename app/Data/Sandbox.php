<?php

namespace App\Data;

use App\Helpers\EnvironmentVariables;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Laravel\Forge\Forge;
use Laravel\Forge\Resources\Database;
use Laravel\Forge\Resources\Site;

class Sandbox
{
    /**
     * The full URL to the sandbox
     */
    public string $url;

    /**
     * The name of the sandbox's database
     */
    public ?string $databaseName;

    /**
     * The Forge SDK client
     */
    public Forge $forge;

    public function __construct()
    {
        $this->validate(config('forge'));
    }

    /**
     * Creates a new site on the Forge server
     */
    public function createSite(): void
    {
        $this->forge->createSite(config('forge.server'), [
            'domain' => $this->url,
            'project_type' => 'php',
            'php_version' => config('forge.php_version'),
            'directory' => config('forge.web_directory'),
            'database' => $this->databaseName,
        ]);
    }

    /**
     * Returns the site on the Forge server
     */
    public function getSite(): ?Site
    {
        $allSites = $this->forge->sites(config('forge.server'));

        return collect($allSites)->firstWhere('name', $this->url);
    }

    /**
     * Returns the sandbox's database on the Forge server
     */
    public function getDatabase(): ?Database
    {
        $allDatabases = $this->forge->databases(config('forge.server'));

        return collect($allDatabases)->firstWhere('name', $this->databaseName);
    }

    /**
     * Mount the Git repository to the site
     */
    public function mountRepository(): void
    {
        $this->getSite()->installGitRepository([
            'provider' => 'github',
            'repository' => config('forge.repo'),
            'branch' => config('forge.branch'),
            'database' => $this->databaseName,
            'composer' => true,
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
        $envFile = $this->forge->siteEnvironmentFile(
            config('forge.server'),
            $this->getSite()->id
        );

        // Ensure APP_ENV and ENVIRONMENT are always set to dev
        // Attempt to set the URL for the sandbox
        // Replace or append user-supplied environment variables
        $newEnv = EnvironmentVariables::setDev($envFile);
        $newEnv = EnvironmentVariables::setUrl($envFile, $this);
        $newEnv = EnvironmentVariables::updateOrAppend($newEnv, config('forge.env_vars'));

        if (config('forge.enable_db')) {
            $newEnv = EnvironmentVariables::setDB($newEnv, $this);
        }

        $this->forge->updateSiteEnvironmentFile(
            config('forge.server'),
            $this->getSite()->id,
            $newEnv
        );
    }

    /**
     * Deploys the site
     */
    public function deploy(): void
    {
        $this->getSite()->deploySite(false);
    }

    /**
     * Creates a database backup via Forge's backup system
     */
    public function createDbBackup(): void
    {
        // Only run if a database is enabled
        if (! $this->getDatabase()) {
            return;
        }

        $backup = $this->forge->createBackupConfiguration(config('forge.server'), [
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

        // Wait before starting the backup. Unfortunately these are all async processes
        sleep(15);

        // Initiate backup
        // The Forge SDK does not have a method for dealing with this
        Http::withHeaders([
            'Authorization' => 'Bearer '.config('forge.token'),
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->withUrlParameters([
            'endpoint' => 'https://forge.laravel.com/api/v1',
            'server' => config('forge.server'),
            'backupId' => $backup->id,
        ])->post('{+endpoint}/servers/{server}/backup-configs/{backupId}');

        // Wait before deleting the backup config. Unfortunately these are all async processes
        sleep(45);

        // Delete the backup configuration after the backup is complete
        $backup->delete();

        // Wait a moment before proceeding to subsequent steps
        sleep(10);
    }

    /**
     * Removes the sandbox from Forge
     */
    public function destroy(): void
    {
        // Delete the database first if it exists
        if ($database = $this->getDatabase()) {
            $database->delete();
        }

        $this->getSite()->delete();
    }

    /**
     * Verify the config options are all valid before instantiation
     */
    private function validate(array $config): void
    {
        $validator = Validator::make($config, [
            'token' => 'required|string',
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
            'backup_provider' => 'nullable|string',
            'backup_region' => 'required_with:backup_provider|nullable|string',
            'backup_bucket' => 'required_with:backup_provider|nullable|string',
            'backup_access_key' => 'required_with:backup_provider|nullable|string',
            'backup_secret_key' => 'required_with:backup_provider|nullable|string',
        ]);

        $validator->validate();

        // Initialize variables
        $this->url = config('forge.app_id').'-'.config('forge.pr_number').'.'.config('forge.domain');
        $this->databaseName = config('forge.enable_db')
            ? config('forge.app_id').'_'.config('forge.pr_number')
            : null;

        $this->forge = new Forge(config('forge.token'));
    }
}
