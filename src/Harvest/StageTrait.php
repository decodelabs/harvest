<?php

/**
 * @package Harvest
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Harvest;

use DecodeLabs\Exceptional;
use DecodeLabs\Harvest\Middleware as HarvestMiddleware;
use Fiber;
use Psr\Http\Message\ResponseInterface as PsrResponse;
use Psr\Http\Message\ServerRequestInterface as PsrRequest;

/**
 * @phpstan-require-implements Stage
 */
trait StageTrait
{
    public int $priority {
        get => $this->priority ??= $this->defaultPriority;
    }

    public MiddlewareGroup $group {
        get => $this->group ??= $this->defaultGroup;
    }

    public int $defaultPriority {
        get {
            $middleware = $this->middleware;

            if (!$middleware instanceof HarvestMiddleware) {
                return 10;
            }

            return $middleware->priority;
        }
    }

    public MiddlewareGroup $defaultGroup {
        get {
            $middleware = $this->middleware;

            if (!$middleware instanceof HarvestMiddleware) {
                return MiddlewareGroup::Generic;
            }

            return $middleware->group;
        }
    }

    public function handle(
        PsrRequest $request
    ): PsrResponse {
        $response = Fiber::suspend($request);

        if (!$response instanceof PsrResponse) {
            throw Exceptional::UnexpectedValue(
                message: 'Middleware did not return a response'
            );
        }

        return $response;
    }

    public function run(
        PsrRequest $request
    ): PsrResponse {
        if ($middleware = $this->middleware) {
            return $middleware->process($request, $this);
        }

        return $this->handle($request);
    }
}
