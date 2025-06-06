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
use DecodeLabs\Nuance\Dumpable;
use DecodeLabs\Nuance\Entity\NativeObject as NuanceEntity;
use Iterator;
use IteratorAggregate;
use Psr\Http\Message\ResponseInterface as PsrResponse;
use Psr\Http\Message\ServerRequestInterface as PsrRequest;
use Psr\Http\Server\MiddlewareInterface as PsrMiddleware;
use Psr\Http\Server\RequestHandlerInterface as PsrHandler;
use ReflectionClass;

/**
 * @implements IteratorAggregate<string,Stage>
 */
class Profile implements
    Countable,
    IteratorAggregate,
    Dumpable
{
    /**
     * @var array<string,Stage>
     */
    private array $stages = [];

    private bool $sorted = false;


    /**
     * @param string|class-string<PsrMiddleware>|PsrMiddleware|Stage|Closure(PsrRequest, PsrHandler):PsrResponse|array<mixed> ...$middlewares
     */
    public function __construct(
        string|array|Closure|Stage|PsrMiddleware ...$middlewares
    ) {
        foreach($middlewares as $key => $middleware) {
            // Array
            if (is_array($middleware)) {
                if (is_string($key)) {
                    /** @var array<string,mixed> $middleware  */
                    $this->add(new DeferredStage(
                        type: $key,
                        parameters: $middleware
                    ));
                } else {
                    /** @var array<int,string|class-string<PsrMiddleware>|PsrMiddleware|Stage|Closure(PsrRequest, PsrHandler):PsrResponse>|array<string,array<mixed>> $middleware */
                    $this->__construct(...$middleware);
                }

                continue;
            }

            $this->add($middleware);
        }
    }

    public function isEmpty(): bool
    {
        return empty($this->stages);
    }

    public function count(): int
    {
        return count($this->stages);
    }

    /**
     * @return array<Stage>
     */
    public function toList(): array
    {
        $this->sort();
        return array_values($this->stages);
    }

    public function getIterator(): Iterator
    {
        $this->sort();
        return new ArrayIterator($this->stages);
    }


    /**
     * @return $this
     */
    public function add(
        string|Closure|Stage|PsrMiddleware $middleware,
        ?MiddlewareGroup $group = null,
        ?int $priority = null,
        mixed ...$parameters
    ): static {
        // Stage
        if ($middleware instanceof Stage) {
            $stage = $middleware;
        }

        // Middleware
        elseif ($middleware instanceof PsrMiddleware) {
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
            /** @var array<string,mixed> $parameters */
            $stage = new DeferredStage(
                type: $middleware,
                parameters: $parameters,
            );
        }

        // Unhandled
        else {
            throw Exceptional::Runtime(
                message: 'Middleware could not be resolved'
            );
        }

        if($group !== null) {
            $stage->group = $group;
        }

        if ($priority !== null) {
            $stage->priority = $priority;
        }

        $this->sorted = false;
        $this->stages[$stage->name] = $stage;

        return $this;
    }

    public function has(
        string|Closure|Stage|PsrMiddleware $middleware
    ): bool {
        $string = $this->getKey($middleware);
        return isset($this->stages[$string]);
    }

    public function get(
        string|Closure|Stage|PsrMiddleware $middleware
    ): ?Stage {
        $string = $this->getKey($middleware);
        return $this->stages[$string] ?? null;
    }

    public function remove(
        string|Closure|Stage|PsrMiddleware $middleware
    ): void {
        $string = $this->getKey($middleware);
        unset($this->stages[$string]);
        $this->sorted = false;
    }

    private function getKey(
        string|Closure|Stage|PsrMiddleware $middleware
    ): string {
        // Stage
        if ($middleware instanceof Stage) {
            return $middleware->name;
        }

        // Middleware
        elseif ($middleware instanceof PsrMiddleware) {
            return new ReflectionClass($middleware)->getShortName();
        }

        // Closure
        elseif ($middleware instanceof Closure) {
            return 'closure:'.spl_object_id($middleware);
        }

        // Deferred
        else {
            return $middleware;
        }
    }

    protected function sort(): void
    {
        if($this->sorted) {
            return;
        }

        uasort($this->stages, function ($a, $b) {
            return
                [$a->group->getKey(), $a->priority] <=>
                [$b->group->getKey(), $b->priority];
        });

        $this->sorted = true;
    }

    public function toNuanceEntity(): NuanceEntity
    {
        $entity = new NuanceEntity($this);
        $this->sort();
        $entity->values = $this->stages;
        return $entity;
    }
}
