<?php

namespace App\Helpers;

class EnvironmentVariables
{
    /**
     * Replaces an existing environment variable or appends it if it does not exist
     *
     * @param  string  $currentEnv  The string version of the current environment variables
     * @param  string  $newVars  The new environment variables to add or update
     */
    public static function updateOrAppend(string $currentEnv, string $newVars): string
    {
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
