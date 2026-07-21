<?php

use Laravel\Forge\Resources\InstallableServices;

return [
    // The Forge API token
    'token' => env('FORGE_TOKEN'),

    // The Forge organization slug. Found in the Forge dashboard URL:
    // forge.laravel.com/orgs/{slug}/...
    'organization' => env('FORGE_ORGANIZATION'),

    // The Forge server to deploy to
    'server' => env('FORGE_SERVER'),

    // The identifier for your app used for your database and URL (Ex: {app_id}-{pr_number}.{domain})
    'app_id' => env('FORGE_APP_ID'),

    // The PR number for the current deployment
    'pr_number' => env('FORGE_PR_NUMBER'),

    // The PHP version to use for the site
    'php_version' => env('FORGE_PHP_VERSION', InstallableServices::PHP_83),

    // The domain to use for the site
    'domain' => env('FORGE_DOMAIN'),

    // The document root for the site
    'web_directory' => env('FORGE_WEB_DIRECTORY', '/public'),

    // If a database should be created for the site
    'enable_db' => env('FORGE_ENABLE_DB', false),

    // The org/repo to deploy
    'repo' => env('FORGE_REPO'),

    // The branch to deploy
    'branch' => env('FORGE_BRANCH'),

    // Additional deploy commands to run after the default deploy script
    'composer_install_on_mount' => env('FORGE_COMPOSER_INSTALL_ON_MOUNT', true),

    // Additional deploy commands to run after the default deploy script
    'deploy_script' => env('FORGE_DEPLOY_SCRIPT', ''),

    // Additional environment variables to set (or replace if they already exist)
    'env_vars' => env('FORGE_ENV_VARS'),

    // The primary `forge` user's database password
    'db_password' => env('FORGE_DB_PASSWORD', ''),

    // When set only these IP addresses are allowed access to the site
    'allowed_ips' => env('FORGE_ALLOWED_IPS'),

    // The ID of a Forge Storage Provider used to back up the database before
    // destroying the site. The backup destination (provider type, region, bucket,
    // and credentials) is configured as a Storage Provider in the Forge dashboard
    // and referenced here by ID. When empty, no backup is taken on destroy.
    'storage_provider_id' => env('FORGE_STORAGE_PROVIDER_ID'),

    // The token for the GitHub API to post details to the PR
    'github_token' => env('FORGE_GITHUB_TOKEN'),

    // Path to the composer.json file (if not in the root of the repo)
    'path_to_composer_file' => env('FORGE_PATH_TO_COMPOSER_FILE'),

    // Post-mount commands to run
    'post_mount_commands' => env('FORGE_POST_MOUNT_COMMANDS'),

    // Install a Let's Encrypt SSL
    'install_ssl' => env('FORGE_INSTALL_SSL', true),
];
