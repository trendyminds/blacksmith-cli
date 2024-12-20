<?php

namespace App\Data;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

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

    public function __construct()
    {
        $this->validate(config('forge'));
    }

    /**
     * Creates a new site on the Forge server
     */
    public function createSite(): void
    {
        Http::forge()->post('servers/{serverId}/sites', [
            'domain' => $this->url,
            'project_type' => 'php',
            'php_version' => config('forge.php_version'),
            'directory' => config('forge.web_directory'),
            'database' => $this->databaseName,
        ]);
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
        ]);

        $validator->validate();

        // Initialize variables
        $this->url = config('forge.app_id').'-'.config('forge.pr_number').'.'.config('forge.domain');
        $this->databaseName = config('forge.enable_db')
            ? config('forge.app_id').'_'.config('forge.pr_number')
            : null;
    }
}
