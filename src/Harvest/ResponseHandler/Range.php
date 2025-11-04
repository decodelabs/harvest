<?php

/**
 * Harvest
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Harvest\ResponseHandler;

use Stringable;

class Range implements Stringable
{
    public int $start;
    public int $end;
    public ?int $size = null;

    public int $length {
        get => $this->end - $this->start + 1;
    }

    public function __construct(
        int $start,
        int $end,
        ?int $size
    ) {
        $this->start = $start;
        $this->end = $end;
        $this->size = $size;
    }

    public function isValid(): bool
    {
        return
            $this->start >= 0 &&
            $this->end >= 0 &&
            $this->start <= $this->end &&
            ($this->size === null || $this->start <= $this->size) &&
            ($this->size === null || $this->end <= $this->size);
    }

    public function __toString(): string
    {
        return 'bytes=' . $this->start . '-' . $this->end . '/' . ($this->size ?? '*');
    }
}
