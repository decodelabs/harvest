<?php

/**
 * @package Harvest
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Harvest\Cookie;

use DecodeLabs\Enumerable\Unit\Named;
use DecodeLabs\Enumerable\Unit\NamedTrait;

enum SameSite implements Named
{
    use NamedTrait;

    case None;
    case Lax;
    case Strict;
}
