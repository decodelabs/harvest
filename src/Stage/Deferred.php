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
use DecodeLabs\Pandora\Container as PandoraContainer;

use Psr\Container\ContainerInterface as Container;
use Psr\Http\Server\MiddlewareInterface as Middleware;

use ReflectionClass;
use ReflectionParameter;

class Deferred implements Stage
{
    use StageTrait;

    /**
     * @var string|class-string<Middleware>
     */
    protected string $type;

    protected ?Container $container = null;

    /**
     * Init with middleware class name
     *
     * @param string|class-string<Middleware> $type
     */
    public function __construct(
        string $type,
        ?Container $container = null
    ) {
        [$type, $priority] = explode(':', $type, 2) + [$type, null];

        $this->type = (string)$type;
        $this->container = $container;

        if (
            $priority !== null &&
            is_numeric($priority)
        ) {
            $this->priority = (int)$priority;
        }
    }

    /**
     * Get middleware
     */
    public function getMiddleware(): Middleware
    {
        $class = Archetype::resolve(Middleware::class, $this->type);

        if (
            $this->container &&
            (
                $this->container->has($class) ||
                $this->container instanceof PandoraContainer
            )
        ) {
            $output = $this->container->get($class);

            if (!$output instanceof Middleware) {
                throw Exceptional::UnexpectedValue(
                    'Middleware from container as ' . $class . ' is not an instance of ' . Middleware::class
                );
            }

            return $output;
        }

        $params = (new ReflectionClass($class))
            ->getConstructor()
            ?->getParameters() ?? [];

        /**
         * @var ReflectionParameter $param
         */
        foreach ($params as $param) {
            if ($param->isDefaultValueAvailable()) {
                continue;
            }

            throw Exceptional::Setup(
                'Unable to instantiate ' . $class . ' without a container'
            );
        }

        return new $class();
    }
}
