<?php

namespace App\Helpers;

use App\Data\Sandbox;

class EnvironmentVariables
{
    /**
     * Ensure most environments we interact with get database details set
     */
    public static function setDB(string $currentEnv, Sandbox $sandbox): string
    {
        return str($currentEnv)
            ->replaceMatches('/^DB_HOST=.*/m', 'DB_HOST=127.0.0.1')
            ->replaceMatches('/^CRAFT_DB_SERVER=.*/m', 'CRAFT_DB_SERVER=127.0.0.1')
            ->replaceMatches('/^DB_SERVER=.*/m', 'DB_SERVER=127.0.0.1')
            ->replaceMatches('/^DB_PORT=.*/m', 'DB_PORT=3306')
            ->replaceMatches('/^CRAFT_DB_PORT=.*/m', 'CRAFT_DB_PORT=3306')
            ->replaceMatches('/^DB_DATABASE=.*/m', 'DB_DATABASE='.$sandbox->databaseName)
            ->replaceMatches('/^CRAFT_DB_DATABASE=.*/m', 'CRAFT_DB_DATABASE='.$sandbox->databaseName)
            ->replaceMatches('/^DB_NAME=.*/m', 'DB_NAME='.$sandbox->databaseName)
            ->replaceMatches('/^DB_USERNAME=.*/m', 'DB_USERNAME=forge')
            ->replaceMatches('/^CRAFT_DB_USER=.*/m', 'CRAFT_DB_USER=forge')
            ->replaceMatches('/^DB_USER=.*/m', 'DB_USER=forge')
            ->replaceMatches('/^DB_PASSWORD=.*/m', 'DB_PASSWORD='.config('forge.db_password'))
            ->replaceMatches('/^CRAFT_DB_PASSWORD=.*/m', 'CRAFT_DB_PASSWORD='.config('forge.db_password'))
            ->replaceMatches('/^DB_PASS=.*/m', 'DB_PASS='.config('forge.db_password'))
            ->value();
    }

    /**
     * Ensure most environments we interact with get site URL details set
     */
    public static function setUrl(string $currentEnv, Sandbox $sandbox): string
    {
        return str($currentEnv)
            ->replaceMatches('/^APP_URL=.*/m', 'APP_URL='.$sandbox->fullUrl)
            ->replaceMatches('/^BASE_URL=.*/m', 'BASE_URL='.$sandbox->fullUrl)
            ->replaceMatches('/^SITE_URL=.*/m', 'SITE_URL='.$sandbox->fullUrl)
            ->replaceMatches('/^PRIMARY_SITE_URL=.*/m', 'PRIMARY_SITE_URL='.$sandbox->fullUrl)
            ->value();
    }

    /**
     * Ensure most environments we interact with get set to development instead of production
     */
    public static function setDev(string $currentEnv): string
    {
        return str($currentEnv)
            ->replace('APP_ENV=production', 'APP_ENV=dev')
            ->replace('ENVIRONMENT=production', 'ENVIRONMENT=dev')
            ->value();
    }

    /**
     * Replaces an existing environment variable or appends it if it does not exist
     *
     * @param  string  $currentEnv  The string version of the current environment variables
     * @param  ?string  $newVars  The new environment variables to add or update
     */
    public static function updateOrAppend(string $currentEnv, ?string $newVars): string
    {
        // If there are no new variables, return the current environment
        if (is_null($newVars) || empty($newVars)) {
            return $currentEnv;
        }

        // Parse the stringified environment variables into an [key => value] array
        $parsedVars = str($newVars)
            ->explode(';')
            ->filter()
            ->map(fn ($command) => str($command)->trim()->value())
            ->mapWithKeys(fn ($command) => [str($command)->explode('=')->first() => str($command)->explode('=')->last()]);

        // Check if we need to update or append the new variables
        foreach ($parsedVars as $key => $value) {
            // If the key exists in the current environment variables, replace it
            if (str($currentEnv)->contains("$key=")) {
                $currentEnv = str($currentEnv)
                    ->replaceMatches("/^$key=.*/m", "$key=$value")
                    ->value();

                continue;
            }

            // Otherwise, append the new variable
            $currentEnv = str($currentEnv)
                ->append("\n$key=$value")
                ->value();
        }

        return $currentEnv;
    }
}
