<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Modules\Auth\Http\Middleware\RedirectIfAuthenticated;
use Modules\Auth\Models\User;

uses(RefreshDatabase::class);

test('allows unauthenticated users to continue', function () {
    $middleware = new RedirectIfAuthenticated;
    $request    = Request::create('/login');

    $response = $middleware->handle($request, function ($request) {
        return response('next called');
    });

    expect($response->getContent())->toBe('next called');
});

test('redirects authenticated users to dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $middleware = new RedirectIfAuthenticated;
    $request    = Request::create('/login');

    $response = $middleware->handle($request, function ($request) {
        return response('next called');
    });

    expect($response->getStatusCode())->toBe(302);
    expect($response->headers->get('Location'))->toContain('dashboard');
});

test('works with specific guard', function () {
    $user = User::factory()->create();
    $this->actingAs($user, 'web');

    $middleware = new RedirectIfAuthenticated;
    $request    = Request::create('/login');

    $response = $middleware->handle($request, function ($request) {
        return response('next called');
    }, 'web');

    expect($response->getStatusCode())->toBe(302);
    expect($response->headers->get('Location'))->toContain('dashboard');
});

test('allows access when user is not authenticated on specified guard', function () {
    $middleware = new RedirectIfAuthenticated;
    $request    = Request::create('/login');

    $response = $middleware->handle($request, function ($request) {
        return response('next called');
    }, 'web');

    expect($response->getContent())->toBe('next called');
});

test('works with multiple guards', function () {
    $user = User::factory()->create();
    $this->actingAs($user, 'web');

    $middleware = new RedirectIfAuthenticated;
    $request    = Request::create('/login');

    $response = $middleware->handle($request, function ($request) {
        return response('next called');
    }, 'web');

    expect($response->getStatusCode())->toBe(302);
    expect($response->headers->get('Location'))->toContain('dashboard');
});
