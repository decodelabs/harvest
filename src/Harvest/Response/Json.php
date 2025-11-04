<?php

/**
 * Harvest
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Harvest\Response;

use Closure;
use DecodeLabs\Exceptional;
use DecodeLabs\Harvest\Message\Stream as MessageStream;
use Stringable;

class Json extends Stream
{
    /**
     * @param array<string,string|Stringable|array<string|Stringable>> $headers
     */
    public function __construct(
        mixed $data,
        int $status = 200,
        array $headers = []
    ) {
        if ($data instanceof Closure) {
            $data = $data($this);
        }

        $json = json_encode(
            $data,
            JSON_UNESCAPED_SLASHES |
                JSON_PRETTY_PRINT
        );

        if ($json === false) {
            throw Exceptional::UnexpectedValue(
                message: 'Unable to encode json for stream',
                data: $data
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
