<?php

/**
 * @package Harvest
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Harvest;

use Countable;
use Psr\Http\Server\MiddlewareInterface as Middleware;
use Psr\Http\Server\RequestHandlerInterface as Handler;

interface Stage extends
    Handler,
    Countable
{
    public function getMiddleware(): Middleware;
    public function getNext(): Stage;
}
