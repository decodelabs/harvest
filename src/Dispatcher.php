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

        $stages = $this->stages;
        $stages[] = new ClosureStage(function (
            Request $request,
            Handler $handler
        ) {
            throw Exceptional::NotFound(
                message: 'No middleware could handle the current request',
                http: 404
            );
        });

        $stack = [];
        $pos = -1;
        $shift = 1;
        $response = null;

        while (
            $pos + $shift >= 0 &&
            isset($stages[$pos + $shift])
        ) {
            $e = null;
            $pos += $shift;

            if ($shift > 0) {
                $stage = $stages[$pos];

                $stack[$pos] = [
                    'stage' => $stage,
                    'fiber' => $fiber = new Fiber([$stage, 'run'])
                ];
            } else {
                [
                    'stage' => $stage,
                    'fiber' => $fiber
                ] = $stack[$pos];
            }

            /**
             * @var Fiber<Request,Response,Response,Request> $fiber
             */

            try {
                if ($fiber->isSuspended()) {
                    $request = $fiber->resume($response);
                } else {
                    $request = $fiber->start($request);
                }
            } catch (Throwable $e) {
                $request = null;
                array_pop($stack);
                $pos--;

                if (empty($stack)) {
                    throw $e;
                }

                while (!empty($stack)) {
                    [
                        'stage' => $stage,
                        'fiber' => $fiber
                    ] = $stack[$pos];


                    try {
                        $request = $fiber->throw($e);
                        break;
                    } catch (Throwable $e) {
                        if ($pos === 0) {
                            throw $e;
                        }
                    }

                    array_pop($stack);
                    $pos--;
                }
            }

            if ($request) {
                $shift = 1;

                if (!isset($stages[$pos + $shift])) {
                    throw Exceptional::NotFound(
                        message: 'No middleware could handle the current request',
                        http: 404
                    );
                }

                continue;
            }


            if (!$fiber->isTerminated()) {
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
     * @param string|class-string<Middleware>|Middleware|Stage|Closure(Request, Handler):Response|array<mixed> ...$middlewares
     *
     * @return $this
     */
    public function add(
        string|array|Closure|Stage|Middleware ...$middlewares
    ): static {
        foreach ($middlewares as $key => $middleware) {
            // Array
            if (is_array($middleware)) {
                if (is_string($key)) {
                    $this->add(new DeferredStage(
                        type: $key,
                        container: $this->container,
                        /** @var array<string, mixed> $middleware  */
                        parameters: $middleware
                    ));
                } else {
                    /** @var array<int,string|class-string<Middleware>|Middleware|Stage|Closure(Request, Handler):Response>|array<string,array<mixed>> $middleware */
                    $this->add(...$middleware);
                }

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
