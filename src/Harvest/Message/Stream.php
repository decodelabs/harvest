<?php

/**
 * @package Harvest
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Harvest\Message;

use Closure;
use DecodeLabs\Deliverance\Channel\Stream as Channel;
use DecodeLabs\Exceptional;
use Generator;
use Psr\Http\Message\StreamInterface;
use Stringable;
use Throwable;

class Stream implements StreamInterface
{
    use StringableToStringTrait;

    /**
     * @var resource|null
     */
    public mixed $ioResource {
        get => $this->channel?->ioResource;
    }

    protected ?Channel $channel;

    public static function createTemp(
        string $mode = 'r+'
    ): StreamInterface {
        return new Stream('php://temp', $mode);
    }

    public static function createMemory(
        string $mode = 'r+'
    ): StreamInterface {
        return new Stream('php://memory', $mode);
    }

    /**
     * @param string|Stringable|Generator<string|Stringable>|Closure():(string|Stringable|Generator<string|Stringable>) $content
     */
    public static function fromString(
        string|Stringable|Generator|Closure $content,
        string $mode = 'r+'
    ): StreamInterface {
        $content = self::stringableToString($content);
        $output = self::createTemp($mode);
        $output->write($content);
        $output->rewind();

        return $output;
    }




    public function __construct(
        string|Channel $channel,
        string $mode = 'r'
    ) {
        if (is_string($channel)) {
            $channel = new Channel($channel, $mode);
        }

        $this->channel = $channel;
    }


    /**
     * @return resource|null
     */
    public function detach()
    {
        /** @var resource|null $output */
        $output = $this->channel?->ioResource;
        $this->channel = null;

        return $output;
    }


    public function getSize(): ?int
    {
        if (null === ($resource = $this->ioResource)) {
            return null;
        }

        $stats = fstat($resource);

        if ($stats === false) {
            return null;
        }

        return (int)$stats['size'];
    }


    public function tell(): int
    {
        if (null === ($resource = $this->ioResource)) {
            throw Exceptional::Io(
                message: 'Cannot tell stream position, resource has been detached'
            );
        }

        if (false === ($output = ftell($resource))) {
            throw Exceptional::Io(
                message: 'Unable to tell stream position'
            );
        }

        return $output;
    }


    public function eof(): bool
    {
        return $this->channel?->isAtEnd() ?? true;
    }


    public function isSeekable(): bool
    {
        if (null === ($resource = $this->ioResource)) {
            return false;
        }

        $meta = stream_get_meta_data($resource);
        return (bool)$meta['seekable'];
    }


    public function seek(
        int $offset,
        int $whence = SEEK_SET
    ): void {
        if (
            !$this->isSeekable() ||
            null === ($resource = $this->ioResource)
        ) {
            throw Exceptional::Io(
                message: 'Stream is not seekable'
            );
        }

        $result = fseek($resource, $offset, $whence);

        if ($result !== 0) {
            throw Exceptional::Io(
                message: 'Stream seeking failed'
            );
        }
    }


    public function rewind(): void
    {
        $this->seek(0);
    }


    public function isWritable(): bool
    {
        return $this->channel?->isWritable() ?? false;
    }


    public function write(
        string $string
    ): int {
        if (!$this->channel) {
            throw Exceptional::Io(
                message: 'Stream is not writable'
            );
        }

        return $this->channel->write($string);
    }


    public function isReadable(): bool
    {
        return $this->channel?->isReadable() ?? false;
    }


    public function read(
        int $length
    ): string {
        if (!$this->channel) {
            throw Exceptional::Io(
                message: 'Stream is not readable'
            );
        }

        /** @var int<1, max> $length */
        return (string)$this->channel->read($length);
    }


    public function getContents(): string
    {
        if (
            !$this->isReadable() ||
            null === ($resource = $this->ioResource)
        ) {
            throw Exceptional::Io(
                message: 'Stream is not readable'
            );
        }

        /** @var string|false $output */
        $output = stream_get_contents($resource);

        if ($output === false) {
            throw Exceptional::Io(
                message: 'Reading from stream failed'
            );
        }

        return $output;
    }


    /**
     * @return ($key is null ? array<string, mixed> : mixed)
     */
    public function getMetadata(
        ?string $key = null
    ): mixed {
        if (null === ($resource = $this->ioResource)) {
            throw Exceptional::Io(
                message: 'Cannot get stream metadata, resource has been detached'
            );
        }

        $output = stream_get_meta_data($resource);

        if ($key === null) {
            return $output;
        }

        if (!array_key_exists($key, $output)) {
            return null;
        }

        return $output[$key];
    }


    public function close(): void
    {
        $this->channel?->close();
        $this->channel = null;
    }



    public function __toString(): string
    {
        if (!$this->isReadable()) {
            return '';
        }

        try {
            if ($this->isSeekable()) {
                $this->rewind();
            }

            return $this->getContents();
        } catch (Throwable $e) {
            return '';
        }
    }
}
