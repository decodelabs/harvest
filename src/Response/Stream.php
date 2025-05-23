<?php

/**
 * @package Harvest
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Harvest\Response;

use DecodeLabs\Deliverance\Channel\Stream as Channel;
use DecodeLabs\Exceptional;
use DecodeLabs\Harvest\Response;
use DecodeLabs\Harvest\ResponseTrait;
use Psr\Http\Message\StreamInterface;
use Stringable;

class Stream implements Response
{
    use ResponseTrait {
        ResponseTrait::__construct as protected __messageConstruct;
    }

    /**
     * @var array<int,string>
     */
    protected const array Codes = [
        // Info code
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',
        103 => 'Early Hints',

        // Success codes
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-Status',
        208 => 'Already Reported',
        226 => 'IM Used',

        // Redirect codes
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        306 => 'Switch Proxy', // Deprecated to 306 => '(Unused)'
        307 => 'Temporary Redirect',
        308 => 'Permanent Redirect',

        // Client codes
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Payload Too Large',
        414 => 'URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Range Not Satisfiable',
        417 => 'Expectation Failed',
        418 => 'I\'m a teapot',
        421 => 'Misdirected Request',
        422 => 'Unprocessable Entity',
        423 => 'Locked',
        424 => 'Failed Dependency',
        425 => 'Unordered Collection',
        426 => 'Upgrade Required',
        428 => 'Precondition Required',
        429 => 'Too Many Requests',
        431 => 'Request Header Fields Too Large',
        444 => 'Connection Closed Without Response',
        451 => 'Unavailable For Legal Reasons',
        499 => 'Client Closed Request',

        // Server codes
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        506 => 'Variant Also Negotiates',
        507 => 'Insufficient Storage',
        508 => 'Loop Detected',
        510 => 'Not Extended',
        511 => 'Network Authentication Required',
        599 => 'Network Connect Timeout Error'
    ];

    protected int $status;
    protected ?string $phrase = null;


    /**
     * Initiate standard response using stream
     *
     * @param array<string, string|Stringable|array<string|Stringable>> $headers
     */
    public function __construct(
        string|Channel|StreamInterface $body = 'php://memory',
        int $status = 200,
        array $headers = []
    ) {
        $this->status = $this->normalizeStatusCode($status);
        $this->__messageConstruct($body, $headers);
    }


    /**
     * New instance with status code set
     */
    public function withStatus(
        int $code,
        ?string $reasonPhrase = null
    ): static {
        if (empty($reasonPhrase)) {
            $reasonPhrase = null;
        }

        $output = clone $this;
        $output->status = $this->normalizeStatusCode($code);
        $output->phrase = $reasonPhrase;

        return $output;
    }

    /**
     * Get current status code
     */
    public function getStatusCode(): int
    {
        return $this->status;
    }


    /**
     * Get HTTP status message
     */
    public function getReasonPhrase(): string
    {
        return $this->phrase ?? static::Codes[$this->status];
    }


    /**
     * Ensure code is valid
     */
    public static function normalizeStatusCode(
        int $code
    ): int {
        if (!isset(static::Codes[$code])) {
            throw Exceptional::InvalidArgument(
                message: 'Invalid HTTP status code: ' . $code
            );
        }

        return $code;
    }
}
