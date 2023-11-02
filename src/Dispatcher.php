<?php

/**
 * @package Harvest
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Harvest;

use ArrayIterator;
use Closure;
use Countable;
use DecodeLabs\Exceptional;
use DecodeLabs\Harvest\Stage\Closure as ClosureStage;
use DecodeLabs\Harvest\Stage\Deferred as DeferredStage;
use DecodeLabs\Harvest\Stage\Instance as InstanceStage;
use Fiber;
use Iterator;
use IteratorAggregate;
use Psr\Container\ContainerInterface as Container;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface as Middleware;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Throwable;

/**
 * @implements IteratorAggregate<Stage>
 */
class Dispatcher implements
    Handler,
    Countable,
    IteratorAggregate
{
    /**
     * @var array<int, Stage>
     */
    protected array $stages = [];
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
    public function handle(
        Request $request
    ): Response {
        if (empty($this->stages)) {
            throw Exceptional::Setup(
                'No middleware stack has been set'
            );
        }

        $this->sortStages();

        $stack = [];
        $pos = -1;
        $shift = 1;
        $response = null;

        while (
            $pos + $shift >= 0 &&
            isset($this->stages[$pos + $shift])
        ) {
            $pos += $shift;

            if ($shift > 0) {
                $stage = $this->stages[$pos];

                /** @var Fiber<Request,Response,Response,Request> $fiber */
                $fiber = new Fiber([$stage, 'run']);

                $stack[$pos] = [
                    'stage' => $stage,
                    'fiber' => $fiber
                ];

                try {
                    $request = $fiber->start($request);
                } catch (Throwable $e) {
                    $request = null;
                    array_pop($stack);
                    $pos--;

                    while (!empty($stack)) {
                        [
                            'stage' => $stage,
                            'fiber' => $fiber
                        ] = array_pop($stack);
                        $pos--;

                        try {
                            $request = $fiber->throw($e);
                        } catch (Throwable $e) {
                            continue;
                        }
                    }

                    if (!$request instanceof Request) {
                        throw $e;
                    }
                }

                if ($request) {
                    $shift = 1;

                    if (!isset($this->stages[$pos + $shift])) {
                        throw Exceptional::NotFound([
                            'message' => 'No middleware could handle the current request',
                            'http' => 404
                        ]);
                    }

                    continue;
                }
            }

            if (!isset($fiber)) {
                throw Exceptional::Runtime(
                    'Middleware stack has been corrupted'
                );
            }

            $response = $fiber->getReturn();
            array_pop($stack);
            $shift = -1;

            if (empty($stack)) {
                break;
            }

            [
                'stage' => $stage,
                'fiber' => $fiber
            ] = $stack[$pos + $shift];

            try {
                $fiber->resume($response);
            } catch (Throwable $e) {
                $fiber->throw($e);
            }
        }

        if (!$response instanceof Response) {
            throw Exceptional::Runtime(
                'Middleware stack has been corrupted'
            );
        }

        return $response;
    }



    /**
     * Add middleware to stack
     *
     * @param string|class-string<Middleware>|Middleware|Stage|Closure(Request, Handler):Response|array<string|class-string<Middleware>|Middleware|Closure(Request, Handler):Response> ...$middlewares
     *
     * @return $this
     */
    public function add(
        string|array|Closure|Stage|Middleware ...$middlewares
    ): static {
        foreach ($middlewares as $middleware) {
            // Array
            if (is_array($middleware)) {
                $this->add(...$middleware);
                continue;
            }

            $this->addPriority($middleware, null);
        }

        return $this;
    }

    /**
     * Add middleware with custom priority
     *
     * @param string|class-string<Middleware>|Middleware|Stage|Closure(Request, Handler):Response $middleware
     */
    public function addPriority(
        string|Closure|Stage|Middleware $middleware,
        ?int $priority
    ): Stage {
        // Stage
        if ($middleware instanceof Stage) {
            $stage = $middleware;
        }

        // Middleware
        elseif ($middleware instanceof Middleware) {
            $stage = new InstanceStage(
                middleware: $middleware,
            );
        }

        // Closure
        elseif ($middleware instanceof Closure) {
            $stage = new ClosureStage(
                closure: $middleware,
            );
        }

        // Deferred
        elseif (is_string($middleware)) {
            $stage = new DeferredStage(
                type: $middleware,
                container: $this->container,
            );
        }

        // Unhandled
        else {
            throw Exceptional::Runtime(
                'Middleware could not be resolved'
            );
        }

        if ($priority !== null) {
            $stage->setPriority($priority);
        }

        return $this->stages[] = $stage;
    }



    /**
     * Sort stages according to priority
     */
    protected function sortStages(): void
    {
        usort($this->stages, function ($a, $b) {
            return $a->getPriority() <=> $b->getPriority();
        });
    }


    /**
     * Count stages
     */
    public function count(): int
    {
        return count($this->stages);
    }

    /**
     * Get iterator
     *
     * @return Iterator<Stage>
     */
    public function getIterator(): Iterator
    {
        $this->sortStages();
        return new ArrayIterator($this->stages);
    }
}
