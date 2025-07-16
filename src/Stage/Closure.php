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

use Psr\Http\Message\ResponseInterface as PsrResponse;
use Psr\Http\Message\ServerRequestInterface as PsrRequest;
use Psr\Http\Server\MiddlewareInterface as PsrMiddleware;
use Psr\Http\Server\RequestHandlerInterface as PsrHandler;

class Closure implements Stage
{
    use StageTrait;

    public string $name {
        get => 'closure:' . spl_object_id($this->closure);
    }

    /**
     * @var Callback(PsrRequest,PsrHandler):PsrResponse
     */
    protected Callback $closure;

    public ?PsrMiddleware $middleware {
        get => $this->middleware ??= new class($this->closure) implements PsrMiddleware {
            public function __construct(
                /**
                 * @var Callback(PsrRequest,PsrHandler):PsrResponse
                 */
                protected Callback $closure
            ) {
            }

            public function process(
                PsrRequest $request,
                PsrHandler $handler
            ): PsrResponse {
                return ($this->closure)($request, $handler);
            }
        };
    }

    /**
     * Init with closure
     *
     * @param Callback(PsrRequest,PsrHandler):PsrResponse $closure
     */
    public function __construct(
        Callback $closure
    ) {
        $this->closure = $closure;
    }
}
