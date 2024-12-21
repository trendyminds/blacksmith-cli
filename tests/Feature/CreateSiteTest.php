<?php

use App\Data\Sandbox;
use Illuminate\Support\Facades\Config;

afterEach(function () {
    Mockery::close();
});

it('sends the expected payload for a basic site', function () {
    $forgeMock = Mockery::mock(\Laravel\Forge\Forge::class);
    $forgeMock->shouldReceive('createSite')
        ->once()
        ->with(config('forge.server'), [
            'domain' => 'fake-123.example.com',
            'project_type' => 'php',
            'php_version' => 'php83',
            'directory' => '/public',
            'database' => null,
        ]);

    $sandbox = new Sandbox;
    $sandbox->forge = $forgeMock;
    $sandbox->createSite();
});

it('includes a database if requested', function () {
    Config::set('forge.enable_db', true);

    $forgeMock = Mockery::mock(\Laravel\Forge\Forge::class);
    $forgeMock->shouldReceive('createSite')
        ->withArgs(function ($serverId, $args) {
            expect($args['database'])->toBe('fake_123');

            return true;
        });

    $sandbox = new Sandbox;
    $sandbox->forge = $forgeMock;
    $sandbox->createSite();
});

it('uses a different php version if requested', function () {
    Config::set('forge.php_version', 'php81');

    $forgeMock = Mockery::mock(\Laravel\Forge\Forge::class);
    $forgeMock->shouldReceive('createSite')
        ->withArgs(function ($serverId, $args) {
            expect($args['php_version'])->toBe('php81');

            return true;
        });

    $sandbox = new Sandbox;
    $sandbox->forge = $forgeMock;
    $sandbox->createSite();
});

it('uses a different document root if requested', function ($expected, $actual) {
    if ($expected) {
        Config::set('forge.web_directory', $expected);
    }

    $forgeMock = Mockery::mock(\Laravel\Forge\Forge::class);
    $forgeMock->shouldReceive('createSite')
        ->withArgs(function ($serverId, $args) use ($actual) {
            expect($args['directory'])->toBe($actual);

            return true;
        });

    $sandbox = new Sandbox;
    $sandbox->forge = $forgeMock;
    $sandbox->createSite();
})->with([
    ['/public', '/public'],
    ['/web', '/web'],
    ['', '/public'],
    [null, '/public'],
]);
