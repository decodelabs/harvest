<?php

/**
 * @package Harvest
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Harvest;

use DecodeLabs\Deliverance\Channel\Stream as Channel;
use DecodeLabs\Exceptional;
use DecodeLabs\Harvest\Message\Stream;
use Psr\Http\Message\StreamInterface;
use Stringable;

trait MessageTrait
{
    protected string $protocol = '1.1';

    /**
     * @var array<string, array<string>>
     */
    protected array $headers = [];

    /**
     * @var array<string, string>
     */
    protected array $headerAliases = [];

    protected StreamInterface $body;

    /**
     * @param array<string, string|Stringable|array<string|Stringable>> $headers
     */
    public function __construct(
        string|Channel|StreamInterface|null $body,
        array $headers,
        string $protocol = '1.1'
    ) {
        $this->protocol = static::normalizeProtocolVersion($protocol);
        $this->body = static::normalizeBody($body);

        $this->headers = $this->headerAliases = [];

        foreach ($headers as $name => $value) {
            if (!$this->isHeaderNameValid($name)) {
                throw Exceptional::InvalidArgument(
                    'Invalid header name: ' . $name
                );
            }

            $this->headers[$name] = $this->normalizeHeader($value);
            $this->headerAliases[strtolower($name)] = $name;
        }
    }


    /**
     * Return new version with version set
     */
    public function withProtocolVersion(
        ?string $version
    ): static {
        $output = clone $this;
        $output->protocol = $this->normalizeProtocolVersion($version);

        return $output;
    }


    /**
     * Get HTTP version 1.0 or 1.1
     */
    public function getProtocolVersion(): string
    {
        return $this->protocol;
    }


    /**
     * Prepare protocol version
     */
    public static function normalizeProtocolVersion(
        ?string $version
    ): string {
        if ($version === null) {
            $version = '1.1';
        }

        if (!preg_match('#^(1\.[01]|2)$#', (string)$version)) {
            throw Exceptional::InvalidArgument(
                'Invalid HTTP protocol version: ' . $version,
                null,
                $version
            );
        }

        return $version;
    }




    /**
     * Return new instance with header set
     *
     * @param string|array<string> $value
     */
    public function withHeader(
        string $name,
        $value
    ): static {
        if (!$this->isHeaderNameValid($name)) {
            throw Exceptional::InvalidArgument(
                'Invalid header name: ' . $name
            );
        }

        $output = clone $this;
        $lowerName = strtolower($name);

        if (isset($this->headerAliases[$lowerName])) {
            unset($output->headers[$this->headerAliases[$lowerName]]);
        }

        $output->headers[$name] = $this->normalizeHeader($value);
        $output->headerAliases[$lowerName] = $name;

        return $output;
    }

    /**
     * Merge $value with current value stack
     *
     * @param string|array<string> $value
     */
    public function withAddedHeader(
        string $name,
        $value
    ): static {
        if (!$this->isHeaderNameValid($name)) {
            throw Exceptional::InvalidArgument(
                'Invalid header name: ' . $name
            );
        }

        $output = clone $this;

        $output->headers[$name] = array_merge(
            $output->headers[$name] ?? [],
            $this->normalizeHeader($value)
        );

        $output->headerAliases[strtolower($name)] = $name;

        return $output;
    }

    /**
     * Get raw header array
     *
     * @return array<string, array<string>>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Has this header been set?
     */
    public function hasHeader(
        string $name
    ): bool {
        return isset($this->headerAliases[strtolower($name)]);
    }

    /**
     * Get header value stack
     */
    public function getHeader(
        string $name
    ): array {
        if (!$this->hasHeader($name)) {
            return [];
        }

        $name = $this->headerAliases[strtolower($name)];
        return $this->headers[$name];
    }

    /**
     * Get comma separate list of headers by name
     */
    public function getHeaderLine(
        string $name
    ): string {
        return implode(',', $this->getHeader($name));
    }

    /**
     * Remove header
     */
    public function withoutHeader(
        string $name
    ): static {
        $output = clone $this;
        unset($output->headers[$name], $output->headerAliases[strtolower($name)]);

        return $output;
    }

    /**
     * Prepare a header value
     *
     * @param string|Stringable|array<string|Stringable> $value
     * @return array<string>
     */
    public static function normalizeHeader(
        string|Stringable|array $value
    ): array {
        if (!is_array($value)) {
            $value = [$value];
        }

        return array_map(function ($value) {
            $value = (string)$value;

            if (!static::isHeaderValueValid($value)) {
                throw Exceptional::InvalidArgument(
                    'Invalid header value',
                    null,
                    $value
                );
            }

            return $value;
        }, $value);
    }


    /**
     * Is a header key valid?
     */
    public static function isHeaderNameValid(
        string $key
    ): bool {
        return (bool)preg_match('/^[a-zA-Z0-9\'`#$%&*+.^_|~!-]+$/', $key);
    }

    /**
     * Is a header valid?
     */
    public static function isHeaderValueValid(
        mixed $value
    ): bool {
        if (
            !is_scalar($value) &&
            !$value instanceof Stringable
        ) {
            return false;
        }

        $value = (string)$value;

        if (preg_match("#(?:(?:(?<!\r)\n)|(?:\r(?!\n))|(?:\r\n(?![ \t])))#", $value)) {
            return false;
        }

        if (preg_match('/[^\x09\x0a\x0d\x20-\x7E\x80-\xFE]/', $value)) {
            return false;
        }

        return true;
    }

    /**
     * Inject a header into header array
     *
     * @param array<string, string|Stringable|array<string|Stringable>> $defaults
     * @param array<string, string|Stringable|array<string|Stringable>> $headers
     * @return array<string, string|Stringable|array<string|Stringable>>
     */
    protected static function injectDefaultHeaders(
        array $defaults,
        array $headers
    ): array {
        $testHeaders = array_change_key_case($headers, CASE_LOWER);

        foreach ($defaults as $key => $value) {
            if (!isset($testHeaders[strtolower($key)])) {
                if (!is_array($value)) {
                    $value = [$value];
                }

                $headers[$key] = $value;
            }
        }

        return $headers;
    }



    /**
     * Replace body stream
     */
    public function withBody(
        string|Channel|StreamInterface $body
    ): static {
        $output = clone $this;
        $output->body = $this->normalizeBody($body);

        return $output;
    }

    /**
     * Get active body stream object
     */
    public function getBody(): StreamInterface
    {
        return $this->body;
    }


    /**
     * Ensure stream object is available
     */
    public static function normalizeBody(
        string|Channel|StreamInterface|null $stream,
        string $mode = 'r'
    ): StreamInterface {
        if ($stream instanceof StreamInterface) {
            return $stream;
        }

        if ($stream === null) {
            return Stream::createTemp();
        }

        return new Stream($stream, $mode);
    }
}
