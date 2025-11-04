<?php

/**
 * Harvest
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Harvest;

use Psr\Http\Message\ResponseInterface as PsrResponse;
use Psr\Http\Message\ServerRequestInterface as PsrRequest;
use Psr\Http\Server\MiddlewareInterface as PsrMiddleware;
use Psr\Http\Server\RequestHandlerInterface as PsrHandler;

interface Stage extends PsrHandler
{
    public string $name { get; }

    public int $priority { get; set; }
    public MiddlewareGroup $group { get; set; }

    public int $defaultPriority { get; }
    public MiddlewareGroup $defaultGroup { get; }

    public ?PsrMiddleware $middleware { get; }

    public function run(
        PsrRequest $handler
    ): PsrResponse;
}
