<?php

/**
 * @package Harvest
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Harvest\Response;

use DecodeLabs\Harvest\Message\Stream as MessageStream;
use DecodeLabs\Singularity;
use Psr\Http\Message\UriInterface;
use Stringable;

class Redirect extends Stream
{
    /**
     * Init with text stream and content type headers set
     *
     * @param array<string, string|Stringable|array<string|Stringable>> $headers
     */
    public function __construct(
        string|UriInterface $uri,
        int $status = 302,
        array $headers = []
    ) {
        parent::__construct(
            MessageStream::fromString(
                $this->getContent($uri),
                'wb+'
            ),
            $status,
            $this->injectDefaultHeaders([
                'content-type' => 'text/html; charset=utf-8',
                'location' => [(string)Singularity::url((string)$uri)]
            ], $headers)
        );
    }


    protected function getContent(
        string|UriInterface $uri
    ): string {
        return
            '<html><head><title>Redirecting...</title></head><body>' .
            '<p>Redirecting to <a href="' . $uri . '">' . $uri . '</a></p>' .
            '</body></html>';
    }
}
