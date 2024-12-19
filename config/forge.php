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

    'env_vars' => env('FORGE_ENV_VARS'),
];
