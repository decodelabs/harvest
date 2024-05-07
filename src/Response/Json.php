<?php

/**
 * @package Harvest
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Harvest\Response;

use DecodeLabs\Exceptional;
use DecodeLabs\Harvest\Message\Stream as MessageStream;
use Stringable;

class Json extends Stream
{
    /**
     * Init with JSON stream and content type headers set
     *
     * @param array<string, string|Stringable|array<string|Stringable>> $headers
     */
    public function __construct(
        mixed $data,
        int $status = 200,
        array $headers = []
    ) {
        $json = json_encode(
            $data,
            JSON_UNESCAPED_SLASHES |
            JSON_PRETTY_PRINT
        );

        if ($json === false) {
            throw Exceptional::UnexpectedValue(
                'Unable to encode json for stream',
                null,
                $data
            );
        }

        parent::__construct(
            MessageStream::fromString($json, 'wb+'),
            $status,
            $this->injectDefaultHeaders([
                'content-type' => 'application/json'
            ], $headers)
        );
    }
}
