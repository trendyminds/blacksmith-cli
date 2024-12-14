<?php

namespace App\Data;

use Exception;
use Laravel\Forge\Forge;
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
    protected string $server;

    /**
     * The PHP version to use
     */
    protected string $php_version;

    /**
     * The Git repository to mount
     */
    protected string $git_repo;

    /**
     * The Git branch to mount
     */
    protected string $git_branch;

    /**
     * The subdomain of the sandbox
     */
    protected string $subdomain;

    /**
     * The primary domain
     */
    protected string $domain;

    /**
     * The Forge API client
     */
    private Forge $forge;

    public function __construct()
    {
        $this->token = config('forge.token');
        $this->server = config('forge.server');
        $this->php_version = config('forge.php_version');
        $this->git_repo = config('forge.git_repo');
        $this->git_branch = config('forge.git_branch');
        $this->subdomain = config('forge.subdomain');
        $this->domain = config('forge.domain');
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
            'database' => null,
            'migrate' => false,
        ])->enableQuickDeploy();
    }

    /**
     * Deploys the site
     */
    public function deploy(): void
    {
        $this->getSite()->deploySite(true);
    }

    /**
     * Removes the sandbox from Forge
     */
    public function destroy(): void
    {
        if (! $this->getSite()) {
            throw new Exception('There is no sandbox to destroy');
        }

        $this->getSite()->delete();
    }
}
