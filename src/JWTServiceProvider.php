<?php

namespace Sprocketbox\JWT;

use Illuminate\Auth\AuthManager;
use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class JWTServiceProvider extends BaseServiceProvider
{
    public function register(): void
    {
        // Register the JWT driver with Laravel
        $auth = $this->app->make(AuthManager::class);
        $auth->extend('jwt', function (Application $app, string $name, array $config) use ($auth) {
            $guard = new JWTGuard($auth->createUserProvider($config['provider'] ?? null), $name, $config);
            $guard
                // Set the request instance on the guard
                ->setRequest($app->refresh('request', $guard, 'setRequest'))
                // Set the event dispatcher on the guard
                ->setDispatcher($this->app['events']);

            return $guard;
        });
    }
}