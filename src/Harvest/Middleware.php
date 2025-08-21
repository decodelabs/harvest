<?php

/**
 * @package Harvest
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Harvest;

use Psr\Http\Server\MiddlewareInterface;

interface Middleware extends MiddlewareInterface
{
    public MiddlewareGroup $group { get; }
    public int $priority { get; }
}
