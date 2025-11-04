<?php

/**
 * Harvest
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Harvest\Client;

use DecodeLabs\Deliverance\Channel\Stream as Channel;
use DecodeLabs\Exceptional;
use DecodeLabs\Harvest\MessageTrait;
use DecodeLabs\Singularity;
use DecodeLabs\Singularity\Url;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
use Stringable;

class Request implements RequestInterface
{
    use MessageTrait {
        MessageTrait::__construct as protected __messageConstruct;
    }

    /**
     * @var list<string>
     */
    public const array Methods = [
        'OPTIONS', 'GET', 'HEAD', 'POST', 'PUT',
        'DELETE', 'PATCH', 'TRACE', 'CONNECT'
    ];


    public protected(set) string $method = 'GET';
    protected ?string $target = null;
    public protected(set) Url $uri;


    /**
     * @param array<string,string|Stringable|array<string|Stringable>> $headers
     */
    public function __construct(
        string $method,
        string|UriInterface $uri,
        string|Channel|StreamInterface|null $body = null,
        array $headers = [],
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
                message: 'Invalid HTTP method: ' . $method
            );
        }

        $this->method = $method;

        // Uri
        if (!$uri instanceof Url) {
            $uri = Singularity::url((string)$uri);
        }

        $this->uri = $uri;

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


    public function withMethod(
        string $method
    ): static {
        $method = strtoupper($method);

        if (!$this->isValidMethod($method)) {
            throw Exceptional::InvalidArgument(
                message: 'Invalid HTTP method: ' . $method
            );
        }

        $output = clone $this;
        $output->method = $method;

        return $output;
    }


    public function getMethod(): string
    {
        return $this->method;
    }


    public static function isValidMethod(
        ?string $method
    ): bool {
        return in_array($method, static::Methods);
    }

    /**
     * @return array<string>
     */
    public static function getValidMethods(): array
    {
        return static::Methods;
    }



    public function withRequestTarget(
        string $target
    ): static {
        if (preg_match('/\s/', $target)) {
            throw Exceptional::InvalidArgument(
                message: 'Request target must not contain spaces'
            );
        }

        $output = clone $this;
        $output->target = $target;

        return $output;
    }

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

    public function getUri(): Url
    {
        return $this->uri;
    }
}
