<?php

/**
 * @package Harvest
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Harvest\Middleware;

use DateTimeImmutable;
use DecodeLabs\Harvest;
use DecodeLabs\Monarch;
use DecodeLabs\Harvest\Middleware as HarvestMiddleware;
use DecodeLabs\Harvest\MiddlewareGroup;
use Psr\Http\Message\ResponseInterface as PsrResponse;
use Psr\Http\Message\ServerRequestInterface as PsrRequest;
use Psr\Http\Server\RequestHandlerInterface as PsrHandler;

class LastModified implements HarvestMiddleware
{
    public MiddlewareGroup $group {
        get => MiddlewareGroup::Outbound;
    }

    public int $priority {
        get => 0;
    }

    public function process(
        PsrRequest $request,
        PsrHandler $next
    ): PsrResponse {
        $response = $next->handle($request);

        if(
            $request->getMethod() !== 'GET' ||
            $response->getStatusCode() !== 200
        ) {
            return $response;
        }

        if(
            $this->isUnmodifiedSince($request, $response) ||
            $this->isEtagUnmodified($request, $response)
        ) {
            return $response->withStatus(304);
        }

        return $response;
    }

    protected function isUnmodifiedSince(
        PsrRequest $request,
        PsrResponse $response
    ): bool {
        $lastModified = $response->getHeaderLine('Last-Modified');
        $modifiedSince = explode(';', $request->getHeaderLine('If-Modified-Since'))[0];

        if(
            $lastModified === '' ||
            $modifiedSince === ''
        ) {
            return false;
        }

        $lastModified = new DateTimeImmutable($lastModified);
        $modifiedSince = new DateTimeImmutable($modifiedSince);

        if($lastModified->getTimestamp() > $modifiedSince->getTimestamp()) {
            return false;
        }

        return true;
    }

    protected function isEtagUnmodified(
        PsrRequest $request,
        PsrResponse $response
    ): bool {
        $etag = $response->getHeaderLine('ETag');
        $ifNoneMatch = $request->getHeaderLine('If-None-Match');

        if(
            $etag === '' ||
            $ifNoneMatch === ''
        ) {
            return false;
        }

        if($etag !== $ifNoneMatch) {
            return false;
        }

        return true;
    }
}
