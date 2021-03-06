<?php

namespace Sprocketbox\JWT;

use Illuminate\Auth\AuthManager;
use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use RuntimeException;
use Sprocketbox\JWT\Commands\KeyGenerateCommand;

class JWTServiceProvider extends BaseServiceProvider
{
    public function register(): void
    {
        // Register the JWT driver with Laravel
        $auth = $this->app->make(AuthManager::class);
        $auth->extend('jwt', function (Application $app, string $name, array $config) use ($auth) {
            $provider = $auth->createUserProvider($config['provider'] ?? null);

            if ($provider === null) {
                throw new RuntimeException('No user provider available');
            }

            $guard = new JWTGuard($provider, $name, $config);
            $guard
                // Set the request instance on the guard
                ->setRequest($app->refresh('request', $guard, 'setRequest'))
                // Set the event dispatcher on the guard
                ->setDispatcher($this->app['events'])
                // Set the cookie jar
                ->setCookieJar($this->app['cookie']);

            return $guard;
        });

        $this->commands([
            KeyGenerateCommand::class,
        ]);
    }
}