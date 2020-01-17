# Laravel JWT
[![Latest Stable Version](https://poser.pugx.org/sprocketbox/laravel-jwt/v/stable.png)](https://packagist.org/packages/sprocketbox/laravel-jwt) 
[![Latest Unstable Version](https://poser.pugx.org/sprocketbox/laravel-jwt/v/unstable.png)](https://packagist.org/packages/sprocketbox/laravel-jwt) 
[![License](https://poser.pugx.org/sprocketbox/laravel-jwt/license.png)](https://packagist.org/packages/sprocketbox/laravel-jwt)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/sprocketbox/laravel-jwt/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/sprocketbox/laravel-jwt/?branch=master)

- **Laravel**: 6
- **PHP**: 7.2+
- **License**: MIT
- **Author**: Ollie Read 
- **Author Homepage**: https://sprocketbox.io

Laravel JWT provides a seamless JWT (JSON Web Tokens) implementation that integrates directly with Laravels 
authentication library allowing for stateless API authentication.

#### Table of Contents

- [Installing](#installing)
- [Configuring](#configuring)
    - [Quick configuration](#quick-configuration)
    - [Driver](#driver)
    - [Key](#key)
    - [Signer](#signer)
    - [TTL](#ttl)
    - [Customer generation](#custom-generation)
    - [Customer validation](#custom-validation)
- [Usage](#usage)
    - [Providing the token](#providing-the-token)
    - [Getting the token](#getting-the-token)
    - [Example](#example)
    - [Avoiding XSS](#avoiding-xss)
    - [Events](#events)
- [The token](#the-token)
- [The future](#the-future)

## Installing
To install this package simply run the following command.

```
composer require sprocketbox/laravel-jwt
```

This package uses auto-discovery to register the service provider but if you'd rather do it manually, 
the service provider is:

```
Sprocketbox\JWT\JWTServiceProvider
```

## Configuring
There are no extra configuration files required, but there are a few extra options when configuring a guard in
`config/auth.php`.

Here's an example configuration for a JWT guard.

```php
'api' => [
    'driver'   => 'jwt',
    'provider' => 'users',
    'key'      => env('JWT_KEY_API'),
    'signer'   => Lcobucci\JWT\Signer\Hmac\Sha256::class,
    'ttl'      => 'P1M',
],
```

### Quick configuration
If you don't care to dive into all the extra bits you can create a very minimal JWT guard config
by:

 - Changing the driver to `jwt` 
 - Add `'key' => env('JWT_KEY_GUARD'),` where `GUARD` is the name of your auth guard
 - Run `php artisan jwt:generate guard` where `guard` is the name of your auth guard
 - Make sure to duplicate the env variable, but not the value, into your `.env.example` file

### Driver
If you wish to use the JWT driver, just set the `driver` option to `jwt`.

### Key
If you wish for your tokens to be signed you must, at the very least, provide a key using the `key` option.
As the default signature uses a SHA256 HMAC, I recommend a 64 character key.

It's best you place this key in your env file as `JWT_KEY` or something similar.

### Signer
By default this package will create a signature using a SHA256 HMAC, but if you wish to change that you can
set the `signer` option to be the class name of a valid signer.

The default is `Lcobucci\JWT\Signer\Hmac\Sha256` but there are other options in the 
[`Lcobucci\JWT\Signer` namespace](https://github.com/lcobucci/jwt/tree/master/src/Signer). If you wish to keep the 
default you can omit this option.

### TTL
By default this package will set the TTL (total time to live) to 1 month, or more precisely `P1M`. If you wish to change
this you can set the `ttl` config value to be a valid [interval spec](https://www.php.net/manual/en/dateinterval.construct.php#refsect1-dateinterval.construct-parameters).

### Custom generation
If you wish to generate the token yourself you can provide a custom generator like so:

```php
Auth::guard('api')->setTokenGenerator(function (\Illuminate\Contracts\Auth\Authenticatable $user, \Sprocketbox\JWT\JWTGuard $guard) {
    return $instanceOfBuilder;
});
```

The generator must return an instance of `Lcobucci\JWT\Builder`.

### Custom validation
If you wish to provide custom validation for your token you may provide it like so:

```php
Auth::guard('api')->setTokenValidator(function (\Lcobucci\JWT\Token $token, \Sprocketbox\JWT\JWTGuard $guard) {
    return $validationState;
});
```

If the validation fails you must return `false`. Any other return type, including `null` will be treated as a pass.

### Custom token signature generation
In some situations you may find that the static signing method and key in the config isn't sufficient. If that is the
case, you can provide an override like so:

```php
Auth::guard('api')->setTokenSigner(function (\Sprocketbox\JWT\JWTGuard $guard): array {
    return [
        new config('auth.guards.api.signer'), 
        new \Lcobucci\JWT\Signer\Key(config('auth.guards.api.key'))
    ];
});
```

This must return an array with two indexes, the first being the signer and the second being the key.

## Generating keys
You can generate a key per guard by running the `jwt:generate` command with the name of the guard. The 
commands signature is:

```
jwt:generate {guard}
    {--length : The length of the key, defaults to 32}
    {--show : Display the key instead of modifying files}
    {--force : Force the operation to run when in production}
```

## Usage
This package functions in an almost identical way to Laravels session authentication, with a few exceptions.

### Providing the token
The token is loaded as a bearer token, so you must provide it as a bearer token in the HTTP authorization header.

```php
Authorization: Bearer TOKEN_HERE
```

If you passed `true` as the second argument for `attempt()` the token will be automatically provided
by the cookie, removing the need to manually pass the token.

### Getting the token
The `Auth::attempt($credentials)` method is missing the second parameter (remember me) and instead of returning a 
boolean, returns an instance of `Lcobucci\JWT\Token`. Casting this object to a string will give you the
actual JWT token.

If you wish to get the token currently being used, as in, the currently authenticated token, you can call the `token()`
method on the guard, the same way you would call `user()`

### Example
Take the following code as an example:

```php
$input = $request->only('email', 'password');
$token = Auth::guard('api')->attempt($input);

if ($token !== null) {
    return response()->json(['token' => (string) $token]);
}

return response()->json(null, 401);
```

### Avoiding XSS
If you pass `true` as the second argument for `attempt()` the guard will create a HTTP only
(Not accessible via javascript) cookie. This will prevent you from having to store the token in
the browsers localStorage.

To make sure that the cookie is added to the response you need to add the following middleware to 
your routes.

```
Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class
```

It makes sense to add this to the `api` group. Though it's not technically required, I recommend that 
you also add the following middleware to encrypt the cookies.

```
App\Http\Middleware\EncryptCookies::class
```

It's also advised to simply return a `204` response when using this method so that the token data isn't
output anywhere.

### Events
The login and authenticated events are called just like with the session guard.

## The token
By default the token generation is somewhat opinionated, but that is because this is the initial version of this package.

The following covers how the claims are populated.

- Issued by/Issuer (`iss`) is set to `config('app.url')`
- Permitted for/Audience (`aud`) is also set to `config('app.url')`
- Identified by/ID (`jti`) is a UUID4 generated with the token
- Issued at (`iat`) is set to the current timestamp
- Expires at (`exp`) is set to the current timestamp + the value of `ttl` (defaults to `P1M`)
- Related to/Subject (`sub`) is set to the value of `Authenticatable::getAuthIdentifier()`

The token is generated using the [lcobucci/jwt](https://github.com/lcobucci/jwt) package.

## The future
There are a couple of things that I wish to add into later versions of this package.
I've made an attempt to list them all here, as a sort of roadmap.

- [x] HTTP Only cookie support (XSS)
- [x] Custom token generation
- [x] Custom token validation
- [x] Custom token signature
- [ ] Database driven log of `jti`, `aud` and `exp` to blacklist and revoke tokens
- [ ] Provide auth scaffolding for generating JWTs
