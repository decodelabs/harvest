<?php

/**
 * @package Harvest
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Harvest\Stage;

use DecodeLabs\Archetype;
use DecodeLabs\Exceptional;
use DecodeLabs\Harvest\Stage;
use DecodeLabs\Harvest\StageTrait;
use DecodeLabs\Slingshot;
use Psr\Container\ContainerInterface as Container;
use Psr\Http\Server\MiddlewareInterface as Middleware;

class Deferred implements Stage
{
    use StageTrait;

    /**
     * @var string|class-string<Middleware>
     */
    protected string $type;

    protected ?Container $container = null;

    /**
     * @var array<string, mixed>|null
     */
    protected ?array $parameters = null;

    protected bool $optional = false;


    public ?Middleware $middleware {
        get {
            if (isset($this->middleware)) {
                return $this->middleware;
            }

            $class = Archetype::tryResolve(Middleware::class, $this->type);

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
                container: $this->container,
                parameters: $this->parameters ?? []
            );

            return $this->middleware = $slingshot->newInstance($class);
        }
    }



    /**
     * Init with middleware class name
     *
     * @param string|class-string<Middleware> $type
     * @param array<string, mixed>|null $parameters
     */
    public function __construct(
        string $type,
        ?Container $container = null,
        ?array $parameters = null
    ) {
        $this->optional = str_starts_with($type, '?');
        $type = ltrim($type, '?');
        [$type, $priority] = explode(':', $type, 2) + [$type, null];

        $this->type = (string)$type;
        $this->container = $container;
        $this->parameters = $parameters;

        if (
            $priority !== null &&
            is_numeric($priority)
        ) {
            $this->priority = (int)$priority;
        }
    }
}
