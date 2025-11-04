<?php

/**
 * Harvest
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Harvest\Response;

use Closure;
use DecodeLabs\Harvest\Message\Generator as MessageGenerator;
use DecodeLabs\Harvest\Message\StringableToStringTrait;
use Stringable;

class Generator extends Stream
{
    use StringableToStringTrait;

    /**
     * @param iterable<int|string,string>|Closure(MessageGenerator):(iterable<int|string, string>|null) $iterator
     * @param array<string,string|Stringable|array<string|Stringable>> $headers
     */
    public function __construct(
        iterable|Closure $iterator,
        int $status = 200,
        array $headers = []
    ) {
        parent::__construct(
            new MessageGenerator($iterator),
            $status,
            $this->injectDefaultHeaders([
                'Transfer-Encoding' => 'chunked',
                'X-Accel-Buffering' => 'no'
            ], $headers)
        );
    }
}
