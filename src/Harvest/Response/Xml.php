<?php

/**
 * Harvest
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Harvest\Response;

use Closure;
use DecodeLabs\Harvest\Message\Stream as MessageStream;
use DecodeLabs\Harvest\Message\StringableToStringTrait;
use Generator;
use Stringable;

class Xml extends Stream
{
    use StringableToStringTrait;

    /**
     * @param string|Stringable|Generator<string|Stringable>|Closure(self=):(string|Stringable|Generator<string|Stringable>) $xml
     * @param array<string, string|Stringable|array<string|Stringable>> $headers
     */
    public function __construct(
        string|Stringable|Generator|Closure $xml,
        int $status = 200,
        array $headers = []
    ) {
        parent::__construct(
            MessageStream::fromString(
                content: self::stringableToString($xml, $this),
                mode: 'wb+'
            ),
            $status,
            $this->injectDefaultHeaders([
                'content-type' => 'application/xml; charset=utf-8',
            ], $headers)
        );
    }
}
