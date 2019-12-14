<?php

namespace Sprocketbox\JWT;

use BadMethodCallException;
use Carbon\CarbonInterval;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Cookie\QueueingFactory as CookieJar;
use Illuminate\Support\Carbon;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key;
use Lcobucci\JWT\Token;
use Lcobucci\JWT\ValidationData;
use Ramsey\Uuid\Uuid;
use RuntimeException;
use Sprocketbox\JWT\Concerns\DefaultCompatibility;

/**
 * Class JwtGuard
 *
 * @package Sprocketbox\JWT
 */
class JWTGuard implements Guard
{
    use DefaultCompatibility;

    /**
     * Configuration options for this guard.
     *
     * @var array
     */
    private $config;

    /**
     * The currently authenticated token.
     *
     * @var \Lcobucci\JWT\Token|null
     */
    private $token;

    /**
     * Create a new authentication guard.
     *
     * @param \Illuminate\Contracts\Auth\UserProvider $userProvider
     * @param string                                  $name
     * @param array                                   $config
     */
    public function __construct(UserProvider $userProvider, string $name, array $config = [])
    {
        $this->setProvider($userProvider);
        $this->setConfig($config);
        $this->name = $name;
    }

    /**
     * Get the currently authenticated user.
     *
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function user(): ?Authenticatable
    {
        if ($this->user === null) {
            $token = $this->getTokenFromCookie();

            if ($token === null) {
                $token = $this->getTokenFromRequest();
            }

            if ($token !== null) {
                $this->user = $this->getProvider()->retrieveById($token->getClaim('sub'));

                if ($this->user !== null) {
                    $this->fireAuthenticatedEvent($this->user);
                }
            }
        }

        return $this->user;
    }

    /**
     * Validate a user's credentials.
     *
     * @param array $credentials
     *
     * @return bool
     */
    public function validate(array $credentials = []): bool
    {
        return $this->getProvider()->retrieveByCredentials($credentials) !== null;
    }

    /**
     * Set the current user.
     *
     * @param \Illuminate\Contracts\Auth\Authenticatable $user
     *
     * @return $this|void
     */
    public function setUser(Authenticatable $user)
    {
        $this->user = $user;

        $this->fireAuthenticatedEvent($user);
    }

    /**
     * Get the currently authenticated token.
     *
     * @return \Lcobucci\JWT\Token|null
     */
    public function token(): ?Token
    {
        return $this->token;
    }

    /**
     * Attempt to log a user in.
     *
     * @param array $credentials
     * @param bool  $cookie
     *
     * @return \Lcobucci\JWT\Token|null
     * @throws \Exception
     */
    public function attempt(array $credentials = [], bool $cookie = false): ?Token
    {
        $this->fireAttemptEvent($credentials, false);

        $this->lastAttempted = $user = $this->provider->retrieveByCredentials($credentials);

        if ($this->hasValidCredentials($user, $credentials)) {
            return $this->login($user, $cookie);
        }

        $this->fireFailedEvent($user, $credentials);

        return null;
    }

    /**
     * Log a user into the application.
     *
     * @param \Illuminate\Contracts\Auth\Authenticatable $user
     * @param bool                                       $cookie
     *
     * @return \Lcobucci\JWT\Token
     * @throws \Exception
     */
    public function login(Authenticatable $user, bool $cookie = false): Token
    {
        $time    = Carbon::now();
        $expiry  = CarbonInterval::fromString($this->config['ttl']);
        $builder = (new Builder)
            ->issuedBy(config('app.url'))
            ->permittedFor(config('app.url'))
            ->identifiedBy(Uuid::uuid4())
            ->issuedAt($time->timestamp)
            ->expiresAt($time->copy()->add($expiry)->timestamp)
            ->relatedTo($user->getAuthIdentifier());

        if ($this->shouldSignToken()) {
            $token = $builder->getToken(new $this->config['signer'], new Key($this->config['key']));
        } else {
            $token = $builder->getToken();
        }

        $this->fireLoginEvent($user, false);

        $this->setUser($user);
        $this->setToken($token);

        if ($cookie) {
            $this->createJwtCookie($token, $expiry);
        }

        return $token;
    }

    public function getCookieName(): string
    {
        return 'login_' . $this->name . '_' . sha1(static::class);
    }

    /**
     * Get the cookie creator instance used by the guard.
     *
     * @return \Illuminate\Contracts\Cookie\QueueingFactory
     *
     * @throws \RuntimeException
     */
    public function getCookieJar(): CookieJar
    {
        if (! isset($this->cookie)) {
            throw new RuntimeException('Cookie jar has not been set.');
        }

        return $this->cookie;
    }

    /**
     * Set the cookie creator instance used by the guard.
     *
     * @param \Illuminate\Contracts\Cookie\QueueingFactory $cookie
     *
     * @return \Sprocketbox\JWT\JWTGuard
     */
    public function setCookieJar(CookieJar $cookie): self
    {
        $this->cookie = $cookie;

        return $this;
    }

    /**
     * Get the token from the current request.
     *
     * @return \Lcobucci\JWT\Token|null
     */
    private function getTokenFromRequest(): ?Token
    {
        return $this->parseToken($this->getRequest()->bearerToken());
    }

    /**
     * Get the token from a cookie.
     *
     * @return \Lcobucci\JWT\Token|null
     */
    private function getTokenFromCookie(): ?Token
    {
        return $this->parseToken($this->getRequest()->cookie($this->getCookieName()));
    }

    /**
     * Validate a token retrieved from a request.
     *
     * @param \Lcobucci\JWT\Token $token
     *
     * @return bool
     */
    private function validateToken(Token $token): bool
    {
        $validator = new ValidationData;
        $validator->setAudience(config('app.url'));
        $validator->setIssuer(config('app.url'));

        if (! $token->validate($validator)) {
            return false;
        }

        if ($this->shouldSignToken()) {
            try {
                return $token->verify(new Sha256(), (new Key($this->config['key']))->getContent());
            } catch (BadMethodCallException $exception) {
                report($exception);
            }
        }

        return false;
    }

    /**
     * Set the currently authenticated token.
     *
     * @param \Lcobucci\JWT\Token $token
     *
     * @return $this
     */
    private function setToken(Token $token): self
    {
        $this->token = $token;

        return $this;
    }

    /**
     * Get whether the token should be signed or not.
     *
     * @return bool
     */
    private function shouldSignToken(): bool
    {
        return ! empty($this->config['signer']) && ! empty($this->config['key']);
    }

    /**
     * Set this guards configuration.
     *
     * @param array $config
     *
     * @return $this
     */
    private function setConfig(array $config): self
    {
        $this->config = array_merge([
            'signer' => Sha256::class,
            'key'    => null,
            'ttl'    => 'P1M',
        ], $config);

        return $this;
    }

    /**
     * Parse a JWT string
     *
     * @param string|null $jwt
     *
     * @return \Lcobucci\JWT\Token|null
     */
    private function parseToken(?string $jwt): ?Token
    {
        if (empty($jwt)) {
            return null;
        }

        $token = (new Parser)->parse($jwt);

        if (! $this->validateToken($token)) {
            return null;
        }

        return $token;
    }

    /**
     * Create the HTTP only JWT cookie
     *
     * @param \Lcobucci\JWT\Token    $token
     * @param \Carbon\CarbonInterval $expiry
     */
    private function createJwtCookie(Token $token, CarbonInterval $expiry): void
    {
        $this->getCookieJar()->queue(
            $this->getCookieJar()->make($this->getCookieName(), (string) $token, $expiry->minutes)
        );
    }
}