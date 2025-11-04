<?php

/**
 * Harvest
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Harvest\Middleware;

use DecodeLabs\Coercion;
use DecodeLabs\Harvest\Middleware as HarvestMiddleware;
use DecodeLabs\Harvest\MiddlewareGroup;
use DecodeLabs\Monarch;
use Psr\Http\Message\ResponseInterface as PsrResponse;
use Psr\Http\Message\ServerRequestInterface as PsrRequest;
use Psr\Http\Server\RequestHandlerInterface as PsrHandler;

class OverrideMethod implements HarvestMiddleware
{
    public MiddlewareGroup $group {
        get {
            return MiddlewareGroup::Inbound;
        }
    }

    public int $priority {
        get {
            return -10;
        }
    }

    public function process(
        PsrRequest $request,
        PsrHandler $next
    ): PsrResponse {
        if (
            Monarch::isDevelopment() &&
            ($method = ($request->getQueryParams()['method'] ?? null)) !== null
        ) {
            $request = $request->withMethod(
                Coercion::asString($method)
            );
        }


        return $next->handle($request);
    }
}
