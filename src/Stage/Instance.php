<?php

/**
 * @package Harvest
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Harvest\Stage;

use DecodeLabs\Harvest\Stage;
use DecodeLabs\Harvest\StageTrait;

use Psr\Http\Server\MiddlewareInterface as Middleware;

class Instance implements Stage
{
    use StageTrait;

    protected(set) Middleware $middleware;

    public function __construct(
        Middleware $middleware
    ) {
        $this->middleware = $middleware;
    }
}
