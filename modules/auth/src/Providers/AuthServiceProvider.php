<?php

declare(strict_types=1);

namespace Modules\Auth\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Auth\Interfaces\AuthServiceInterface;
use Modules\Auth\Interfaces\UserRepositoryInterface;
use Modules\Auth\Interfaces\UserServiceInterface;
use Modules\Auth\Repositories\UserRepository;
use Modules\Auth\Services\AuthService;
use Modules\Auth\Services\Google2FAService;
use Modules\Auth\Services\PasskeyService;
use Modules\Auth\Services\UserService;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->bindRepositories();
        $this->bindServices();
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void {}

    /**
     * Bind repository interfaces to implementations.
     */
    private function bindRepositories(): void
    {
        $this->app->bind(
            UserRepositoryInterface::class,
            UserRepository::class
        );
    }

    /**
     * Bind service interfaces to implementations.
     */
    private function bindServices(): void
    {
        $this->app->bind(
            AuthServiceInterface::class,
            AuthService::class
        );

        $this->app->bind(
            UserServiceInterface::class,
            UserService::class
        );
        $this->app->singleton(PasskeyService::class);
        $this->app->singleton(Google2FAService::class);
    }
}
