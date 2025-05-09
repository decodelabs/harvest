<?php

/**
 * @package Harvest
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Harvest\Stage;

use DecodeLabs\Archetype;
use DecodeLabs\Exceptional;
use DecodeLabs\Glitch\Dumpable;;
use DecodeLabs\Harvest\Stage;
use DecodeLabs\Harvest\StageTrait;
use DecodeLabs\Slingshot;
use Psr\Container\ContainerInterface as Container;
use Psr\Http\Server\MiddlewareInterface as PsrMiddleware;

class Deferred implements
    Stage,
    Dumpable
{
    use StageTrait;

    public string $name {
        get => $this->type;
    }

    /**
     * @var string|class-string<PsrMiddleware>
     */
    protected string $type;

    /**
     * @var array<string, mixed>|null
     */
    protected ?array $parameters = null;

    protected bool $optional = false;


    public ?PsrMiddleware $middleware {
        get {
            if (isset($this->middleware)) {
                return $this->middleware;
            }

            $class = Archetype::tryResolve(PsrMiddleware::class, $this->type);

            if ($class === null) {
                if ($this->optional) {
                    return null;
                } else {
                    throw Exceptional::Runtime(
                        message: 'Middleware ' . $this->type . ' could not be resolved'
                    );
                }
            }

            $slingshot = new Slingshot(
                parameters: $this->parameters ?? []
            );

            return $this->middleware = $slingshot->newInstance($class);
        }
    }



    /**
     * Init with middleware class name
     *
     * @param string|class-string<PsrMiddleware> $type
     * @param array<string,mixed>|null $parameters
     */
    public function __construct(
        string $type,
        ?array $parameters = null
    ) {
        $this->optional = str_starts_with($type, '?');
        $type = ltrim($type, '?');
        [$type, $priority] = explode(':', $type, 2) + [$type, null];

        $this->type = (string)$type;
        $this->parameters = $parameters;

        if (
            $priority !== null &&
            is_numeric($priority)
        ) {
            $this->priority = (int)$priority;
        }
    }



    public function glitchDump(): iterable
    {
        yield 'className' => $this->group->name;

        yield 'properties' => [
            'name' => $this->name,
            '*parameters' => $this->parameters,
            '*optional' => $this->optional
        ];

        yield 'meta' => [
            'priority' => $this->priority
        ];
    }
}
