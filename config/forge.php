<?php

use Laravel\Forge\Resources\InstallableServices;

return [
    'token' => env('FORGE_TOKEN'),

    'server' => env('FORGE_SERVER'),

    'php_version' => env('FORGE_PHP_VERSION', InstallableServices::PHP_83),

    'subdomain' => env('FORGE_SUBDOMAIN'),

    'domain' => env('FORGE_DOMAIN'),

    'deploy_script' => env('FORGE_DEPLOY_SCRIPT'),

    'web_directory' => env('FORGE_WEB_DIRECTORY', '/public'),

    'enable_db' => env('FORGE_ENABLE_DB', false),

    'db_password' => env('FORGE_DB_PASSWORD'),

    'env_vars' => env('FORGE_ENV_VARS'),

    'backup_provider' => env('FORGE_BACKUP_PROVIDER'),

    'backup_region' => env('FORGE_BACKUP_REGION'),

    'backup_bucket' => env('FORGE_BACKUP_BUCKET'),

    'backup_access_key' => env('FORGE_BACKUP_ACCESS_KEY'),

    'backup_secret_key' => env('FORGE_BACKUP_SECRET_KEY'),
];
