<?php

/**
 * Harvest
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Harvest;

use DateTimeInterface;
use DecodeLabs\Harvest\Cookie\Collection as CookieCollection;
use DecodeLabs\Harvest\Cookie\SameSite;

/**
 * @phpstan-require-implements Response
 */
trait ResponseTrait
{
    use MessageTrait;

    public function parseCookies(): CookieCollection
    {
        $cookies = $this->getHeader('Set-Cookie');

        if (empty($cookies)) {
            return new CookieCollection();
        }

        return CookieCollection::from($cookies);
    }

    public function withCookie(
        string $name,
        string $value,
        ?string $domain = null,
        ?string $path = null,
        string|DateTimeInterface|null $expires = null,
        ?int $maxAge = null,
        bool $secure = false,
        bool $httpOnly = false,
        string|SameSite|null $sameSite = null,
        bool $partitioned = false
    ): static {
        $cookie = new Cookie(
            name: $name,
            value: $value,
            domain: $domain,
            path: $path,
            expires: $expires,
            maxAge: $maxAge,
            secure: $secure,
            httpOnly: $httpOnly,
            sameSite: $sameSite,
            partitioned: $partitioned
        );

        $collection = $this->parseCookies()
            ->add($cookie);

        // @phpstan-ignore-next-line
        return $this->withHeader('Set-Cookie', $collection);
    }

    public function withoutCookie(
        string $name
    ): static {
        $collection = $this->parseCookies()
            ->remove($name);

        // @phpstan-ignore-next-line
        return $this->withHeader('Set-Cookie', $collection);
    }

    public function expireCookie(
        string $name,
        ?string $domain = null,
        ?string $path = null,
    ): static {
        $collection = $this->parseCookies()
            ->expire(
                name: $name,
                domain: $domain,
                path: $path,
            );

        // @phpstan-ignore-next-line
        return $this->withHeader('Set-Cookie', $collection);
    }

    public function withCookies(
        CookieCollection $cookies
    ): static {
        $collection = $this->parseCookies()
            ->merge($cookies);

        // @phpstan-ignore-next-line
        return $this->withHeader('Set-Cookie', $collection);
    }
}
