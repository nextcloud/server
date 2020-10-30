<?php

namespace Amp\ByteStream\Base64;

use Amp\ByteStream\OutputStream;
use Amp\ByteStream\StreamException;
use Amp\Failure;
use Amp\Promise;

final class Base64DecodingOutputStream implements OutputStream
{
    /** @var OutputStream */
    private $destination;

    /** @var string */
    private $buffer = '';

    /** @var int */
    private $offset = 0;

    public function __construct(OutputStream $destination)
    {
        $this->destination = $destination;
    }

    public function write(string $data): Promise
    {
        $this->buffer .= $data;

        $length = \strlen($this->buffer);
        $chunk = \base64_decode(\substr($this->buffer, 0, $length - $length % 4), true);
        if ($chunk === false) {
            return new Failure(new StreamException('Invalid base64 near offset ' . $this->offset));
        }

        $this->offset += $length - $length % 4;
        $this->buffer = \substr($this->buffer, $length - $length % 4);

        return $this->destination->write($chunk);
    }

    public function end(string $finalData = ""): Promise
    {
        $this->offset += \strlen($this->buffer);

        $chunk = \base64_decode($this->buffer . $finalData, true);
        if ($chunk === false) {
            return new Failure(new StreamException('Invalid base64 near offset ' . $this->offset));
        }

        $this->buffer = '';

        return $this->destination->end($chunk);
    }
}
