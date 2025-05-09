<?php

/**
 * @package Harvest
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Harvest;

use DateTimeInterface;
use DecodeLabs\Harvest\Cookie\Collection as CookieCollection;
use DecodeLabs\Harvest\Cookie\SameSite;
use Psr\Http\Message\ResponseInterface as PsrResponse;

interface Response extends PsrResponse
{
    public function parseCookies(): CookieCollection;

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
    ): static;

    public function withoutCookie(
        string $name
    ): static;

    public function expireCookie(
        string $name,
        ?string $domain = null,
        ?string $path = null
    ): static;

    public function withCookies(
        CookieCollection $cookies
    ): static;
}
