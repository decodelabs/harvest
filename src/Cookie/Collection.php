<?php

/**
 * @package Harvest
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Harvest\Cookie;

use ArrayIterator;
use Countable;
use DateTimeInterface;
use DecodeLabs\Collections\ArrayProvider;
use DecodeLabs\Glitch\Dumpable;
use DecodeLabs\Harvest\Cookie;
use DecodeLabs\Harvest\Cookie\SameSite;
use IteratorAggregate;

/**
 * @implements ArrayProvider<string,Cookie>
 * @implements IteratorAggregate<string,Cookie>
 */
class Collection implements
    ArrayProvider,
    IteratorAggregate,
    Countable,
    Dumpable
{
    use ParameterTrait;

    /**
     * @var array<string,Cookie>
     */
    private array $cookies = [];

    /**
     * @var array<string,bool>
     */
    private array $remove = [];

    /**
     * @param array<string|Cookie> $cookies
     */
    public static function from(
        string|array $cookies
    ): self {
        $instance = new self();

        if(is_string($cookies)) {
            $cookies = [$cookies];
        }

        foreach ($cookies as $cookie) {
            if ($cookie instanceof Cookie) {
                $instance->add($cookie);
                continue;
            }

            $instance->parse($cookie);
        }

        return $instance;
    }



    /**
     * @return $this
     */
    public function add(
        Cookie $cookie
    ): static {
        if(
            $this->domain !== null &&
            $cookie->domain === null
        ) {
            $cookie->domain = $this->domain;
        }

        if(
            $this->path !== null &&
            $cookie->path === null
        ) {
            $cookie->path = $this->path;
        }

        if(
            $this->expires !== null &&
            $cookie->expires === null
        ) {
            $cookie->expires = $this->expires;
        }

        if(
            $this->maxAge !== null &&
            $cookie->maxAge === null
        ) {
            $cookie->maxAge = $this->maxAge;
        }

        if(
            $this->secure &&
            !$cookie->secure
        ) {
            $cookie->secure = $this->secure;
        }

        if(
            $this->httpOnly &&
            !$cookie->httpOnly
        ) {
            $cookie->httpOnly = $this->httpOnly;
        }

        if(
            $this->sameSite !== null &&
            $cookie->sameSite === null
        ) {
            $cookie->sameSite = $this->sameSite;
        }

        if(
            $this->partitioned &&
            !$cookie->partitioned
        ) {
            $cookie->partitioned = $this->partitioned;
        }


        unset($this->remove[$cookie->name]);
        $this->cookies[$cookie->name] = $cookie;
        return $this;
    }


    /**
     * @return $this
     */
    public function set(
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
        return $this->add(
            new Cookie(
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
            )
        );
    }

    /**
     * @return $this
     */
    public function expire(
        string $name,
        ?string $domain = null,
        ?string $path = null
    ): static {
        return $this->set(
            name: $name,
            value: '',
            domain: $domain,
            path: $path,
            maxAge: 0
        );
    }

    /**
     * @return $this
     */
    public function parse(
        string $cookie
    ): static {
        $this->add(
            Cookie::parse($cookie)
        );

        return $this;
    }

    public function get(
        string $name
    ): ?Cookie {
        return $this->cookies[$name] ?? null;
    }

    public function has(
        string $name
    ): bool {
        return isset($this->cookies[$name]);
    }

    public function remove(
        string $name
    ): static {
        unset($this->cookies[$name]);
        $this->remove[$name] = true;
        return $this;
    }

    public function merge(
        Collection $collection
    ): static {
        foreach ($collection->cookies as $cookie) {
            $this->add($cookie);
        }

        foreach ($collection->remove as $name => $value) {
            $this->remove($name);
        }

        return $this;
    }

    public function clear(): static
    {
        $this->cookies = [];
        $this->remove = [];
        return $this;
    }

    public function isEmpty(): bool
    {
        return
            empty($this->cookies) &&
            empty($this->remove);
    }

    public function count(): int
    {
        return count($this->cookies);
    }

    /**
     * @return ArrayIterator<string,Cookie>
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->cookies);
    }

    public function toArray(): array
    {
        return $this->cookies;
    }

    /**
     * @return array<string>
     */
    public function toStringArray(): array
    {
        $output = [];

        foreach ($this->cookies as $cookie) {
            $output[] = (string)$cookie;
        }

        return $output;
    }


    public function glitchDump(): iterable
    {
        yield 'values' => $this->cookies;
    }
}
