<?php

use App\Data\Sandbox;
use App\Helpers\EnvironmentVariables;
use Illuminate\Support\Facades\Config;

// test('handles various empty and formatting states', function () {
//     $env1 = EnvironmentVariables::updateOrAppend("APP_NAME=Test\nAPP_DEBUG=true", '');
//     expect($env1)->toBe("APP_NAME=Test\nAPP_DEBUG=true");

//     $env2 = EnvironmentVariables::updateOrAppend("APP_NAME=Test\nAPP_DEBUG=true", 'APP_TEST=12;APP_ANOTHER=foo ;APP_AGAIN=bar');
//     expect($env2)->toBe("APP_NAME=Test\nAPP_DEBUG=true\nAPP_TEST=12\nAPP_ANOTHER=foo\nAPP_AGAIN=bar");

//     $env3 = EnvironmentVariables::updateOrAppend("APP_NAME=Test\nAPP_DEBUG=true", null);
//     expect($env3)->toBe("APP_NAME=Test\nAPP_DEBUG=true");
// });

// test('variables that do not exist are appended to the end', function () {
//     $env1 = EnvironmentVariables::updateOrAppend("APP_NAME=Test\nAPP_DEBUG=true", 'NEW_VAR=456');
//     expect($env1)->toBe("APP_NAME=Test\nAPP_DEBUG=true\nNEW_VAR=456");

//     $env2 = EnvironmentVariables::updateOrAppend("APP_NAME=Test\nAPP_DEBUG=true", 'MY_APP_DEBUG=false');
//     expect($env2)->toBe("APP_NAME=Test\nAPP_DEBUG=true\nMY_APP_DEBUG=false");
// });

// test('variables that do exist are replaced', function () {
//     $env1 = EnvironmentVariables::updateOrAppend("APP_NAME=Test\nAPP_DEBUG=true", 'APP_NAME=Example');
//     expect($env1)->toBe("APP_NAME=Example\nAPP_DEBUG=true");

//     $env2 = EnvironmentVariables::updateOrAppend("APP_NAME=Test\nAPP_DEBUG=true", 'APP_DEBUG=false');
//     expect($env2)->toBe("APP_NAME=Test\nAPP_DEBUG=false");

//     $env3 = EnvironmentVariables::updateOrAppend("APP_NAME=Test\nAPP_DEBUG=true\nAPP_FAKE=123", 'APP_DEBUG=false');
//     expect($env3)->toBe("APP_NAME=Test\nAPP_DEBUG=false\nAPP_FAKE=123");
// });

// test('partial matches are not replaced', function () {
//     $env1 = EnvironmentVariables::updateOrAppend("APP_NAME=Test\nAPP_DEBUG=true", 'MY_APP_NAME=Example');
//     expect($env1)->toBe("APP_NAME=Test\nAPP_DEBUG=true\nMY_APP_NAME=Example");

//     $env2 = EnvironmentVariables::updateOrAppend("APP_ID=Test\nAPP_DEBUG=true\nALGOLIA_APP_ID=123", 'APP_ID=456');
//     expect($env2)->toBe("APP_ID=456\nAPP_DEBUG=true\nALGOLIA_APP_ID=123");
// });

// test('default environment is modified', function () {
//     $env1 = EnvironmentVariables::setDev("APP_ENV=production\nAPP_NAME=Test\nAPP_DEBUG=true");
//     expect($env1)->toBe("APP_ENV=dev\nAPP_NAME=Test\nAPP_DEBUG=true");

//     $env2 = EnvironmentVariables::setDev("ENVIRONMENT=production\nAPP_NAME=Test\nAPP_DEBUG=true");
//     expect($env2)->toBe("ENVIRONMENT=dev\nAPP_NAME=Test\nAPP_DEBUG=true");

