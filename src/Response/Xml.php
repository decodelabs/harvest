<?php

/**
 * @package Harvest
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Harvest\Response;

use DecodeLabs\Harvest\Message\Stream as MessageStream;
use Stringable;

class Xml extends Stream
{
    /**
     * Init with HTML stream and content type headers set
     *
     * @param array<string, string|Stringable|array<string|Stringable>> $headers
     */
    public function __construct(
        string $xml,
        int $status = 200,
        array $headers = []
    ) {
        parent::__construct(
            MessageStream::fromString($xml, 'wb+'),
            $status,
            $this->injectDefaultHeaders([
                'content-type' => 'application/xml; charset=utf-8',
            ], $headers)
        );
    }
}
