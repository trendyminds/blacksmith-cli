<?php

namespace App\Commands;

use App\Data\Sandbox;
use App\Services\GitHub;
use Exception;
use Laravel\Forge\Exceptions\ValidationException;
use LaravelZero\Framework\Commands\Command;

class DestroyCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'destroy';

    /**
     * The console command description.
     */
    protected $description = 'Removes the sandbox from Forge';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $sandbox = new Sandbox;

        if (! $sandbox->getSite()) {
            throw new Exception('There is no sandbox to destroy');
        }

        try {
            // Create a database backup if the site has a storage provider set
            if (config('forge.enable_db') && config('forge.storage_provider_id')) {
                $this->components->task('Creating database backup', fn () => $sandbox->createDbBackup());
            }

            $this->components->task('Destroying sandbox', fn () => $sandbox->destroy());
            $this->components->task('Posting details to GitHub', fn () => GitHub::postDestroyDetails());
        } catch (ValidationException $e) {
            $this->components->error('Forge rejected the request with the following validation errors:');
            $this->line(json_encode($e->errors(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return self::FAILURE;
        }
    }
}
