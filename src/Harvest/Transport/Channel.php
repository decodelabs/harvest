<?php

/**
 * Harvest
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Harvest\Transport;

use DecodeLabs\Deliverance\DataReceiver;
use DecodeLabs\Harvest\Transport;

class Channel implements Transport
{
    public protected(set) bool $headersSent = false;

    public bool $supportsSendfile {
        get => false;
    }

    public protected(set) DataReceiver $receiver;

    private bool $chunked = false;

    public function __construct(
        DataReceiver $receiver,
    ) {
        $this->receiver = $receiver;
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

        $this->receiver->writeLine($header);
    }

    public function sendHeader(
        string $name,
        string $value,
        bool $replace = true
    ): void {
        if (
            strtolower($name) === 'transfer-encoding' &&
            strtolower($value) === 'chunked'
        ) {
            $this->chunked = true;
        }

        $this->receiver->writeLine($name . ': ' . $value);
    }

    public function initiateBody(): void
    {
        $this->receiver->writeLine();
    }

    public function sendBodyChunk(
        string $chunk
    ): void {
        if ($this->chunked) {
            $this->receiver->write(dechex(strlen($chunk)) . "\r\n" . $chunk . "\r\n");
        } else {
            $this->receiver->write($chunk);
        }
    }

    public function sendTerminator(): void
    {
        if ($this->chunked) {
            $this->receiver->write("0\r\n\r\n");
        }
    }
}
