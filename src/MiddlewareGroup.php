<?php

/**
 * @package Harvest
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Harvest;

use DecodeLabs\Enumerable\Unit\Indexed;
use DecodeLabs\Enumerable\Unit\IndexedTrait;

enum MiddlewareGroup implements Indexed
{
    use IndexedTrait;

    case ErrorHandler;
    case Inbound;
    case Outbound;
    case Generic;
    case Generator;
}
