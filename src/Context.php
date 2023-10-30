<?php

/**
 * @package Harvest
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Harvest;

use DecodeLabs\Archetype;
use DecodeLabs\Atlas\File;
use DecodeLabs\Deliverance\Channel\Stream as Channel;
use DecodeLabs\Harvest;
use DecodeLabs\Harvest\Message\Stream;
use DecodeLabs\Harvest\Middleware as MiddlewareNamespace;
use DecodeLabs\Harvest\Request\Factory\Environment as EnvironmentFactory;
use DecodeLabs\Singularity;
use DecodeLabs\Singularity\Url;
use DecodeLabs\Veneer;
use DecodeLabs\Veneer\LazyLoad;

use Psr\Http\Message\StreamInterface as StreamInterface;
use Psr\Http\Message\UriFactoryInterface as UriFactory;
use Psr\Http\Message\UriInterface;
use Psr\Http\Server\MiddlewareInterface as Middleware;

#[LazyLoad]
class Context implements UriFactory
{
    /**
     * Create Uri as PSR-17 factory
     */
    public function createUri(
        string $uri = ''
    ): Url {
        return Singularity::url($uri);
    }

    /**
     * Create Stream as PSR-17 factory
     */
    public function createStream(
        string $content = ''
    ): StreamInterface {
        return Stream::fromString($content);
    }

    /**
     * Create Stream as PSR-17 factory
     */
    public function createStreamFromFile(
        string|File $filename,
        string $mode = 'r'
    ): StreamInterface {
        return new Stream((string)$filename, $mode);
    }

    /**
     * Create Stream as PSR-17 factory
     *
     * @param resource $resource
     */
    public function createStreamFromResource(
        $resource
    ): StreamInterface {
        return new Stream(new Channel($resource));
    }



    /**
     * Create request from environment
     *
     * @param string|UriInterface $uri
     * @param array<string, mixed>|null $server
     */
    public function createRequestFromEnvironment(
        ?string $method = null,
        $uri = null,
        ?array $server = null
    ): Request {
        return (new EnvironmentFactory())->createServerRequest($method, $uri, $server);
    }
}


// Register interfaces
/** @phpstan-ignore-next-line */
Archetype::extend(Middleware::class, MiddlewareNamespace::class);

// Register Veneer
Veneer::register(Context::class, Harvest::class);
