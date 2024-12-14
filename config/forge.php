<?php

use Laravel\Forge\Resources\InstallableServices;

return [
    'token' => env('FORGE_TOKEN'),

    'server' => env('FORGE_SERVER'),

    'php_version' => env('FORGE_PHP_VERSION', InstallableServices::PHP_83),

    'git_repo' => env('FORGE_GIT_REPO'),

    'git_branch' => env('FORGE_GIT_BRANCH'),

    'subdomain' => env('FORGE_SUBDOMAIN'),

    'domain' => env('FORGE_DOMAIN'),
];
