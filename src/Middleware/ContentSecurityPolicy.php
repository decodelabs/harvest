<?php

/**
 * @package Harvest
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Harvest\Middleware;

use DecodeLabs\Archetype;
use DecodeLabs\Pandora\Container as PandoraContainer;
use DecodeLabs\Sanctum\Definition as SanctumDefinition;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface as Middleware;
use Psr\Http\Server\RequestHandlerInterface as Handler;

class ContentSecurityPolicy implements Middleware
{
    protected ?ContainerInterface $container = null;

    public function __construct(
        ?ContainerInterface $container = null
    ) {
        $this->container = $container;
    }

    /**
     * Process middleware
     */
    public function process(
        Request $request,
        Handler $next
    ): Response {
        $response = $next->handle($request);

        if (!class_exists(SanctumDefinition::class)) {
            return $response;
        }

        if ($csp = $this->loadCsp($response)) {
            $response = $csp->applyHeaders($response);
        }

        return $response;
    }

    /**
     * Attempt to load policy
     */
    protected function loadCsp(
        Response $response
    ): ?SanctumDefinition {
        if (
            $this->container &&
            $this->container->has(SanctumDefinition::class)
        ) {
            if ($this->container instanceof PandoraContainer) {
                return $this->container->tryGetWith(
                    SanctumDefinition::class,
                    [
                        'response' => $response
                    ]
                );
            }

            $output = $this->container->get(SanctumDefinition::class);

            if ($output instanceof SanctumDefinition) {
                return $output;
            }
        }

        if (!$class = Archetype::tryResolve(SanctumDefinition::class)) {
            return null;
        }

        if ($this->container instanceof PandoraContainer) {
            $this->container->bindShared(
                SanctumDefinition::class,
                $class
            );

            return $this->container->get(SanctumDefinition::class);
        }

        return new $class();
    }
}
