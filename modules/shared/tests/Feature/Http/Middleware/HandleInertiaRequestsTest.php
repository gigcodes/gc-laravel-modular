<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Modules\Auth\Models\User;
use Modules\Shared\Http\Middleware\HandleInertiaRequests;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->middleware = new HandleInertiaRequests;
});

test('middleware shares user data when authenticated', function () {
    $user = User::factory()->create([
        'name'  => 'Test User',
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
            ->andReturn((object) ['getPrefix' => fn () => 'auth']);
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

test('middleware loads language files when they exist', function () {
    // Clear cache first
    cache()->forget('translations.en');

    // Create temporary language files
    $langPath = base_path('lang/en');
    File::ensureDirectoryExists($langPath);

    $validationContent = "<?php\nreturn ['required' => 'This field is required'];";
    file_put_contents("{$langPath}/validation.php", $validationContent);

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

    // Clean up
    File::deleteDirectory(base_path('lang'));
});

test('middleware loads module language files for active module', function () {
    // Clear cache first
    cache()->forget('translations.en');

    // Create temporary module language files
    $moduleLangPath = base_path('modules/auth/resources/lang/en');
    File::ensureDirectoryExists($moduleLangPath);

    $moduleContent = "<?php\nreturn ['title' => 'Authentication Module'];";
    file_put_contents("{$moduleLangPath}/general.php", $moduleContent);

    app()->setLocale('en');

    Route::middleware(['web', HandleInertiaRequests::class])
        ->name('auth::test')
        ->get('/test-route', function () {
            return inertia('TestPage');
        });

    $response = $this->get('/test-route');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->has('translations')
    );

    // Clean up
    File::deleteDirectory(base_path('modules/auth/resources/lang'));
});

test('middleware handles non-array language file content', function () {
    // Create language files with non-array content
    $langPath = base_path('lang/en');
    File::ensureDirectoryExists($langPath);

    $invalidContent = "<?php\nreturn 'invalid content';";
    file_put_contents("{$langPath}/validation.php", $invalidContent);

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

    // Clean up
    File::deleteDirectory(base_path('lang'));
});
