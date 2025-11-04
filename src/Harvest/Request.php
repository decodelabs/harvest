<?php

/**
 * Harvest
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Harvest;

use DecodeLabs\Compass\Ip;
use DecodeLabs\Deliverance\Channel\Stream as Channel;
use DecodeLabs\Exceptional;
use DecodeLabs\Harvest;
use DecodeLabs\Harvest\Client\Request as ClientRequest;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\UriInterface;
use Stringable;

class Request extends ClientRequest implements ServerRequestInterface
{
    /**
     * @var array<string,string|array<string, mixed>>|null
     */
    public protected(set) ?array $query = null;

    /**
     * @var array<string,mixed>
     */
    public protected(set) array $attributes = [];

    /**
     * @var array<string,string>
     */
    public protected(set) array $cookies = [];

    /**
     * @var array<string,UploadedFileInterface|array<string,UploadedFileInterface>>
     */
    public protected(set) array $files = [];

    /**
     * @var array<string,mixed>
     */
    public protected(set) array $server = [];

    /**
     * @var array<mixed>|object|null
     */
    public protected(set) mixed $parsedBody = null;


    /**
     * @param array<string, string|Stringable|array<string|Stringable>> $headers
     * @param array<string, string|array<string, mixed>>|null $query
     * @param array<string, string> $cookies
     * @param array<string, UploadedFileInterface|array<string, UploadedFileInterface>> $files
     * @param array<string, mixed> $server
     * @param array<mixed>|object|null $parsedBody
     */
    public function __construct(
        string $method,
        string|UriInterface $uri,
        string|Channel|StreamInterface|null $body = null,
        array $headers = [],
        ?array $query = null,
        array $cookies = [],
        array $files = [],
        array $server = [],
        mixed $parsedBody = null,
        string $protocol = '1.1',
    ) {
        parent::__construct(
            method: $method,
            uri: $uri,
            body: $body,
            headers: $headers,
            protocol: $protocol
        );

        $this->query = $query;
        $this->cookies = $cookies;
        $this->files = $this->normalizeUploadedFiles($files);
        $this->server = $server;
        $this->parsedBody = $parsedBody;
    }






    /**
     * @return array<string, string|array<string, mixed>>
     */
    public function getQueryParams(): array
    {
        /** @var array<string, string|array<string, mixed>> $output */
        $output = $this->query ?? $this->uri->parseQuery()->toArray();
        return $output;
    }

    /**
     * @param array<string, string|array<string, mixed>> $query
     */
    public function withQueryParams(
        array $query
    ): static {
        $output = clone $this;
        $output->query = $query;

        return $output;
    }





    /**
     * @param array<string, string> $cookies
     */
    public function withCookieParams(
        array $cookies
    ): static {
        $output = clone $this;
        $output->cookies = $cookies;

        return $output;
    }

    /**
     * @return array<string, string>
     */
    public function getCookieParams(): array
    {
        return $this->cookies;
    }

    public function getCookieParam(
        string $key
    ): ?string {
        if (!isset($this->cookies[$key])) {
            return null;
        }

        return (string)$this->cookies[$key];
    }

    public function hasCookieParam(
        string $key
    ): bool {
        return isset($this->cookies[$key]);
    }



    /**
     * @param array<string, UploadedFileInterface|array<string, UploadedFileInterface>> $files
     */
    public function withUploadedFiles(
        array $files
    ): static {
        $output = clone $this;
        $output->files = $this->normalizeUploadedFiles($files);

        return $output;
    }

    /**
     * @return array<string, UploadedFileInterface|array<string, UploadedFileInterface>>
     */
    public function getUploadedFiles(): array
    {
        return $this->files;
    }

    /**
     * @param array<string,UploadedFileInterface|array<string,UploadedFileInterface>> $files
     * @return array<string,UploadedFileInterface|array<string,UploadedFileInterface>>
     */
    public static function normalizeUploadedFiles(
        array $files
    ): array {
        foreach ($files as $file) {
            if (is_array($file)) {
                static::normalizeUploadedFiles($file);
                continue;
            }

            // @phpstan-ignore-next-line
            if (!$file instanceof UploadedFileInterface) {
                throw Exceptional::InvalidArgument(
                    message: 'Invalid uploaded file array - files must be instances of UploadedFileInterface'
                );
            }
        }

        return $files;
    }




    /**
     * @return array<string, mixed>
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }


    public function withAttribute(
        string $name,
        mixed $value
    ): static {
        $output = clone $this;
        $output->attributes[$name] = $value;

        return $output;
    }

    public function getAttribute(
        string $name,
        mixed $default = null
    ): mixed {
        if (!array_key_exists($name, $this->attributes)) {
            return $default;
        }

        return $this->attributes[$name];
    }

    public function withoutAttribute(
        string $name
    ): static {
        $output = clone $this;
        unset($output->attributes[$name]);

        return $output;
    }




    /**
     * @return array<string, mixed>
     */
    public function getServerParams(): array
    {
        return $this->server;
    }

    public function getServerParam(
        string $key
    ): mixed {
        return $this->server[$key] ?? null;
    }

    public function hasServerParam(
        string $key
    ): bool {
        return isset($this->server[$key]);
    }



    /**
     * @param array<mixed>|object|null $data
     */
    public function withParsedBody(
        mixed $data
    ): static {
        $output = clone $this;
        $output->parsedBody = $data;

        return $output;
    }

    /**
     * @return array<mixed>|object|null
     */
    public function getParsedBody(): mixed
    {
        return $this->parsedBody;
    }


    public function getIp(): Ip
    {
        return Harvest::extractIpFromRequest($this);
    }
}
