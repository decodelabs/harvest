<?php

/**
 * @package Harvest
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Harvest;

use Closure;
use Countable;
use DecodeLabs\Exceptional;
use DecodeLabs\Harvest\Stage\Closure as ClosureStage;
use DecodeLabs\Harvest\Stage\Deferred as DeferredStage;
use DecodeLabs\Harvest\Stage\Instance as InstanceStage;

use Psr\Container\ContainerInterface as Container;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface as Middleware;
use Psr\Http\Server\RequestHandlerInterface as Handler;

class Dispatcher implements
    Handler,
    Countable
{
    protected ?Stage $stage;
    protected ?Container $container;

    /**
     * Init with Container
     */
    public function __construct(
        ?Container $container = null,
    ) {
        $this->container = $container;
    }


    /**
     * Begin stage stack navigation
     */
    public function handle(Request $request): Response
    {
        if (!isset($this->stage)) {
            throw Exceptional::Setup(
                'No middleware stack has been set'
            );
        }

        return $this->stage->handle($request);
    }


    /**
     * Get stage if available
     */
    public function getStage(): ?Stage
    {
        return $this->stage ?? null;
    }


    /**
     * Add middleware to stack
     *
     * @param string|class-string<Middleware>|Middleware|Closure(Request, Handler):Response|array<string|class-string<Middleware>|Middleware|Closure(Request, Handler):Response> ...$middlewares
     *
     * @return $this
     */
    public function add(
        string|array|Closure|Middleware ...$middlewares
    ): static {
        foreach ($middlewares as $middleware) {
            // Middleware
            if ($middleware instanceof Middleware) {
                $this->stage = new InstanceStage(
                    middleware: $middleware,
                    next: $this->stage ?? null
                );
                continue;
            }

            // Closure
            if ($middleware instanceof Closure) {
                $this->stage = new ClosureStage(
                    closure: $middleware,
                    next: $this->stage ?? null
                );
                continue;
            }

            // Deferred
            if (is_string($middleware)) {
                $this->stage = new DeferredStage(
                    type: $middleware,
                    container: $this->container,
                    next: $this->stage ?? null
                );
                continue;
            }

            // Array
            if (is_array($middleware)) {
                $this->add(...$middleware);
                continue;
            }
        }

        return $this;
    }

    /**
     * Count stages
     */
    public function count(): int
    {
        if (!isset($this->stage)) {
            return 0;
        }

        return 1 + $this->stage->count();
    }
}
