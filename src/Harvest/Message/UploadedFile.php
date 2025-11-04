<?php

/**
 * Harvest
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Harvest\Message;

use DecodeLabs\Atlas\Dir;
use DecodeLabs\Deliverance\Channel\Stream as Channel;
use DecodeLabs\Exceptional;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;

class UploadedFile implements UploadedFileInterface
{
    /**
     * @var array<int,string>
     */
    protected const array Errors = [
        UPLOAD_ERR_OK => 'The file uploaded successfully',
        UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive',
        UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive',
        UPLOAD_ERR_PARTIAL => 'The uploaded file was not fully completed',
        UPLOAD_ERR_NO_FILE => 'The file was not uploaded',
        UPLOAD_ERR_NO_TMP_DIR => 'Upload temp dir was not available',
        UPLOAD_ERR_CANT_WRITE => 'Upload temp dir was not writable',
        UPLOAD_ERR_EXTENSION => 'The file upload was cancelled by a PHP extension'
    ];

    protected ?int $size = null;
    protected ?string $fileName = null;
    protected ?string $type = null;
    protected int $error;

    protected ?string $file = null;
    protected ?StreamInterface $stream = null;

    protected bool $moved = false;


    public function __construct(
        string|StreamInterface|Channel $file,
        ?int $size,
        int $error,
        ?string $fileName = null,
        ?string $type = null
    ) {
        if ($error === UPLOAD_ERR_OK) {
            if (is_string($file)) {
                $this->file = $file;
            } else {
                if (!$file instanceof StreamInterface) {
                    $file = new Stream($file);
                }

                $this->stream = $file;
            }
        }

        $this->size = $size;

        if (!isset(static::Errors[$error])) {
            throw Exceptional::InvalidArgument(
                message: 'Invalid uploaded file status: ' . $error
            );
        }

        $this->error = $error;
        $this->fileName = $fileName;
        $this->type = $type;
    }


    public function getSize(): ?int
    {
        return $this->size;
    }

    public function getClientFilename(): ?string
    {
        return $this->fileName;
    }

    public function getClientMediaType(): ?string
    {
        return $this->type;
    }

    public function getError(): int
    {
        return $this->error;
    }



    public function moveTo(
        string|Dir $targetPath
    ): void {
        if ($this->moved) {
            throw Exceptional::Runtime(
                message: 'File has already been moved'
            );
        }

        if ($this->error !== UPLOAD_ERR_OK) {
            throw Exceptional::Runtime(
                message: 'Cannot move file: ' . static::Errors[$this->error]
            );
        }

        if (empty($targetPath = (string)$targetPath)) {
            throw Exceptional::InvalidArgument(
                message: 'Invalid upload file target path'
            );
        }

        $targetDir = dirname($targetPath);

        if (
            !is_dir($targetDir) ||
            !is_writable($targetDir)
        ) {
            throw Exceptional::Runtime(
                message: 'Target directory doesn\'t exist: ' . $targetDir
            );
        }

        $sapi = PHP_SAPI;

        if (
            // @phpstan-ignore-next-line
            empty($sapi) ||
            0 === strpos($sapi, 'cli') ||
            !$this->file
        ) {
            $this->writeFile($targetPath);
        } elseif (false === move_uploaded_file($this->file, $targetPath)) {
            throw Exceptional::Runtime(
                message: 'Moving uploaded file failed'
            );
        }

        $this->moved = true;
    }


    protected function writeFile(
        string $targetPath
    ): void {
        if (false === ($fp = fopen($targetPath, 'wb+'))) {
            throw Exceptional::Runtime(
                message: 'Target path is not writable'
            );
        }

        $stream = $this->getStream();
        $stream->rewind();

        while (!$stream->eof()) {
            fwrite($fp, $stream->read(4096));
        }

        fclose($fp);
    }


    public function getStream(): StreamInterface
    {
        if ($this->error !== UPLOAD_ERR_OK) {
            throw Exceptional::Runtime(
                message: 'Stream not available: ' . static::Errors[$this->error]
            );
        }

        if ($this->moved) {
            throw Exceptional::Runtime(
                message: 'Stream not available, file has already been moved'
            );
        }

        if ($this->stream instanceof StreamInterface) {
            return $this->stream;
        }

        if ($this->file === null) {
            throw Exceptional::Runtime(
                message: 'Stream not available, file not locatable'
            );
        }

        $this->stream = new Stream($this->file);
        return $this->stream;
    }
}
