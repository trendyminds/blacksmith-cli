<?php

use App\Data\Sandbox;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

it('uses the correct api format', function () {
    Config::set('forge.app_id', 'app');
    Config::set('forge.pr_number', 123);
    Config::set('forge.domain', 'example.com');
    Config::set('forge.repo', 'org/repo');
    Config::set('forge.branch', 'foo');

    Http::fake([
        'servers/1/sites' => Http::response(['sites' => [['name' => 'app-123.example.com', 'id' => 1]]]),
        'servers/1/sites/1/git' => Http::response(),
        'servers/1/sites/1/deployment' => Http::response(),
    ]);
    $sandbox = new Sandbox;
    $sandbox->mountRepository();

    Http::assertSent(function (Request $request) {
        return $request->url() === 'https://forge.laravel.com/api/v1/servers/1/sites/1/git' &&
            $request->method() === 'POST' &&
            $request->data() === [
                'provider' => 'github',
                'repository' => 'org/repo',
                'branch' => 'foo',
                'composer' => true,
            ];
    });
});

it('enables quick deployments', function () {
    Config::set('forge.app_id', 'app');
    Config::set('forge.pr_number', 123);
    Config::set('forge.domain', 'example.com');
    Config::set('forge.repo', 'org/repo');
    Config::set('forge.branch', 'foo');

    Http::fake([
        'servers/1/sites' => Http::response(['sites' => [['name' => 'app-123.example.com', 'id' => 1]]]),
        'servers/1/sites/1/git' => Http::response(),
        'servers/1/sites/1/deployment' => Http::response(),
    ]);
    $sandbox = new Sandbox;
    $sandbox->mountRepository();

    $recorded = Http::recorded();
    [$request, $response] = $recorded[3];
    expect($request->url())->toBe('https://forge.laravel.com/api/v1/servers/1/sites/1/deployment');
});
