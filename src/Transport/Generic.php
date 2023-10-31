<?php

/**
 * @package Harvest
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Harvest\Transport;

use DecodeLabs\Exceptional;
use DecodeLabs\Harvest\Transport;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class Generic implements Transport
{
    public const MERGE_HEADERS = [
        'Set-Cookie'
    ];

    protected ?string $sendfile = null;
    protected bool $manualChunk = true;

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
                'Cannot send response, headers already sent'
            );
        }

        $status = $response->getStatusCode();
        $phrase = $response->getReasonPhrase();
        $stream = $response->getBody();
        $sendData = true;

        // Send status
        $header = 'HTTP/' . $response->getProtocolVersion();
        $header .= ' ' . (string)$status;

        if (!empty($phrase)) {
            $header .= ' ' . $phrase;
        }

        header($header, true, $status);



        // Send headers
        foreach ($response->getHeaders() as $header => $values) {
            $name = str_replace('-', ' ', $header);
            $name = ucwords($name);
            $name = str_replace(' ', '-', $name);
            $replace = !in_array($name, static::MERGE_HEADERS);

            if ($name === $this->sendfile) {
                $sendData = false;
            }

            foreach ($values as $value) {
                header($name . ': ' . $value, $replace, $status);
            }
        }


        // Hand off using x-sendfile
        if (
            $sendData &&
            $this->sendfile !== null &&
            $stream->getMetadata('wrapper_type') === 'plainfile' &&
            ($filePath = $stream->getMetadata('uri'))
        ) {
            header($this->sendfile . ': ' . $filePath, true, $status);
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
            $this->sendBody($response);
        }
    }

    /**
     * Send body data
     */
    protected function sendBody(
        ResponseInterface $response
    ): void {
        $stream = $response->getBody();

        if ($stream->isSeekable()) {
            $stream->rewind();
        }

        flush();

        $isChunked = $this->manualChunk ?
            $response->getHeaderLine('transfer-encoding') == 'chunked' :
            false;

        while (!$stream->eof()) {
            $chunk = $stream->read(4096);

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
