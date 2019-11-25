# Laravel JWT
[![Latest Stable Version](https://poser.pugx.org/sprocketbox/laravel-jwt/v/stable.png)](https://packagist.org/packages/sprocketbox/laravel-jwt) [![Latest Unstable Version](https://poser.pugx.org/sprocketbox/laravel-jwt/v/unstable.png)](https://packagist.org/packages/sprocketbox/laravel-jwt) [![License](https://poser.pugx.org/sprocketbox/laravel-jwt/license.png)](https://packagist.org/packages/sprocketbox/laravel-jwt)

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
- [Usage](#usage)
    - [Providing the token](#providing-the-token)
    - [Getting the token](#getting-the-token)
    - [Example](#example)
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
    'key'      => env('JWT_KEY'),
    'signer'   => Lcobucci\JWT\Signer\Hmac\Sha256::class,
    'ttl'      => 'P1M',
],
```

### Quick configuration
If you don't care to dive into all the extra bits you can create a very minimal JWT guard config
by:

 - Changing the driver to `jwt` 
 - Adding `'key' => env('JWT_KEY'),`
 - Create your key by running tinker (`php artisan tinker`) and entering `Str::random(64)`
 - Copy that value and prefix with `JWT_KEY=` and add it to the end of your `.env` file.
 - Make sure to add `JWT_KEY=` without the key to the `.env.example` file.

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

## Usage
This package functions in an almost identical way to Laravels session authentication, with a few exceptions.

### Providing the token
The token is loaded as a bearer token, so you must provide it as a bearer token in the HTTP authorization header.

```php
Authorization: Bearer TOKEN_HERE
```

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

- [ ] Custom token generation
- [ ] Custom token validation
- [ ] Database driven log of `jti`, `aud` and `exp` to blacklist and revoke tokens
- [ ] Provide auth scaffolding for generating JWTs
