<?php

namespace App\Commands;

use App\Data\Sandbox;
use LaravelZero\Framework\Commands\Command;

class CreateCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'create';

    /**
     * The console command description.
     */
    protected $description = 'Create a new sandbox on Forge';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $sandbox = new Sandbox;
        $this->components->task('Creating sandbox', fn () => $sandbox->addSite());
        $this->components->task('Mounting the repository', fn () => $sandbox->mountRepository());
        $this->components->task('Updating the deployment script', fn () => $sandbox->updateDeployScript());
        $this->components->task('Deploying site', fn () => $sandbox->deploy());
    }
}
