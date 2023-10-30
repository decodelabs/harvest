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
use DecodeLabs\Harvest\Response as ResponseNamespace;
use DecodeLabs\Harvest\Response\Html as HtmlResponse;
use DecodeLabs\Harvest\Response\Json as JsonResponse;
use DecodeLabs\Harvest\Response\Redirect as RedirectResponse;
use DecodeLabs\Harvest\Response\Stream as StreamResponse;
use DecodeLabs\Harvest\Response\Text as TextResponse;
use DecodeLabs\Harvest\Response\Xml as XmlResponse;
use DecodeLabs\Singularity;
use DecodeLabs\Singularity\Url;
use DecodeLabs\Veneer;
use DecodeLabs\Veneer\LazyLoad;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\StreamInterface as StreamInterface;
use Psr\Http\Message\UriFactoryInterface as UriFactory;
use Psr\Http\Message\UriInterface;
use Psr\Http\Server\MiddlewareInterface as Middleware;
use Stringable;

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




    /**
     * Create stream respomse
     *
     * @param array<string, string|Stringable|array<string|Stringable>> $headers
     */
    public function stream(
        string|Channel|StreamInterface $body = 'php://memory',
        int $status = 200,
        array $headers = []
    ): StreamResponse {
        return new StreamResponse($body, $status, $headers);
    }

    /**
     * Create text response
     *
     * @param array<string, string|Stringable|array<string|Stringable>> $headers
     */
    public function text(
        string $text,
        int $status = 200,
        array $headers = []
    ): TextResponse {
        return new TextResponse($text, $status, $headers);
    }

    /**
     * Create HTML response
     *
     * @param array<string, string|Stringable|array<string|Stringable>> $headers
     */
    public function html(
        string $html,
        int $status = 200,
        array $headers = []
    ): HtmlResponse {
        return new HtmlResponse($html, $status, $headers);
    }

    /**
     * Create JSON response
     *
     * @param array<string, string|Stringable|array<string|Stringable>> $headers
     */
    public function json(
        mixed $data,
        int $status = 200,
        array $headers = []
    ): JsonResponse {
        return new JsonResponse($data, $status, $headers);
    }

    /**
     * Create XML response
     *
     * @param array<string, string|Stringable|array<string|Stringable>> $headers
     */
    public function xml(
        string $xml,
        int $status = 200,
        array $headers = []
    ): XmlResponse {
        return new XmlResponse($xml, $status, $headers);
    }

    /**
     * Create redirect response
     *
     * @param array<string, string|Stringable|array<string|Stringable>> $headers
     */
    public function redirect(
        string|UriInterface $uri,
        int $status = 302,
        array $headers = []
    ): RedirectResponse {
        return new RedirectResponse($uri, $status, $headers);
    }
}


// Register interfaces
/** @phpstan-ignore-next-line */
Archetype::extend(Middleware::class, MiddlewareNamespace::class);
/** @phpstan-ignore-next-line */
Archetype::extend(Response::class, ResponseNamespace::class);

// Register Veneer
Veneer::register(Context::class, Harvest::class);
