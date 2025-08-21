<?php

/**
 * @package Harvest
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs;

use DecodeLabs\Atlas\File;
use DecodeLabs\Compass\Ip;
use DecodeLabs\Deliverance\Channel\Stream as Channel;
use DecodeLabs\Harvest\Cookie\Collection as CookieCollection;
use DecodeLabs\Harvest\Message\Stream;
use DecodeLabs\Harvest\Middleware;
use DecodeLabs\Harvest\Request;
use DecodeLabs\Harvest\Request\Factory\Environment as EnvironmentFactory;
use DecodeLabs\Harvest\Response as HarvestResponse;
use DecodeLabs\Harvest\Transformer\Generic as TransformHandler;
use DecodeLabs\Harvest\Transport;
use DecodeLabs\Kingdom\EagreService;
use DecodeLabs\Kingdom\ServiceTrait;
use DecodeLabs\Singularity\Url;
use Psr\Http\Message\ResponseInterface as PsrResponse;
use Psr\Http\Message\ServerRequestInterface as ServerRequest;
use Psr\Http\Message\StreamInterface as StreamInterface;
use Psr\Http\Message\UriFactoryInterface as UriFactory;
use Psr\Http\Message\UriInterface;
use Psr\Http\Server\MiddlewareInterface as PsrMiddleware;
use Throwable;

class Harvest implements UriFactory, EagreService
{
    use ServiceTrait;

    public function __construct(
        public CookieCollection $cookies,
        protected Archetype $archetype
    ) {
        $this->archetype->alias(
            PsrMiddleware::class,
            Middleware::class
        );

        $this->archetype->alias(
            PsrResponse::class,
            HarvestResponse::class
        );
    }


    public function createUri(
        string $uri = ''
    ): Url {
        return Singularity::url($uri);
    }

    public function createStream(
        string $content = ''
    ): StreamInterface {
        return Stream::fromString($content);
    }

    public function createStreamFromFile(
        string|File $filename,
        string $mode = 'r'
    ): StreamInterface {
        return new Stream((string)$filename, $mode);
    }

    /**
     * @param resource $resource
     */
    public function createStreamFromResource(
        $resource
    ): StreamInterface {
        return new Stream(new Channel($resource));
    }



    /**
     * @param string|UriInterface $uri
     * @param array<string,mixed>|null $server
     */
    public function createRequestFromEnvironment(
        ?string $method = null,
        $uri = null,
        ?array $server = null
    ): Request {
        return (new EnvironmentFactory())->createServerRequest($method, $uri, $server);
    }



    public function createTransport(
        ?string $name = null
    ): Transport {
        $class = $this->archetype->resolve(Transport::class, $name);
        return new $class();
    }





    public function transform(
        ServerRequest $request,
        mixed $response
    ): PsrResponse {
        return (new TransformHandler($this->archetype))->transform($request, $response);
    }




    public static function extractIpFromRequest(
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
