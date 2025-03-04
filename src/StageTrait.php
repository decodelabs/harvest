<?php

/**
 * @package Harvest
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Harvest;

use DecodeLabs\Exceptional;
use Fiber;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface as Middleware;

/**
 * @phpstan-require-implements Stage
 */
trait StageTrait
{
    public int $priority {
        get => $this->priority ??= $this->defaultPriority;
    }

    public int $defaultPriority {
        get {
            $middleware = $this->middleware;

            if (!$middleware instanceof PriorityProvider) {
                return 0;
            }

            return $middleware->getPriority();
        }
    }

    /**
     * Fiber interchange
     */
    public function handle(
        Request $request
    ): Response {
        $response = Fiber::suspend($request);

        if (!$response instanceof Response) {
            throw Exceptional::UnexpectedValue(
                message: 'Middleware did not return a response'
            );
        }

        return $response;
    }

    /**
     * Process middleware
     */
    public function run(
        Request $request
    ): Response {
        if ($middleware = $this->middleware) {
            return $middleware->process($request, $this);
        }

        return $this->handle($request);
    }
}
