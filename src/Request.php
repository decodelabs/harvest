<?php

/**
 * @package Harvest
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Harvest;

use DecodeLabs\Deliverance\Channel\Stream as Channel;
use DecodeLabs\Exceptional;
use DecodeLabs\Singularity;
use DecodeLabs\Singularity\Url;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\UriInterface;
use Stringable;

class Request implements ServerRequestInterface
{
    use MessageTrait {
        MessageTrait::__construct as private __messageConstruct;
    }

    public const METHODS = [
        'OPTIONS', 'GET', 'HEAD', 'POST', 'PUT',
        'DELETE', 'PATCH', 'TRACE', 'CONNECT'
    ];

    protected string $method = 'GET';
    protected ?string $target = null;
    protected Url $uri;

    /**
     * @var array<string, string|array<string, mixed>>|null
     */
    protected ?array $query = null;

    /**
     * @var array<string, mixed>
     */
    protected array $attributes = [];

    /**
     * @var array<string, string>
     */
    protected array $cookies = [];

    /**
     * @var array<string, UploadedFileInterface|array<string, UploadedFileInterface>>
     */
    protected array $files = [];

    /**
     * @var array<string, mixed>
     */
    protected array $server = [];

    /**
     * @var array<mixed>|object|null
     */
    protected mixed $parsedBody = null;


    /**
     * Init with global data
     *
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
        string|Channel|StreamInterface $body,
        array $headers = [],
        ?array $query = null,
        array $cookies = [],
        array $files = [],
        array $server = [],
        mixed $parsedBody = null,
        string $protocol = '1.1',
    ) {
        $this->__messageConstruct(
            $body,
            $headers,
            $protocol
        );

        // Method
        $method = strtoupper($method);

        if (!$this->isValidMethod($method)) {
            throw Exceptional::InvalidArgument(
                'Invalid HTTP method: ' . $method
            );
        }

        $this->method = $method;

        // Uri
        if (!$uri instanceof Url) {
            $uri = Singularity::url((string)$uri);
        }

        $this->uri = $uri;
        $this->query = $query;
        $this->cookies = $cookies;
        $this->files = $this->normalizeUploadedFiles($files);
        $this->server = $server;
        $this->parsedBody = $parsedBody;

        // Headers
        if (
            !$this->hasHeader('host') &&
            ($host = $this->uri->getHost())
        ) {
            if ($port = $this->uri->getPort()) {
                $host .= ':' . $port;
            }

            $this->headerAliases['host'] = 'Host';
            $this->headers['Host'] = [$host];
        }
    }


    /**
     * New instance with method
     */
    public function withMethod(
        string $method
    ): static {
        $method = strtoupper($method);

        if (!$this->isValidMethod($method)) {
            throw Exceptional::InvalidArgument(
                'Invalid HTTP method: ' . $method
            );
        }

        $output = clone $this;
        $output->method = $method;

        return $output;
    }

    /**
     * Get method
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * Check method
     */
    public static function isValidMethod(?string $method): bool
    {
        return in_array($method, static::METHODS);
    }

    /**
     * Get list of available HTTP methods
     *
     * @return array<string>
     */
    public static function getValidMethods(): array
    {
        return static::METHODS;
    }




    /**
     * New instance with custom target
     */
    public function withRequestTarget(
        string $target
    ): static {
        if (preg_match('/\s/', $target)) {
            throw Exceptional::InvalidArgument(
                'Request target must not contain spaces'
            );
        }

        $output = clone $this;
        $output->target = $target;

        return $output;
    }

    /**
     * Get stored request target or generate from uri
     */
    public function getRequestTarget(): string
    {
        if ($this->target !== null) {
            return $this->target;
        }

        $output = $this->uri->getPath();

        if (!empty($query = $this->uri->getQuery())) {
            $output .= '?' . $query;
        }

        return $output;
    }



    /**
     * New instance with URI replaced
     */
    public function withUri(
        string|UriInterface $uri,
        bool $preserveHost = false
    ): static {
        if (!$uri instanceof Url) {
            $uri = Singularity::url((string)$uri);
        }

        $output = clone $this;
        $output->uri = $uri;
        $host = $uri->getHost();

        if (
            (
                $preserveHost &&
                $this->hasHeader('Host')
            ) ||
            empty($host)
        ) {
            return $output;
        }

        if ($port = $uri->getPort()) {
            $host .= ':' . $port;
        }


        if (isset($this->headerAliases['host'])) {
            unset($output->headers[$this->headerAliases['host']]);
        }

        $output->headerAliases['host'] = 'Host';
        $output->headers['Host'] = [$host];

        return $output;
    }

    /**
     * Get uri instance
     */
    public function getUri(): Url
    {
        return $this->uri;
    }



    /**
     * Get injected query params
     *
     * @return array<string, string|array<string, mixed>>
     */
    public function getQueryParams(): array
    {
        /** @var array<string, string|array<string, mixed>> $output */
        $output = $this->query ?? $this->uri->parseQuery()->toArray();
        return $output;
    }

    /**
     * New instance with query params set
     *
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
     * New instance with cookies set
     *
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
     * Get injected cookies
     *
     * @return array<string, string>
     */
    public function getCookieParams(): array
    {
        return $this->cookies;
    }

    /**
     * Get single cookie value
     */
    public function getCookieParam(
        string $key
    ): ?string {
        if (!isset($this->cookies[$key])) {
            return null;
        }

        return (string)$this->cookies[$key];
    }

    /**
     * Is $cookie set?
     */
    public function hasCookieParam(string $key): bool
    {
        return isset($this->cookies[$key]);
    }



    /**
     * New instance with files added
     *
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
     * Get injected file list
     *
     * @return array<string, UploadedFileInterface|array<string, UploadedFileInterface>>
     */
    public function getUploadedFiles(): array
    {
        return $this->files;
    }

    /**
     * Prepare file array
     *
     * @param array<string, UploadedFileInterface|array<string, UploadedFileInterface>> $files
     * @return array<string, UploadedFileInterface|array<string, UploadedFileInterface>>
     */
    public static function normalizeUploadedFiles(
        array $files
    ): array {
        foreach ($files as $file) {
            if (is_array($file)) {
                static::normalizeUploadedFiles($file);
                continue;
            }

            if (!$file instanceof UploadedFileInterface) {
                throw Exceptional::InvalidArgument(
                    'Invalid uploaded file array - files must be instances of UploadedFileInterface'
                );
            }
        }

        return $files;
    }




    /**
     * Get attribute list
     *
     * @return array<string, mixed>
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }


    /**
     * New instance with attribute set
     */
    public function withAttribute(
        string $name,
        mixed $value
    ): static {
        $output = clone $this;
        $output->attributes[$name] = $value;

        return $output;
    }

    /**
     * Get attribute value
     */
    public function getAttribute(
        string $name,
        mixed $default = null
    ): mixed {
        if (!array_key_exists($name, $this->attributes)) {
            return $default;
        }

        return $this->attributes[$name];
    }

    /**
     * New instance with attribute removed
     */
    public function withoutAttribute(
        string $name
    ): static {
        $output = clone $this;
        unset($output->attributes[$name]);

        return $output;
    }




    /**
     * Get $_SERVER equiv
     *
     * @return array<string, mixed>
     */
    public function getServerParams(): array
    {
        return $this->server;
    }

    /**
     * Get single server param
     */
    public function getServerParam(
        string $key
    ): mixed {
        return $this->server[$key] ?? null;
    }

    /**
     * Is $key in $server?
     */
    public function hasServerParam(string $key): bool
    {
        return isset($this->server[$key]);
    }



    /**
     * New instance with structured body data added
     *
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
     * Get structured body data
     *
     * @return array<mixed>|object|null
     */
    public function getParsedBody(): mixed
    {
        return $this->parsedBody;
    }
}
