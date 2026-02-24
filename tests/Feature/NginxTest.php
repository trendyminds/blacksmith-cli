<?php

use App\Helpers\Nginx;
use Illuminate\Support\Facades\Config;

test('ip restrictions are added', function () {
    Config::set('forge.allowed_ips', '1.1.1.1; 2.2.2.2; 3.3.3.3');
    $baseNginx = file_get_contents(base_path('tests/Fixtures/nginx.conf'));
    $updatedNginx = Nginx::setAllowedIps($baseNginx, '9.9.9.9');
    expect($updatedNginx)->toMatchSnapshot();
});

test('ip formatting does not matter', function () {
    Config::set('forge.allowed_ips', '1.1.1.1;2.2.2.2;   3.3.3.3;');
    $baseNginx = file_get_contents(base_path('tests/Fixtures/nginx.conf'));
    $updatedNginx = Nginx::setAllowedIps($baseNginx, '9.9.9.9');
    expect($updatedNginx)->toMatchSnapshot();
});

test('new lines are accepted', function () {
    $ips = '
        1.1.1.1;
        2.2.2.2;
        3.3.3.3;
    ';

    Config::set('forge.allowed_ips', $ips);
    $baseNginx = file_get_contents(base_path('tests/Fixtures/nginx.conf'));
    $updatedNginx = Nginx::setAllowedIps($baseNginx, '9.9.9.9');
    expect($updatedNginx)->toMatchSnapshot();
});
