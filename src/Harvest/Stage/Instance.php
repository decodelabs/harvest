<?php

/**
 * Harvest
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Harvest\Stage;

use DecodeLabs\Coercion;
use DecodeLabs\Harvest\Stage;
use DecodeLabs\Harvest\StageTrait;
use Psr\Http\Server\MiddlewareInterface as PsrMiddleware;
use ReflectionClass;

class Instance implements Stage
{
    use StageTrait;

    public string $name {
        get => new ReflectionClass(
            Coercion::asType($this->middleware, PsrMiddleware::class)
        )->getShortName();
    }

    public protected(set) ?PsrMiddleware $middleware;

    public function __construct(
        PsrMiddleware $middleware
    ) {
        $this->middleware = $middleware;
    }
}
