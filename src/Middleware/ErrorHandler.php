<?php

/**
 * @package Harvest
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Harvest\Middleware;

use DecodeLabs\Exceptional;
use DecodeLabs\Glitch;
use DecodeLabs\Harvest;
use DecodeLabs\Harvest\Middleware as HarvestMiddleware;
use DecodeLabs\Harvest\MiddlewareGroup;
use DecodeLabs\Harvest\NotFoundException;
use DecodeLabs\Monarch;
use Psr\Http\Message\ResponseInterface as PsrResponse;
use Psr\Http\Message\ServerRequestInterface as PsrRequest;
use Psr\Http\Server\RequestHandlerInterface as PsrHandler;
use Throwable;

class ErrorHandler implements HarvestMiddleware
{
    public MiddlewareGroup $group {
        get => MiddlewareGroup::ErrorHandler;
    }

    public int $priority {
        get => -100;
    }


    public function process(
        PsrRequest $request,
        PsrHandler $next
    ): PsrResponse {
        try {
            return $next->handle($request);
        } catch (Throwable $e) {
            return $this->handleException($e, $request, $next);
        }
    }

    protected function handleException(
        Throwable $e,
        PsrRequest $request,
        PsrHandler $next
    ): PsrResponse {
        Monarch::logException($e);

        try {
            if ($e instanceof Exceptional\Exception) {
                $code = $e->http ?? 500;
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
        PsrRequest $request
    ): PsrResponse {
        if ($request->getHeaderLine('Accept') === 'application/json') {
            $error = $f instanceof NotFoundException ? $e : $f;

            if ($error instanceof Exceptional\Exception) {
                $code = $error->http ?? 500;
                $data = $error->data;
            } else {
                $code = 500;
                $data = null;
            }

            return Harvest::json([
                'error' => (string)$error,
                'data' => $data
            ], $code, [
                'Access-Control-Allow-Origin' => '*'
            ]);
        }

        if (class_exists(Glitch::class)) {
            Glitch::handleException(
                $f instanceof NotFoundException ?
                    $e : $f
            );
            exit;
        }

        Monarch::logException($f);

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
