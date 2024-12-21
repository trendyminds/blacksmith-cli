<?php

use App\Data\Sandbox;
use Illuminate\Support\Facades\Config;
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
