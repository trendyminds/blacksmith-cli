<?php

use App\Data\Sandbox;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

it('requires config settings', function () {
    Config::set('forge.app_id', 'myapp');
    Config::set('forge.pr_number', 123);
    Config::set('forge.domain', 'example.com');
    new Sandbox;
})->throwsNoExceptions();

it('requires a forge token', function () {
    Config::set('forge.token', null);
    Config::set('forge.app_id', 'myapp');
    Config::set('forge.pr_number', 123);
    Config::set('forge.domain', 'example.com');
    new Sandbox;
})->throws(ValidationException::class);

it('requires a server id', function () {
    Config::set('forge.server', null);
    Config::set('forge.app_id', 'myapp');
    Config::set('forge.pr_number', 123);
    Config::set('forge.domain', 'example.com');
    new Sandbox;
})->throws(ValidationException::class);

it('creates a new sandbox with minimum info', function ($domain, $app_id, $pr_number) {
    Config::set('forge.domain', $domain);
    Config::set('forge.app_id', $app_id);
    Config::set('forge.pr_number', $pr_number);

    Http::fake();
    (new Sandbox)->createSite();

    Http::assertSent(function (Request $request) use ($domain, $app_id, $pr_number) {
        return $request->url() === 'https://forge.laravel.com/api/v1/servers/1/sites' &&
            $request->method() === 'POST' &&
            $request->data() === [
                'domain' => "$app_id-$pr_number.$domain",
                'project_type' => 'php',
                'php_version' => 'php83',
                'directory' => '/public',
            ];
    });
})->with([
    ['domain' => 'trendyminds.io', 'app_id' => 'fake', 'pr_number' => 1234],
    ['domain' => 'example.io', 'app_id' => 'foo', 'pr_number' => 555],
    ['domain' => 'another.one.com', 'app_id' => 'bar', 'pr_number' => 900],
]);

it('PHP version defaults to php83', function () {
    Config::set('forge.domain', 'trendyminds.io');
    Config::set('forge.app_id', 'fake');
    Config::set('forge.pr_number', 1234);

    Http::fake();
    (new Sandbox)->createSite();

    Http::assertSent(function (Request $request) {
        return $request['php_version'] === 'php83';
    });
});

it('PHP version can be changed', function () {
    Config::set('forge.domain', 'trendyminds.io');
    Config::set('forge.app_id', 'fake');
    Config::set('forge.pr_number', 1234);
    Config::set('forge.php_version', 'php82');

    Http::fake();
    (new Sandbox)->createSite();

    Http::assertSent(function (Request $request) {
        return $request['php_version'] === 'php82';
    });
});

it('Does not accept invalid PHP strings', function () {
    Config::set('forge.app_id', 'myapp');
    Config::set('forge.pr_number', 123);
    Config::set('forge.domain', 'example.com');
    Config::set('forge.php_version', 'fake');
    new Sandbox;
})->throws(ValidationException::class)->with([
    'fake',
    'php56',
    'php71',
]);

it('Accepts specific PHP versions', function (string $version) {
    Config::set('forge.app_id', 'myapp');
    Config::set('forge.pr_number', 123);
    Config::set('forge.domain', 'example.com');
    Config::set('forge.php_version', $version);
    new Sandbox;
})->throwsNoExceptions()->with([
    'php73',
    'php74',
    'php80',
    'php81',
    'php82',
    'php83',
    'php84',
]);

it('Allows overriding the web directory', function ($expected, $actual) {
    Config::set('forge.domain', 'trendyminds.io');
    Config::set('forge.app_id', 'fake');
    Config::set('forge.pr_number', 1234);

    if ($expected) {
        Config::set('forge.web_directory', $expected);
    }

    Http::fake();
    (new Sandbox)->createSite();

    Http::assertSent(function (Request $request) use ($actual) {
        return $request['directory'] === $actual;
    });
})->with([
    ['/fake/directory/foo', '/fake/directory/foo'],
    ['/docroot', '/docroot'],
    [null, '/public'],
    ['', '/public'],
]);

it('creates a database when the db config is enabled', function ($enabled) {
    Config::set('forge.domain', 'trendyminds.io');
    Config::set('forge.app_id', 'fake');
    Config::set('forge.pr_number', 1234);

    if ($enabled) {
        Config::set('forge.enable_db', true);
    }

    Http::fake();
    (new Sandbox)->createSite();

    Http::assertSent(function (Request $request) use ($enabled) {
        if ($enabled) {
            return $request['database'] === 'fake_1234';
        }

        return ! array_key_exists('database', $request->data());
    });
})->with([true, false]);
