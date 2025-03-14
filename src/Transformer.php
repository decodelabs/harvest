<?php

/**
 * @package Harvest
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Harvest;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * @template T
 */
interface Transformer
{
    /**
     * @param T $response
     */
    public function transform(
        Request $request,
        mixed $response
    ): Response;
}
