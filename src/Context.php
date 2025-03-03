<?php

/**
 * @package Harvest
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Harvest;

use Closure;
use DecodeLabs\Archetype;
use DecodeLabs\Atlas\File;
use DecodeLabs\Coercion;
use DecodeLabs\Compass\Ip;
use DecodeLabs\Deliverance\Channel\Stream as Channel;
use DecodeLabs\Harvest;
use DecodeLabs\Harvest\Message\Generator as MessageGenerator;
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
use Generator;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as ServerRequest;
use Psr\Http\Message\StreamInterface as StreamInterface;
use Psr\Http\Message\UriFactoryInterface as UriFactory;
use Psr\Http\Message\UriInterface;
use Psr\Http\Server\MiddlewareInterface as Middleware;
use Stringable;
use Throwable;

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
     * Create transport
     */
    public function createTransport(
        ?string $name = null
    ): Transport {
        $class = Archetype::resolve(Transport::class, $name);
        return new $class();
    }




    /**
     * Create stream respomse
     *
     * @param array<string,string|Stringable|array<string|Stringable>> $headers
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
     * @param string|Stringable|Generator<string|Stringable>|Closure(TextResponse=):(string|Stringable|Generator<string|Stringable>) $text
     * @param array<string,string|Stringable|array<string|Stringable>> $headers
     */
    public function text(
        string|Stringable|Generator|Closure $text,
        int $status = 200,
        array $headers = []
    ): TextResponse {
        return new TextResponse($text, $status, $headers);
    }

    /**
     * Create HTML response
     * @param string|Stringable|Generator<string|Stringable>|Closure(HtmlResponse=):(string|Stringable|Generator<string|Stringable>) $html
     * @param array<string,string|Stringable|array<string|Stringable>> $headers
     */
    public function html(
        string|Stringable|Generator|Closure $html,
        int $status = 200,
        array $headers = []
    ): HtmlResponse {
        return new HtmlResponse($html, $status, $headers);
    }

    /**
     * Create JSON response
     *
     * @param array<string,string|Stringable|array<string|Stringable>> $headers
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
     * @param string|Stringable|Generator<string|Stringable>|Closure(XmlResponse=):(string|Stringable|Generator<string|Stringable>) $xml
     * @param array<string,string|Stringable|array<string|Stringable>> $headers
     */
    public function xml(
        string|Stringable|Generator|Closure $xml,
        int $status = 200,
        array $headers = []
    ): XmlResponse {
        return new XmlResponse($xml, $status, $headers);
    }

    /**
     * Create redirect response
     *
     * @param array<string,string|Stringable|array<string|Stringable>> $headers
     */
    public function redirect(
        string|UriInterface $uri,
        int $status = 302,
        array $headers = []
    ): RedirectResponse {
        return new RedirectResponse($uri, $status, $headers);
    }


    /**
     * Create iteration response
     *
     * @param iterable<int|string,string>|Closure(MessageGenerator):(iterable<int|string, string>|null) $iterator
     * @param array<string,string|Stringable|array<string|Stringable>> $headers
     */
    public function generator(
        iterable|Closure $iterator,
        int $status = 200,
        array $headers = [],
    ): StreamResponse {
        return $this->stream(
            new MessageGenerator($iterator),
            $status,
            [
                'Transfer-Encoding' => 'chunked',
                'X-Accel-Buffering' => 'no'
            ] + $headers
        );
    }

    /**
     * Create iteration response
     *
     * @param iterable<int|string,string>|Closure(MessageGenerator):(iterable<int|string, string>|null) $iterator
     * @param array<string,string|Stringable|array<string|Stringable>> $headers
     */
    public function liveGenerator(
        iterable|Closure $iterator,
        int $status = 200,
        array $headers = []
    ): StreamResponse {
        $generator = new MessageGenerator($iterator, false);

        // Send whitespace to clear browser buffers
        $generator->write(str_repeat(' ', 1024) . "\n");

        return $this->stream(
            $generator,
            $status,
            [
                'Content-Type' => 'text/plain',
                'Transfer-Encoding' => 'chunked',
                'X-Accel-Buffering' => 'no'
            ] + $headers
        );
    }



    /**
     * Extract IP from request
     */
    public function extractIpFromRequest(
        ServerRequest $request
    ): Ip {
        $ips = '';
        $server = $request->getServerParams();

        if (isset($server['HTTP_X_FORWARDED_FOR'])) {
            $ips .= Coercion::asString($server['HTTP_X_FORWARDED_FOR']) . ',';
        }

        if (isset($server['REMOTE_ADDR'])) {
            $ips .= Coercion::asString($server['REMOTE_ADDR']) . ',';
        }

        if (isset($server['HTTP_CLIENT_IP'])) {
            $ips .= Coercion::asString($server['HTTP_CLIENT_IP']) . ',';
        }

        $parts = explode(',', rtrim($ips, ','));

        while (!empty($parts)) {
            $ip = trim(array_shift($parts));

            try {
                return Ip::parse($ip);
            } catch (Throwable $e) {
            }
        }

        return new Ip('0.0.0.0');
    }
}


// Register interfaces
Archetype::alias(
    Middleware::class,
    /** @phpstan-ignore-next-line */
    MiddlewareNamespace::class
);

Archetype::alias(
    Response::class,
    ResponseNamespace::class
);

// Register Veneer
Veneer\Manager::getGlobalManager()->register(
    Context::class,
    Harvest::class
);
