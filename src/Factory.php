<?php

/**
 * @package Harvest
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Harvest;

use DecodeLabs\Deliverance\Channel\Stream as Channel;
use DecodeLabs\Harvest\Client\Request as ClientRequest;
use DecodeLabs\Harvest\Message\Stream;
use DecodeLabs\Harvest\Message\UploadedFile;
use DecodeLabs\Harvest\Response\Stream as Response;
use DecodeLabs\Singularity;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;

class Factory implements
    RequestFactoryInterface,
    ResponseFactoryInterface,
    ServerRequestFactoryInterface,
    StreamFactoryInterface,
    UploadedFileFactoryInterface,
    UriFactoryInterface
{
    public function createRequest(
        string $method,
        $uri
    ): RequestInterface {
        return new ClientRequest($method, $uri);
    }

    public function createResponse(
        int $code = 200,
        string $reasonPhrase = ''
    ): ResponseInterface {
        $output = new Response(
            status: $code
        );

        if ($reasonPhrase !== '') {
            $output = $output->withStatus($code, $reasonPhrase);
        }

        return $output;
    }

    public function createStream(
        string $content = ''
    ): StreamInterface {
        return Stream::fromString($content);
    }

    public function createStreamFromFile(
        string $filename,
        string $mode = 'r'
    ): StreamInterface {
        return new Stream($filename, $mode);
    }

    public function createStreamFromResource(
        $resource
    ): StreamInterface {
        return new Stream(new Channel($resource));
    }

    public function createUploadedFile(
        StreamInterface $stream,
        int $size = null,
        int $error = \UPLOAD_ERR_OK,
        string $clientFilename = null,
        string $clientMediaType = null
    ): UploadedFileInterface {
        if (null === $size) {
            $size = $stream->getSize();
        }

        return new UploadedFile($stream, $size, $error, $clientFilename, $clientMediaType);
    }

    public function createUri(
        string $uri = ''
    ): UriInterface {
        return Singularity::url($uri);
    }

    /**
     * @param array<string, mixed> $serverParams
     */
    public function createServerRequest(
        string $method,
        $uri,
        array $serverParams = []
    ): ServerRequestInterface {
        return new Request(
            method: $method,
            uri: $uri,
            server: $serverParams
        );
    }
}
