<?php

/**
 * @package Harvest
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Harvest;

use DecodeLabs\Coercion;
use DecodeLabs\Exceptional;
use DecodeLabs\Harvest\ResponseHandler\Range;
use DecodeLabs\Harvest\Transport\Native as NativeTransport;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ResponseHandler
{
    protected const array MergeHeaders = [
        'Set-Cookie'
    ];

    public ?string $sendfileHeader = null;

    public int $chunkSize = 4096 {
        set(int $chunkSize) {
            if ($chunkSize < 8) {
                throw Exceptional::InvalidArgument(
                    message: 'Chunk size must be 8 or greater',
                    data: ['chunkSize' => $chunkSize]
                );
            }

            $this->chunkSize = $chunkSize;
        }
    }

    public protected(set) Transport $transport;

    private bool $sendData = true;

    public function __construct(
        ?Transport $transport = null
    ) {
        $this->transport = $transport ?? new NativeTransport();
    }

    public function sendResponse(
        ServerRequestInterface $request,
        ResponseInterface $response
    ): void {
        if ($this->transport->headersSent) {
            throw Exceptional::Runtime(
                message: 'Cannot send response, headers already sent'
            );
        }


        $this->sendData = true;

        // Detect sendfile header
        $this->detectSendfileHeader();

        // Propagate protocol version from request to response
        $response = $this->propagateProtocolVersion($request, $response);

        // Detect head request
        $this->detectNoBodyResponse($request, $response);

        // Apply accept-ranges header
        $response = $this->applyAcceptRangesHeader($response);

        // Apply content-length header
        $response = $this->applyContentLengthHeader($response);

        // Detect range
        $range = $this->detectRange($request, $response);
        $response = $this->applyRange($response, $range);


        // Send status
        $this->transport->sendStatus(
            $response->getProtocolVersion(),
            $response->getStatusCode(),
            $response->getReasonPhrase()
        );

        // Send headers
        // @phpstan-ignore-next-line
        $this->sendHeaders($response->getHeaders());

        // Hand off using x-sendfile
        $this->applySendfileHeader($response, $range);

        // Send body if we need to
        if ($this->sendData) {
            $this->sendBody($response, $range);
        }
    }

    protected function propagateProtocolVersion(
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface {
        if ($response->getProtocolVersion() === $request->getProtocolVersion()) {
            return $response;
        }

        return $response->withProtocolVersion($request->getProtocolVersion());
    }

    protected function detectNoBodyResponse(
        ServerRequestInterface $request,
        ResponseInterface $response
    ): void {
        if (
            $request->getMethod() === 'HEAD' ||
            $response->getStatusCode() === 304
        ) {
            $this->sendData = false;
        }
    }

    protected function detectSendfileHeader(): void
    {
        if (
            $this->sendfileHeader === null &&
            $this->transport->supportsSendfile &&
            isset($_SERVER['HTTP_X_SENDFILE_TYPE']) &&
            $_SERVER['HTTP_X_SENDFILE_TYPE'] !== 'X-Accel-Redirect'
        ) {
            $this->sendfileHeader = Coercion::asString($_SERVER['HTTP_X_SENDFILE_TYPE']);
        }
    }

    protected function applyContentLengthHeader(
        ResponseInterface $response
    ): ResponseInterface {
        if (
            $response->getStatusCode() !== 200 ||
            $response->hasHeader('Content-Length')
        ) {
            return $response;
        }

        if (null === ($size = $response->getBody()->getSize())) {
            return $response->withHeader('Transfer-Encoding', 'chunked');
        }

        return $response->withHeader('Content-Length', (string)$size);
    }

    protected function applyAcceptRangesHeader(
        ResponseInterface $response
    ): ResponseInterface {
        if (
            $response->getStatusCode() === 200 &&
            $response->getBody()->isSeekable()
        ) {
            $response = $response->withHeader('Accept-Ranges', 'bytes');
        }

        return $response;
    }

    protected function detectRange(
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ?Range {
        $range = $request->getHeaderLine('range');

        if (
            $range === '' ||
            !str_starts_with($range, 'bytes=')
        ) {
            return null;
        }

        $range = explode('=', $range);
        $range = explode('-', $range[1]);

        $start = (int)$range[0];
        $end = (int)$range[1];

        return new Range(
            start: $start,
            end: $end,
            size: $response->getBody()->getSize()
        );
    }

    protected function applyRange(
        ResponseInterface $response,
        ?Range $range
    ): ResponseInterface {
        if ($range === null) {
            return $response;
        }

        if (!$range->isValid()) {
            $response = $response->withStatus(416);
            $this->sendData = false;
            return $response;
        }

        if (
            $response->getStatusCode() !== 200 ||
            !$response->getBody()->isSeekable()
        ) {
            return $response;
        }

        $response = $response->withStatus(206);
        $response = $response->withHeader('Content-Range', (string)$range);
        $response = $response->withHeader('Content-Length', (string)$range->length);

        return $response;
    }


    /**
     * @param array<string,array<string>> $headers
     */
    protected function sendHeaders(
        array $headers
    ): void {
        foreach ($headers as $header => $values) {
            $name = str_replace('-', ' ', $header);
            $name = ucwords($name);
            $name = str_replace(' ', '-', $name);
            $replace = !in_array($name, static::MergeHeaders);

            if ($name === $this->sendfileHeader) {
                $this->sendData = false;
            }

            foreach ($values as $value) {
                $this->transport->sendHeader(
                    $name,
                    $value,
                    $replace
                );
            }
        }
    }

    protected function applySendfileHeader(
        ResponseInterface $response,
        ?Range $range
    ): void {
        if (
            !$this->sendData ||
            $this->sendfileHeader === null ||
            !$this->transport->supportsSendfile ||
            $range !== null
        ) {
            return;
        }

        $stream = $response->getBody();

        if (
            $stream->getMetadata('wrapper_type') === 'plainfile' &&
            ($filePath = $stream->getMetadata('uri'))
        ) {
            $this->transport->sendHeader(
                $this->sendfileHeader,
                Coercion::asString($filePath),
                true
            );

            $this->sendData = false;
        }
    }

    protected function sendBody(
        ResponseInterface $response,
        ?Range $range
    ): void {
        $this->transport->initiateBody();
        set_time_limit(0);

        $stream = $response->getBody();

        if ($stream->isSeekable()) {
            $stream->rewind();

            if ($range?->start > 0) {
                $stream->seek($range->start);
            }
        }

        if ($range !== null) {
            $length = $range->length;
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
                $chunkSize = min($length, $this->chunkSize);
                $length -= $chunkSize;
            } else {
                $chunkSize = $this->chunkSize;
            }

            $chunk = $stream->read($chunkSize);
            $this->transport->sendBodyChunk($chunk);
        }

        $this->transport->sendTerminator();
    }
}
