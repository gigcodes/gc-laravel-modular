<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Modules\Auth\Models\User;
use Modules\Shared\Http\Middleware\HandleInertiaRequests;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->middleware = new HandleInertiaRequests();
});

test('middleware shares user data when authenticated', function () {
    $user = User::factory()->create([
        'name' => 'Test User',
        'email' => 'test@example.com',
    ]);
    
    Route::middleware(['web', 'auth', HandleInertiaRequests::class])
        ->get('/test-route', function () {
            return inertia('TestPage');
        });

    $response = $this->actingAs($user)->get('/test-route');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->has('auth.user')
        ->where('auth.user.name', 'Test User')
        ->where('auth.user.email', 'test@example.com')
    );
});

test('middleware shares null user when guest', function () {
    Route::middleware(['web', HandleInertiaRequests::class])
        ->get('/test-route', function () {
            return inertia('TestPage');
        });

    $response = $this->get('/test-route');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->where('auth.user', null)
    );
});

test('middleware shares translations for default locale', function () {
    app()->setLocale('en');
    
    Route::middleware(['web', HandleInertiaRequests::class])
        ->get('/test-route', function () {
            return inertia('TestPage');
        });

    $response = $this->get('/test-route');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->has('translations')
    );
});

test('middleware handles missing language files gracefully', function () {
    app()->setLocale('nonexistent');
    
    Route::middleware(['web', HandleInertiaRequests::class])
        ->get('/test-route', function () {
            return inertia('TestPage');
        });

    $response = $this->get('/test-route');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->has('translations')
    );
});

test('middleware handles active module translations', function () {
    // Mock the request to have an active module
    $this->mock(\Illuminate\Http\Request::class, function ($mock) {
        $mock->shouldReceive('route')
            ->andReturn((object) ['getPrefix' => fn() => 'auth']);
    });
    
    Route::middleware(['web', HandleInertiaRequests::class])
        ->prefix('auth')
        ->get('/test-route', function () {
            return inertia('TestPage');
        });

    $response = $this->get('/auth/test-route');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->has('translations')
    );
});

test('middleware extracts module from route prefix', function () {
    Route::middleware(['web', HandleInertiaRequests::class])
        ->prefix('auth')
        ->get('/test-route', function () {
            return inertia('TestPage');
        });

    $response = $this->get('/auth/test-route');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->has('translations')
    );
});

test('middleware handles routes without prefixes', function () {
    Route::middleware(['web', HandleInertiaRequests::class])
        ->get('/test-route', function () {
            return inertia('TestPage');
        });

    $response = $this->get('/test-route');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->has('translations')
    );
});