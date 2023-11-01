<?php

/**
 * @package Harvest
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Harvest\Middleware;

use DecodeLabs\Exceptional;
use DecodeLabs\Glitch;
use DecodeLabs\Glitch\Proxy as GlitchProxy;
use DecodeLabs\Harvest;
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
            return $this->handleException($e, $request, $next);
        }
    }

    protected function handleException(
        Throwable $e,
        Request $request,
        Handler $next
    ): Response {
        Glitch::logException($e);

        try {
            if ($e instanceof Exceptional\Exception) {
                $code = $e->getHttpStatus();
            } else {
                $code = 500;
            }

            $url = $request->getUri()
                ->withPath('/error/' . $code)
                ->withQuery('');

            $errorRequest = $request
                ->withAttribute('error', $e)
                ->withUri($url);

            return $next->handle($errorRequest);
        } catch (Throwable $f) {
            return $this->handleCatastrophe($e, $f, $request);
        }
    }

    protected function handleCatastrophe(
        Throwable $e,
        Throwable $f,
        Request $request
    ): Response {
        if (class_exists(Glitch::class)) {
            Glitch::handleException($f);
            exit;
        }

        GlitchProxy::logException($f);

        return Harvest::text(
            '500 Internal Server Error' . "\n\n" .
            'An unexpected error occurred while processing your request.' . "\n\n" .
            'Request: ' . $request->getUri()->getPath() . "\n\n" .
            'Initial error: ' . $e . "\n\n" .
            'Error handler failure: ' . $f,
            500
        );
    }
}
