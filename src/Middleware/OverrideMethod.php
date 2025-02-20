<?php

/**
 * @package Harvest
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Harvest\Middleware;

use DecodeLabs\Coercion;
use DecodeLabs\Genesis;
use DecodeLabs\Harvest\PriorityProvider;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface as Middleware;
use Psr\Http\Server\RequestHandlerInterface as Handler;

class OverrideMethod implements
    Middleware,
    PriorityProvider
{
    /**
     * Get default priority
     */
    public function getPriority(): int
    {
        return -1;
    }

    /**
     * Process middleware
     */
    public function process(
        Request $request,
        Handler $next
    ): Response {
        if (class_exists(Genesis::class)) {
            $development = Genesis::$environment->isDevelopment();
        } else {
            $development = true;
        }


        if (
            $development &&
            ($method = ($request->getQueryParams()['method'] ?? null)) !== null
        ) {
            $request = $request->withMethod(
                Coercion::asString($method)
            );
        }


        return $next->handle($request);
    }
}
