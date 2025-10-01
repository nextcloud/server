<?php

namespace Aws\Api\Parser;

use GuzzleHttp\Psr7;
use Psr\Http\Message\StreamInterface;
use Aws\Api\Parser\Exception\ParserException;

/**
 * @inheritDoc
 */
class NonSeekableStreamDecodingEventStreamIterator extends DecodingEventStreamIterator
{
    /** @var array $tempBuffer */
    private $tempBuffer;

    /**
     * NonSeekableStreamDecodingEventStreamIterator constructor.
     *
     * @param StreamInterface $stream
     */
    public function __construct(StreamInterface $stream)
    {
        $this->stream = $stream;
        if ($this->stream->isSeekable()) {
            throw new \InvalidArgumentException('The stream provided must be not seekable.');
        }

        $this->tempBuffer = [];
    }

    /**
     * @inheritDoc
     *
     * @return array
     */
    protected function parseEvent(): array
    {
        $event = [];
        $this->hashContext = hash_init('crc32b');
        $prelude = $this->parsePrelude()[0];
        list(
            $event[self::HEADERS],
            $numBytes
        ) = $this->parseHeaders($prelude[self::LENGTH_HEADERS]);
        $event[self::PAYLOAD] = Psr7\Utils::streamFor(
            $this->readAndHashBytes(
                $prelude[self::LENGTH_TOTAL] - self::BYTES_PRELUDE
                - $numBytes - self::BYTES_TRAILING
            )
        );
        $calculatedCrc = hash_final($this->hashContext, true);
        $messageCrc = $this->stream->read(4);
        if ($calculatedCrc !== $messageCrc) {
            throw new ParserException('Message checksum mismatch.');
        }

        return $event;
    }

    protected function readAndHashBytes($num): string
    {
        $bytes = '';
        while (!empty($this->tempBuffer) && $num > 0) {
            $byte = array_shift($this->tempBuffer);
            $bytes .= $byte;
            $num = $num - 1;
        }

        $bytes = $bytes . $this->stream->read($num);
        hash_update($this->hashContext, $bytes);

        return $bytes;
    }

    // Iterator Functionality

    #[\ReturnTypeWillChange]
    public function rewind()
    {
        $this->currentEvent = $this->parseEvent();
    }

    public function next()
    {
        $this->tempBuffer[] = $this->stream->read(1);
        if ($this->valid()) {
            $this->key++;
            $this->currentEvent = $this->parseEvent();
        }
    }

    /**
     * @return bool
     */
    #[\ReturnTypeWillChange]
    public function valid()
    {
        return !$this->stream->eof();
    }
}
