<?php

/**
 * @package Harvest
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Harvest\Response;

use DecodeLabs\Harvest\Message\Stream as MessageStream;
use Stringable;

class Text extends Stream
{
    /**
     * Init with text stream and content type headers set
     *
     * @param array<string, string|Stringable|array<string|Stringable>> $headers
     */
    public function __construct(
        string $text,
        int $status = 200,
        array $headers = []
    ) {
        parent::__construct(
            MessageStream::fromString($text, 'wb+'),
            $status,
            $this->injectDefaultHeaders([
                'content-type' => 'text/plain; charset=utf-8',
                //'content-length' => strlen($text)
            ], $headers)
        );
    }
}
