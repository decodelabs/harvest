<?php

/**
 * @package Harvest
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Harvest;

interface Transport
{
    public bool $headersSent { get; }
    public bool $supportsSendfile { get; }

    public function sendStatus(
        string $version,
        int $status,
        ?string $reasonPhrase = null
    ): void;

    public function sendHeader(
        string $name,
        string $value,
        bool $replace = true
    ): void;

    public function initiateBody(): void;

    public function sendBodyChunk(
        string $chunk
    ): void;

    public function sendTerminator(): void;
}
