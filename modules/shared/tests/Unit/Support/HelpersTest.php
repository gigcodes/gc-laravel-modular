<?php

test('module_path returns correct path without sub path', function () {
    $path = module_path('auth');

    expect($path)->toBe(base_path('modules/auth'));
});

test('module_path returns correct path with sub path', function () {
    $path = module_path('auth', 'src/Controllers');

    expect($path)->toBe(base_path('modules/auth/src/Controllers'));
});

test('module_path handles path with leading slash', function () {
    $path = module_path('auth', '/src/Controllers');

    expect($path)->toBe(base_path('modules/auth/src/Controllers'));
});

test('module_asset returns correct asset url', function () {
    $assetUrl = module_asset('auth', 'css/style.css');

    expect($assetUrl)->toBe(asset('modules/auth/css/style.css'));
});

test('module_asset works with different file types', function () {
    $cssUrl = module_asset('shared', 'css/app.css');
    $jsUrl  = module_asset('shared', 'js/app.js');
    $imgUrl = module_asset('shared', 'images/logo.png');

    expect($cssUrl)->toBe(asset('modules/shared/css/app.css'));
    expect($jsUrl)->toBe(asset('modules/shared/js/app.js'));
    expect($imgUrl)->toBe(asset('modules/shared/images/logo.png'));
});
