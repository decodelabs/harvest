<?php

/**
 * @package Harvest
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Harvest;

interface ResponseProxy
{
    public function toHttpResponse(): Response;
}
