<?php

/**
 * @package Harvest
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Harvest;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface as Middleware;
use Psr\Http\Server\RequestHandlerInterface as Handler;

interface Stage extends Handler
{
    public int $priority { get; set; }
    public int $defaultPriority { get; }
    public Middleware $middleware { get; }

    public function run(
        Request $handler
    ): Response;
}
