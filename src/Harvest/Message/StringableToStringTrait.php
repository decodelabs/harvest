<?php

/**
 * Harvest
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Harvest\Message;

use Closure;
use Generator;
use Stringable;

trait StringableToStringTrait
{
    /**
     * @template TContext of object
     * @param string|Stringable|Generator<string|Stringable>|Closure(TContext=):(string|Stringable|Generator<string|Stringable>) $content
     * @param ?TContext $context
     */
    private static function stringableToString(
        string|Stringable|Generator|Closure $content,
        ?object $context = null
    ): string {
        if ($content instanceof Closure) {
            if ($context) {
                $content = $content($context);
            } else {
                $content = $content();
            }
        }

        if ($content instanceof Generator) {
            $content = implode('', iterator_to_array($content));
        }

        return (string)$content;
    }
}
