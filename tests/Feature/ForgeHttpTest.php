<?php

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

it('contains the token in requests to the forge client', function () {
    Config::set('forge.token', '123');
    Http::fake();
    Http::forge()->get('test');

    // Check that the createSite function called the right endpoint
    Http::assertSent(function (Request $request) {
        return $request->hasHeader('Authorization', 'Bearer 123');
    });
});

it('sets the correct headers on requests', function () {
    Http::fake();
    Http::forge()->get('test');

    // Check that the createSite function called the right endpoint
    Http::assertSent(function (Request $request) {
        return $request->hasHeader('Accept', 'application/json') &&
            $request->hasHeader('Content-Type', 'application/json');
    });
});

it('has the serverId available for requests', function () {
    Config::set('forge.server', 1);
    Http::fake();
    Http::forge()->get('test');

    // Check that the createSite function called the right endpoint
    Http::assertSent(function (Request $request) {
        return $request->url() === 'https://forge.laravel.com/api/v1/test';
    });
});
