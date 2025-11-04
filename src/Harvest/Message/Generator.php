<?php

/**
 * Harvest
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Harvest\Message;

use ArrayIterator;
use Closure;
use DecodeLabs\Deliverance\DataReceiver;
use DecodeLabs\Deliverance\DataReceiverTrait;
use DecodeLabs\Exceptional;
use Fiber;
use Iterator as NativeIterator;
use IteratorAggregate;
use Psr\Http\Message\StreamInterface;
use Throwable;

class Generator implements
    StreamInterface,
    DataReceiver
{
    use DataReceiverTrait;

    /**
     * @var NativeIterator<int|string, string>|null
     */
    protected ?NativeIterator $iterator;

    /**
     * @var Fiber<self,null,iterable<int|string, string>|null,string>|null
     */
    protected ?Fiber $fiber = null;

    protected string $buffer = '';
    protected int $position = 0;
    protected bool $eof = false;
    protected bool $complete = false;
    protected bool $started = false;
    protected bool $bufferAll = true;


    /**
     * @param iterable<int|string, string>|Closure(static):(iterable<int|string, string>|null) $iterator
     */
    public function __construct(
        iterable|Closure $iterator,
        bool $buffer = true
    ) {
        /** @phpstan-ignore-next-line */
        $this->iterator = (function () use ($iterator) {
            if ($iterator instanceof Closure) {
                $this->fiber = new Fiber($iterator);

                while (!$this->fiber->isTerminated()) {
                    yield $this->fiber->isSuspended()
                        ? $this->fiber->resume()
                        : $this->fiber->start($this);
                }

                $iterator = $this->fiber->getReturn();
                $this->fiber = null;

                if (!is_iterable($iterator)) {
                    return;
                }
            }

            if (is_array($iterator)) {
                $iterator = new ArrayIterator($iterator);
            }

            if ($iterator instanceof IteratorAggregate) {
                $iterator = $iterator->getIterator();
            }

            yield from $iterator;
        })();

        $this->bufferAll = $buffer;
    }

    public function getSize(): ?int
    {
        return null;
    }

    public function tell(): int
    {
        return $this->position;
    }

    public function eof(): bool
    {
        return $this->eof;
    }

    public function isSeekable(): bool
    {
        return false;
    }

    public function seek(
        int $offset,
        int $whence = SEEK_SET
    ): void {
        throw Exceptional::Runtime(
            message: 'Iterators cannot seek'
        );
    }

    public function rewind(): void
    {
        throw Exceptional::Runtime(
            message: 'Iterators cannot seek'
        );
    }

    public function isWritable(): bool
    {
        return true;
    }

    public function write(
        ?string $string,
        ?int $length = null
    ): int {
        if ($string === null) {
            return 0;
        }

        if ($length !== null) {
            $string = substr($string, 0, $length);
        }

        if ($this->fiber) {
            Fiber::suspend($string);
            return strlen($string);
        }

        $this->buffer .= $string;
        return strlen($string);
    }


    public function isReadable(): bool
    {
        return !$this->eof;
    }

    /**
     * If $bufferAll, the iterator is iterated until the buffer can send $length
     * otherwise it's iterated once on each call
     */
    public function read(
        int $length
    ): string {
        if ($this->iterator === null) {
            throw Exceptional::Runtime(
                message: 'Cannot read from stream, resource has been detached'
            );
        }

        if ($this->eof) {
            throw Exceptional::Runtime(
                message: 'Cannot read from stream, iterator has completed'
            );
        }


        if (
            !$this->complete &&
            strlen($this->buffer) < $length
        ) {
            do {
                if ($this->started) {
                    $this->iterator->next();
                }

                $this->buffer .= (string)$this->iterator->current();
                $this->started = true;

                if (!$this->iterator->valid()) {
                    $this->complete = true;
                    break;
                }
            } while (
                $this->bufferAll &&
                strlen($this->buffer) < $length
            );
        }

        $output = substr($this->buffer, 0, $length);

        if ($this->buffer !== '') {
            $this->buffer = substr($this->buffer, $outLength = strlen($output));
            $this->position += $outLength;
        }

        if (
            $this->complete &&
            $this->buffer === ''
        ) {
            $this->eof = true;
        }

        return $output;
    }


    public function getContents(): string
    {
        if ($this->iterator === null) {
            throw Exceptional::Runtime(
                message: 'Cannot read from stream, resource has been detached'
            );
        }

        if ($this->eof) {
            throw Exceptional::Runtime(
                message: 'Cannot read from stream, iterator has completed'
            );
        }

        $output = '';

        /** @phpstan-ignore-next-line */
        while (!$this->eof) {
            $output .= $this->read(4096);
        }

        /** @phpstan-ignore-next-line */
        $this->eof = true;

        return $output;
    }


    /**
     * @return ($key is null ? array<string, mixed> : mixed)
     */
    public function getMetadata(
        ?string $key = null
    ): mixed {
        $metadata = [
            'eof' => $this->eof(),
            'stream_type' => 'iterator',
            'seekable' => false
        ];

        if (null === $key) {
            return $metadata;
        }

        if (!array_key_exists($key, $metadata)) {
            return null;
        }

        return $metadata[$key];
    }


    public function close(): void
    {
        if (!$this->iterator) {
            return;
        }

        $this->detach();
    }

    /**
     * @return NativeIterator<int|string, string>|null
     */
    public function detach(): ?NativeIterator
    {
        $output = $this->iterator;
        $this->iterator = null;

        return $output;
    }

    public function __toString(): string
    {
        if (!$this->isReadable()) {
            return '';
        }

        try {
            return $this->getContents();
        } catch (Throwable $e) {
            return '';
        }
    }
}
