<?php

/**
 * @package Harvest
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Harvest;

use DecodeLabs\Collections\Tree;
use DecodeLabs\Deliverance\Channel\Stream as Channel;
use DecodeLabs\Exceptional;
use DecodeLabs\Harvest\Message\Stream;
use Psr\Http\Message\StreamInterface;
use Stringable;

trait MessageTrait
{
    protected(set) string $protocol = '1.1';

    /**
     * @var array<string, array<string>>
     */
    protected(set) array $headers = [];

    /**
     * @var array<string, string>
     */
    protected(set) array $headerAliases = [];

    protected(set) StreamInterface $body;

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
                    message: 'Invalid header name: ' . $name
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
                message: 'Invalid HTTP protocol version: ' . $version,
                data: $version
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
                message: 'Invalid header name: ' . $name
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
                message: 'Invalid header name: ' . $name
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
     * @param string|Stringable|int|float|array<string|Stringable|int|float> $value
     * @return array<string>
     */
    public static function normalizeHeader(
        string|Stringable|int|float|array $value
    ): array {
        if (!is_array($value)) {
            $value = [$value];
        }

        return array_map(function ($value) {
            $value = (string)$value;

            if (!static::isHeaderValueValid($value)) {
                throw Exceptional::InvalidArgument(
                    message: 'Invalid header value',
                    data: $value
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
     * Get body string
     */
    public function getBodyString(): string
    {
        return $this->body->getContents();
    }

    /**
     * Get form data
     *
     * @return Tree<string|int|float|bool>
     */
    public function getFormData(): Tree
    {
        $parts = explode(';', $this->getHeaderLine('Content-Type'));
        $contentType = array_shift($parts);

        return match($contentType) {
            'application/x-www-form-urlencoded' => $this->extractFormUrlEncoded(),
            'multipart/form-data' => $this->extractFormMultipart(),
            'application/json' => $this->extractJson(),
            default => throw Exceptional::UnexpectedValue(
                message: 'Body is not form data'
            )
        };
    }

    /**
     * Get form url encoded data
     *
     * @return Tree<string|int|float|bool>
     */
    public function getFormUrlEncoded(): Tree
    {
        if (
            $this->hasHeader('Content-Type') &&
            !preg_match('/^application\/x-www-form-urlencoded\b/i', $this->getHeaderLine('Content-Type'))
        ) {
            throw Exceptional::UnexpectedValue(
                message: 'Body is not form url encoded'
            );
        }

        return $this->extractFormUrlEncoded();
    }

    /**
     * Extract form url encoded data
     *
     * @return Tree<string|int|float|bool>
     */
    protected function extractFormUrlEncoded(): Tree
    {
        // @phpstan-ignore-next-line
        return Tree::fromDelimitedString($this->getBodyString());
    }

    /**
     * Get form multipart data
     *
     * @return Tree<string|int|float|bool>
     */
    public function getFormMultipart(): Tree
    {
        if (
            $this->hasHeader('Content-Type') &&
            !preg_match('/^multipart\/form-data\b/i', $this->getHeaderLine('Content-Type'))
        ) {
            throw Exceptional::UnexpectedValue(
                message: 'Body is not form multipart'
            );
        }

        return $this->extractFormMultipart();
    }

    /**
     * Extract form multipart data
     *
     * @return Tree<string|int|float|bool>
     */
    protected function extractFormMultipart(): Tree
    {
        // Cannot access multipart data from php://input
        /** @var array<string,bool|float|int|string> $_POST */
        return new Tree($_POST);
    }

    /**
     * Get body JSON
     *
     * @return Tree<string|int|float|bool>
     */
    public function getJson(): Tree
    {
        if (
            $this->hasHeader('Content-Type') &&
            !preg_match('/^application\/json\b/i', $this->getHeaderLine('Content-Type'))
        ) {
            throw Exceptional::UnexpectedValue(
                message: 'Body is not JSON'
            );
        }

        return $this->extractJson();
    }

    /**
     * Extract JSON data
     *
     * @return Tree<string|int|float|bool>
     */
    protected function extractJson(): Tree
    {
        $output = json_decode($this->getBodyString(), true);

        if (is_iterable($output)) {
            // @phpstan-ignore-next-line
            $output = new Tree($output);
        } else {
            // @phpstan-ignore-next-line
            $output = new Tree(null, $output);
        }

        // @phpstan-ignore-next-line
        return $output;
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
