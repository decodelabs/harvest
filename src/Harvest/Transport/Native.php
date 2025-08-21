<?php

/**
 * @package Harvest
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Harvest\Transport;

use DecodeLabs\Harvest\Transport;

class Native implements Transport
{
    public bool $headersSent {
        get => headers_sent();
    }

    public bool $supportsSendfile {
        get => true;
    }

    public function sendStatus(
        string $version,
        int $status,
        ?string $reasonPhrase = null
    ): void {
        $header = 'HTTP/' . $version;
        $header .= ' ' . (string)$status;

        if (!empty($reasonPhrase)) {
            $header .= ' ' . $reasonPhrase;
        }

        header($header, true, $status);
    }

    public function sendHeader(
        string $name,
        string $value,
        bool $replace = true
    ): void {
        header($name . ': ' . $value, $replace);
    }

    public function initiateBody(): void
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        flush();
    }

    public function sendBodyChunk(
        string $chunk
    ): void {
        echo $chunk;
        flush();
    }

    public function sendTerminator(): void
    {
        // No-op
    }
}
