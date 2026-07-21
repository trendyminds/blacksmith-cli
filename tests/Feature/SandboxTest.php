<?php

use App\Data\Sandbox;
use Illuminate\Support\Facades\Config;
use Illuminate\Validation\ValidationException;

it('requires config settings', function () {
    Config::set('forge.github_token', 'test');
    Config::set('forge.organization', 'test-org');
    Config::set('forge.app_id', 'myapp');
    Config::set('forge.pr_number', 123);
    Config::set('forge.domain', 'example.com');
    new Sandbox;
})->throwsNoExceptions();

it('derives the isolated user from the app id and pr number', function () {
    Config::set('forge.github_token', 'test');
    Config::set('forge.organization', 'test-org');
    Config::set('forge.app_id', 'myapp');
    Config::set('forge.pr_number', 1234);
    Config::set('forge.domain', 'example.com');

    expect((new Sandbox)->isolatedUser)->toBe('myapp_1234');
});

it('requires a forge token', function () {
    Config::set('forge.token', null);
    Config::set('forge.app_id', 'myapp');
    Config::set('forge.pr_number', 123);
    Config::set('forge.domain', 'example.com');
    new Sandbox;
})->throws(ValidationException::class);

it('requires an organization', function () {
    Config::set('forge.organization', null);
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

it('requires a repo', function () {
    Config::set('forge.repo', null);
    Config::set('forge.app_id', 'myapp');
    Config::set('forge.pr_number', 123);
    Config::set('forge.domain', 'example.com');
    new Sandbox;
})->throws(ValidationException::class);

it('requires a branch', function () {
    Config::set('forge.repo', 'org/repo');
    Config::set('forge.branch', null);
    Config::set('forge.app_id', 'myapp');
    Config::set('forge.pr_number', 123);
    Config::set('forge.domain', 'example.com');
    new Sandbox;
})->throws(ValidationException::class);
