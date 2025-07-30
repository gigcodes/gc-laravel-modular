<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Shared\Providers\SharedServiceProvider;

uses(RefreshDatabase::class);

test('shared service provider is registered', function () {
    $providers = app()->getLoadedProviders();

    expect($providers)->toHaveKey(SharedServiceProvider::class);
});

test('middleware classes exist', function () {
    expect(class_exists(\Modules\Shared\Http\Middleware\HandleInertiaRequests::class))->toBeTrue();
    expect(class_exists(\Modules\Shared\Http\Middleware\HandleAppearance::class))->toBeTrue();
    expect(class_exists(\Modules\Shared\Http\Middleware\HandleLocalization::class))->toBeTrue();
});

test('module directory is registered', function () {
    $path = module_path('shared');

    expect($path)->toContain('modules/shared');
    expect(is_dir($path))->toBeTrue();
});

test('module assets are published', function () {
    $this->artisan('vendor:publish', [
        '--tag'   => 'shared-assets',
        '--force' => true,
    ])->assertSuccessful();
});

test('helper functions are loaded', function () {
    expect(function_exists('module_path'))->toBeTrue();
    expect(function_exists('module_asset'))->toBeTrue();
});
