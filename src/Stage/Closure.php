<?php

/**
 * @package Harvest
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Harvest\Stage;

use Closure as Callback;
use DecodeLabs\Harvest\Stage;
use DecodeLabs\Harvest\StageTrait;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface as Middleware;
use Psr\Http\Server\RequestHandlerInterface as Handler;

class Closure implements Stage
{
    use StageTrait;

    protected Callback $closure;

    /**
     * Init with closure
     */
    public function __construct(
        Callback $closure,
        ?Stage $next = null
    ) {
        $this->closure = $closure;
        $this->next = $next;
    }

    /**
     * Get middleware
     */
    public function getMiddleware(): Middleware
    {
        return new class($this->closure) implements Middleware {
            public function __construct(
                protected Callback $closure
            ) {
            }

            public function process(
                Request $request,
                Handler $handler
            ): Response {
                return ($this->closure)($request, $handler);
            }
        };
    }
}
