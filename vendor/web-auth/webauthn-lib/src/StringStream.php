<?php

declare(strict_types=1);

namespace Webauthn;

use CBOR\Stream;
use Webauthn\Exception\InvalidDataException;
use function fclose;
use function fopen;
use function fread;
use function fwrite;
use function rewind;

final class StringStream implements Stream
{
    /**
     * @var resource
     */
    private $data;

    private readonly int $length;

    private int $totalRead = 0;

    public function __construct(string $data)
    {
        $this->length = mb_strlen($data, '8bit');
        $resource = fopen('php://memory', 'rb+');
        fwrite($resource, $data);
        rewind($resource);
        $this->data = $resource;
    }

    public function read(int $length): string
    {
        if ($length <= 0) {
            return '';
        }
        $read = fread($this->data, $length);
        $bytesRead = mb_strlen($read, '8bit');
        mb_strlen($read, '8bit') === $length || throw InvalidDataException::create(null, sprintf(
            'Out of range. Expected: %d, read: %d.',
            $length,
            $bytesRead
        ));
        $this->totalRead += $bytesRead;

        return $read;
    }

    public function close(): void
    {
        fclose($this->data);
    }

    public function isEOF(): bool
    {
        return $this->totalRead === $this->length;
    }
}
