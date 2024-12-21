<?php

use Laravel\Forge\Resources\InstallableServices;

return [
    // The Forge API token
    'token' => env('FORGE_TOKEN'),

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
    'deploy_script' => env('FORGE_DEPLOY_SCRIPT', ''),

    // Additional environment variables to set (or replace if they already exist)
    'env_vars' => env('FORGE_ENV_VARS'),

    // The primary `forge` user's database password
    'db_password' => env('FORGE_DB_PASSWORD'),

    // 'backup_provider' => env('FORGE_BACKUP_PROVIDER'),

    // 'backup_region' => env('FORGE_BACKUP_REGION'),

    // 'backup_bucket' => env('FORGE_BACKUP_BUCKET'),

    // 'backup_access_key' => env('FORGE_BACKUP_ACCESS_KEY'),

    // 'backup_secret_key' => env('FORGE_BACKUP_SECRET_KEY'),
];
