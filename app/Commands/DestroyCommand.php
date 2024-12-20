<?php

namespace App\Commands;

use App\Data\Sandbox;
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

        // Create a database backup if the site has a backup provider set
        if (config('forge.backup_provider')) {
            $this->components->task('Creating database backup', fn () => $sandbox->createDbBackup());
        }

        $this->components->task('Destroying sandbox', fn () => $sandbox->destroy());
    }
}
