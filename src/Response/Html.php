<?php

/**
 * @package Harvest
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Harvest\Response;

use Closure;
use DecodeLabs\Harvest\Message\Stream as MessageStream;
use DecodeLabs\Harvest\Message\StringableToStringTrait;
use Generator;
use Stringable;

class Html extends Stream
{
    use StringableToStringTrait;

    /**
     * Init with HTML stream and content type headers set
     *
     * @param string|Stringable|Generator<string|Stringable>|Closure(self=):(string|Stringable|Generator<string|Stringable>) $html
     * @param array<string,string|Stringable|array<string|Stringable>> $headers
     */
    public function __construct(
        string|Stringable|Generator|Closure $html,
        int $status = 200,
        array $headers = []
    ) {
        parent::__construct(
            MessageStream::fromString(
                content: self::stringableToString($html, $this),
                mode: 'wb+'
            ),
            $status,
            $this->injectDefaultHeaders([
                'content-type' => 'text/html; charset=utf-8',
                //'content-length' => strlen($text)
            ], $headers)
        );
    }
}
