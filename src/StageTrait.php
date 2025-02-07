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
    protected ?int $priority = null;

    /**
     * Set prioerty
     *
     * @return $this
     */
    public function setPriority(
        ?int $priority
    ): static {
        $this->priority = $priority;
        return $this;
    }

    /**
     * Get priority
     */
    public function getPriority(): int
    {
        return $this->priority ?? $this->getDefaultPriority();
    }

    /**
     * Get default priority
     */
    public function getDefaultPriority(): int
    {
        $middleware = $this->getMiddleware();

        if (!$middleware instanceof PriorityProvider) {
            return 0;
        }

        return $middleware->getPriority();
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
                'Middleware did not return a response'
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
        return $this->getMiddleware()->process($request, $this);
    }
}
