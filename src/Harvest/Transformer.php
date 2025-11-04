<?php

/**
 * Harvest
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Harvest;

use Psr\Http\Message\ResponseInterface as PsrResponse;
use Psr\Http\Message\ServerRequestInterface as PsrRequest;

/**
 * @template T
 */
interface Transformer
{
    /**
     * @param T $response
     */
    public function transform(
        PsrRequest $request,
        mixed $response
    ): PsrResponse;
}
