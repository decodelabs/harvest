<?php

/**
 * @package Harvest
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Harvest\Transport;

use DecodeLabs\Coercion;
use DecodeLabs\Exceptional;
use DecodeLabs\Harvest\Transport;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class Generic implements Transport
{
    protected const array MergeHeaders = [
        'Set-Cookie'
    ];

    protected const int ChunkSize = 4096;

    protected ?string $sendfile = null;
    protected bool $manualChunk = false;



    /**
     * Define sendfile header
     *
     * @return $this
     */
    public function setSendfileHeader(
        ?string $sendfile
    ): static {
        $this->sendfile = $sendfile;
        return $this;
    }

    /**
     * Get sendfile header
     */
    public function getSendfileHeader(): ?string
    {
        return $this->sendfile;
    }

    /**
     * Set manual chunking
     */
    public function setManualChunk(
        bool $chunk
    ): static {
        $this->manualChunk = $chunk;
        return $this;
    }

    /**
     * Will this send chunk manually?
     */
    public function shouldManualChunk(): bool
    {
        return $this->manualChunk;
    }



    /**
     * Send response
     */
    public function sendResponse(
        ServerRequestInterface $request,
        ResponseInterface $response
    ): void {
        if (headers_sent()) {
            throw Exceptional::Runtime(
                message: 'Cannot send response, headers already sent'
            );
        }


        $status = $response->getStatusCode();
        $stream = $response->getBody();
        $streamSeekable = $stream->isSeekable();
        $sendData = true;


        // Add accept-ranges header
        if (
            $status === 200 &&
            $streamSeekable
        ) {
            $response = $response->withHeader('Accept-Ranges', 'bytes');
        }


        // Check if we need to send a range
        $range = $request->getHeaderLine('range');
        $start = null;
        $end = null;
        $isRange = false;

        if (
            $status === 200 &&
            $stream->isSeekable() &&
            $range !== '' &&
            null !== ($size = $stream->getSize()) &&
            str_starts_with($range, 'bytes=')
        ) {
            $range = explode('=', $range);
            $range = explode('-', $range[1]);

            $start = (int)$range[0];
            $end = (int)$range[1];
            $isRange = true;


            if (
                $start > $end ||
                $start < 0 ||
                $end < 0 ||
                $start > $size ||
                $end > $size
            ) {
                $response = $response->withStatus($status = 416);
                $sendData = false;
            } else {
                $response = $response->withStatus($status = 206);
                $response = $response->withHeader('Content-Range', 'bytes ' . $start . '-' . $end . '/' . $size);
                $response = $response->withHeader('Content-Length', (string)($end - $start + 1));
            }
        }

        $phrase = $response->getReasonPhrase();


        // Send status
        $header = 'HTTP/' . $response->getProtocolVersion();
        $header .= ' ' . (string)$status;

        if (!empty($phrase)) {
            $header .= ' ' . $phrase;
        }

        header($header, true, $status);

        /**
         * Send headers
         * @var string $header
         * @var array<string> $values
         */
        foreach ($response->getHeaders() as $header => $values) {
            $name = str_replace('-', ' ', $header);
            $name = ucwords($name);
            $name = str_replace(' ', '-', $name);
            $replace = !in_array($name, static::MergeHeaders);

            if ($name === $this->sendfile) {
                $sendData = false;
            }

            foreach ($values as $value) {
                header($name . ': ' . $value, $replace, $status);
            }
        }


        // Hand off using x-sendfile
        if (
            $this->sendfile === null &&
            isset($_SERVER['HTTP_X_SENDFILE_TYPE']) &&
            $_SERVER['HTTP_X_SENDFILE_TYPE'] !== 'X-Accel-Redirect'
        ) {
            $this->sendfile = Coercion::asString($_SERVER['HTTP_X_SENDFILE_TYPE']);
        }

        if (
            $sendData &&
            !$isRange &&
            $this->sendfile !== null &&
            $stream->getMetadata('wrapper_type') === 'plainfile' &&
            ($filePath = $stream->getMetadata('uri'))
        ) {
            header($this->sendfile . ': ' . Coercion::asString($filePath), true, $status);
            $sendData = false;
        }


        // Check request requiring data
        if (
            $request->getMethod() === 'HEAD' ||
            $status === 304
        ) {
            $sendData = false;
        }


        // Send body if we need to
        if ($sendData) {
            $this->sendBody($response, $start, $end);
        }
    }

    /**
     * Send body data
     */
    protected function sendBody(
        ResponseInterface $response,
        ?int $start = null,
        ?int $end = null
    ): void {
        $stream = $response->getBody();

        if ($stream->isSeekable()) {
            $stream->rewind();

            if ($start > 0) {
                $stream->seek($start);
            }
        }

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        flush();
        set_time_limit(0);

        $isChunked = $this->manualChunk ?
            $response->getHeaderLine('transfer-encoding') == 'chunked' :
            false;

        if ($end !== null) {
            $length = $end - $start + 1;
        } else {
            $length = null;
        }

        while (
            !$stream->eof() &&
            (
                $length === null ||
                $length > 0
            )
        ) {
            if ($length !== null) {
                $chunkSize = min($length, static::ChunkSize);
                $length -= $chunkSize;
            } else {
                $chunkSize = static::ChunkSize;
            }

            $chunk = $stream->read($chunkSize);

            if ($isChunked) {
                echo dechex(strlen($chunk)) . "\r\n";
                echo $chunk . "\r\n";
                flush();
            } else {
                echo $chunk;
                flush();
            }
        }

        // Send end chunk
        if ($isChunked) {
            echo "0\r\n\r\n";
            flush();
        }
    }
}