//     $env3 = EnvironmentVariables::setDev("FAKE_ENV=foo\nENVIRONMENT=production\nAPP_NAME=Test\nAPP_DEBUG=true");
//     expect($env3)->toBe("FAKE_ENV=foo\nENVIRONMENT=dev\nAPP_NAME=Test\nAPP_DEBUG=true");
// });

// test('database name is set', function () {
//     // Fake the database name and password
//     $sandboxMock = Mockery::mock(Sandbox::class)->makePartial();
//     $sandboxMock->shouldReceive('getDatabaseName')->andReturn('my_db');
//     Config::set('forge.db_password', 'my_password');

//     $env1 = EnvironmentVariables::setDB("APP_ENV=production\nAPP_NAME=Test\nDB_DATABASE=", $sandboxMock);
//     expect($env1)->toBe("APP_ENV=production\nAPP_NAME=Test\nDB_DATABASE=my_db");

//     $env2 = EnvironmentVariables::setDB("APP_ENV=production\nAPP_NAME=Test\nDB_NAME=", $sandboxMock);
//     expect($env2)->toBe("APP_ENV=production\nAPP_NAME=Test\nDB_NAME=my_db");
// });

// test('database user is set', function () {
//     // Fake the database name and password
//     $sandboxMock = Mockery::mock(Sandbox::class)->makePartial();
//     $sandboxMock->shouldReceive('getDatabaseName')->andReturn('my_db');
//     Config::set('forge.db_password', 'my_password');

//     $env1 = EnvironmentVariables::setDB("APP_ENV=production\nAPP_NAME=Test\nDB_USER=", $sandboxMock);
//     expect($env1)->toBe("APP_ENV=production\nAPP_NAME=Test\nDB_USER=forge");

//     $env2 = EnvironmentVariables::setDB("APP_ENV=production\nAPP_NAME=Test\nDB_USERNAME=", $sandboxMock);
//     expect($env2)->toBe("APP_ENV=production\nAPP_NAME=Test\nDB_USERNAME=forge");
// });

// test('database password is set', function () {
//     // Fake the database name and password
//     $sandboxMock = Mockery::mock(Sandbox::class)->makePartial();
//     $sandboxMock->shouldReceive('getDatabaseName')->andReturn('my_db');
//     Config::set('forge.db_password', 'my_password');

//     $env1 = EnvironmentVariables::setDB("APP_ENV=production\nAPP_NAME=Test\nDB_PASSWORD=", $sandboxMock);
//     expect($env1)->toBe("APP_ENV=production\nAPP_NAME=Test\nDB_PASSWORD=my_password");

//     $env2 = EnvironmentVariables::setDB("APP_ENV=production\nAPP_NAME=Test\nDB_PASS=", $sandboxMock);
//     expect($env2)->toBe("APP_ENV=production\nAPP_NAME=Test\nDB_PASS=my_password");
// });

// test('db host and port are set', function () {
//     // Fake the database name and password
//     $sandboxMock = Mockery::mock(Sandbox::class)->makePartial();
//     $sandboxMock->shouldReceive('getDatabaseName')->andReturn('my_db');
//     Config::set('forge.db_password', 'my_password');

//     $env1 = EnvironmentVariables::setDB("APP_ENV=production\nAPP_NAME=Test\nDB_HOST=", $sandboxMock);
//     expect($env1)->toBe("APP_ENV=production\nAPP_NAME=Test\nDB_HOST=127.0.0.1");

//     $env2 = EnvironmentVariables::setDB("APP_ENV=production\nAPP_NAME=Test\nDB_SERVER=", $sandboxMock);
//     expect($env2)->toBe("APP_ENV=production\nAPP_NAME=Test\nDB_SERVER=127.0.0.1");

//     $env3 = EnvironmentVariables::setDB("APP_ENV=production\nAPP_NAME=Test\nDB_PORT=", $sandboxMock);
//     expect($env3)->toBe("APP_ENV=production\nAPP_NAME=Test\nDB_PORT=3306");
// });
