<?php

use App\Helpers\EnvironmentVariables;

test('default environment is modified', function () {
    $env1 = EnvironmentVariables::setDev("APP_ENV=production\nAPP_NAME=Test\nAPP_DEBUG=true");
    expect($env1)->toBe("APP_ENV=dev\nAPP_NAME=Test\nAPP_DEBUG=true");

    $env2 = EnvironmentVariables::setDev("ENVIRONMENT=production\nAPP_NAME=Test\nAPP_DEBUG=true");
    expect($env2)->toBe("ENVIRONMENT=dev\nAPP_NAME=Test\nAPP_DEBUG=true");

    $env3 = EnvironmentVariables::setDev("FAKE_ENV=foo\nENVIRONMENT=production\nAPP_NAME=Test\nAPP_DEBUG=true");
    expect($env3)->toBe("FAKE_ENV=foo\nENVIRONMENT=dev\nAPP_NAME=Test\nAPP_DEBUG=true");
});
