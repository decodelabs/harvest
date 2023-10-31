<?php

/**
 * @package Harvest
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Harvest\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface as Middleware;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Throwable;

class ErrorHandler implements Middleware
{
    /**
     * Process middleware
     */
    public function process(
        Request $request,
        Handler $next
    ): Response {
        try {
            return $next->handle($request);
        } catch (Throwable $e) {
            return $this->handleException($e, $request);
        }
    }

    public function handleException(
        Throwable $e,
        Request $request
    ): Response {
        dd($e);
    }
}
