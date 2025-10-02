<?php

namespace Aws\Api\Parser;

use \Iterator;
use Aws\Api\DateTimeResult;
use GuzzleHttp\Psr7;
use Psr\Http\Message\StreamInterface;
use Aws\Api\Parser\Exception\ParserException;

/**
 * @internal Implements a decoder for a binary encoded event stream that will
 * decode, validate, and provide individual events from the stream.
 */
class DecodingEventStreamIterator implements Iterator
{
    const HEADERS = 'headers';
    const PAYLOAD = 'payload';

    const LENGTH_TOTAL = 'total_length';
    const LENGTH_HEADERS = 'headers_length';

    const CRC_PRELUDE = 'prelude_crc';

    const BYTES_PRELUDE = 12;
    const BYTES_TRAILING = 4;

    private static $preludeFormat = [
        self::LENGTH_TOTAL => 'decodeUint32',
        self::LENGTH_HEADERS => 'decodeUint32',
        self::CRC_PRELUDE => 'decodeUint32',
    ];

    private static $lengthFormatMap = [
        1 => 'decodeUint8',
        2 => 'decodeUint16',
        4 => 'decodeUint32',
        8 => 'decodeUint64',
    ];

    private static $headerTypeMap = [
        0 => 'decodeBooleanTrue',
        1 => 'decodeBooleanFalse',
        2 => 'decodeInt8',
        3 => 'decodeInt16',
        4 => 'decodeInt32',
        5 => 'decodeInt64',
        6 => 'decodeBytes',
        7 => 'decodeString',
        8 => 'decodeTimestamp',
        9 => 'decodeUuid',
    ];

    /** @var StreamInterface Stream of eventstream shape to parse. */
    protected $stream;

    /** @var array Currently parsed event. */
    protected $currentEvent;

    /** @var int Current in-order event key. */
    protected $key;

    /** @var resource|\HashContext CRC32 hash context for event validation */
    protected $hashContext;

    /** @var int $currentPosition */
    protected $currentPosition;

    /**
     * DecodingEventStreamIterator constructor.
     *
     * @param StreamInterface $stream
     */
    public function __construct(StreamInterface $stream)
    {
        $this->stream = $stream;
        $this->rewind();
    }

    protected function parseHeaders($headerBytes)
    {
        $headers = [];
        $bytesRead = 0;

        while ($bytesRead < $headerBytes) {
            list($key, $numBytes) = $this->decodeString(1);
            $bytesRead += $numBytes;

            list($type, $numBytes) = $this->decodeUint8();
            $bytesRead += $numBytes;

            $f = self::$headerTypeMap[$type];
            list($value, $numBytes) = $this->{$f}();
            $bytesRead += $numBytes;

            if (isset($headers[$key])) {
                throw new ParserException('Duplicate key in event headers.');
            }
            $headers[$key] = $value;
        }

        return [$headers, $bytesRead];
    }

    protected function parsePrelude()
    {
        $prelude = [];
        $bytesRead = 0;

        $calculatedCrc = null;
        foreach (self::$preludeFormat as $key => $decodeFunction) {
            if ($key === self::CRC_PRELUDE) {
                $hashCopy = hash_copy($this->hashContext);
                $calculatedCrc = hash_final($this->hashContext, true);
                $this->hashContext = $hashCopy;
            }
            list($value, $numBytes) = $this->{$decodeFunction}();
            $bytesRead += $numBytes;

            $prelude[$key] = $value;
        }

        if (unpack('N', $calculatedCrc)[1] !== $prelude[self::CRC_PRELUDE]) {
            throw new ParserException('Prelude checksum mismatch.');
        }

        return [$prelude, $bytesRead];
    }

    /**
     * This method decodes an event from the stream.
     *
     * @return array
     */
    protected function parseEvent()
    {
        $event = [];

        if ($this->stream->tell() < $this->stream->getSize()) {
            $this->hashContext = hash_init('crc32b');

            $bytesLeft = $this->stream->getSize() - $this->stream->tell();
            list($prelude, $numBytes) = $this->parsePrelude();
            if ($prelude[self::LENGTH_TOTAL] > $bytesLeft) {
                throw new ParserException('Message length too long.');
            }
            $bytesLeft -= $numBytes;

            if ($prelude[self::LENGTH_HEADERS] > $bytesLeft) {
                throw new ParserException('Headers length too long.');
            }

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
        }

        return $event;
    }

    // Iterator Functionality

    /**
     * @return array
     */
    #[\ReturnTypeWillChange]
    public function current()
    {
        return $this->currentEvent;
    }

    /**
     * @return int
     */
    #[\ReturnTypeWillChange]
    public function key()
    {
        return $this->key;
    }

    /**
     * @return void
     */
    #[\ReturnTypeWillChange]
    public function next()
    {
        $this->currentPosition = $this->stream->tell();
        if ($this->valid()) {
            $this->key++;
            $this->currentEvent = $this->parseEvent();
        }
    }

    /**
     * @return void
     */
    #[\ReturnTypeWillChange]
    public function rewind()
    {
        $this->stream->rewind();
        $this->key = 0;
        $this->currentPosition = 0;
        $this->currentEvent = $this->parseEvent();
    }

    /**
     * @return bool
     */
    #[\ReturnTypeWillChange]
    public function valid()
    {
        return $this->currentPosition < $this->stream->getSize();
    }

    // Decoding Utilities

    protected function readAndHashBytes($num)
    {
        $bytes = $this->stream->read($num);
        hash_update($this->hashContext, $bytes);
        return $bytes;
    }

    private function decodeBooleanTrue()
    {
        return [true, 0];
    }

    private function decodeBooleanFalse()
    {
        return [false, 0];
    }

    private function uintToInt($val, $size)
    {
        $signedCap = pow(2, $size - 1);
        if ($val > $signedCap) {
            $val -= (2 * $signedCap);
        }
        return $val;
    }

    private function decodeInt8()
    {
        $val = (int)unpack('C', $this->readAndHashBytes(1))[1];
        return [$this->uintToInt($val, 8), 1];
    }

    private function decodeUint8()
    {
        return [unpack('C', $this->readAndHashBytes(1))[1], 1];
    }

    private function decodeInt16()
    {
        $val = (int)unpack('n', $this->readAndHashBytes(2))[1];
        return [$this->uintToInt($val, 16), 2];
    }

    private function decodeUint16()
    {
        return [unpack('n', $this->readAndHashBytes(2))[1], 2];
    }

    private function decodeInt32()
    {
        $val = (int)unpack('N', $this->readAndHashBytes(4))[1];
        return [$this->uintToInt($val, 32), 4];
    }

    private function decodeUint32()
    {
        return [unpack('N', $this->readAndHashBytes(4))[1], 4];
    }

    private function decodeInt64()
    {
        $val = $this->unpackInt64($this->readAndHashBytes(8))[1];
        return [$this->uintToInt($val, 64), 8];
    }

    private function decodeUint64()
    {
        return [$this->unpackInt64($this->readAndHashBytes(8))[1], 8];
    }

    private function unpackInt64($bytes)
    {
        return unpack('J', $bytes);
    }

    private function decodeBytes($lengthBytes=2)
    {
        if (!isset(self::$lengthFormatMap[$lengthBytes])) {
            throw new ParserException('Undefined variable length format.');
        }
        $f = self::$lengthFormatMap[$lengthBytes];
        list($len, $bytes) = $this->{$f}();
        return [$this->readAndHashBytes($len), $len + $bytes];
    }

    private function decodeString($lengthBytes=2)
    {
        if (!isset(self::$lengthFormatMap[$lengthBytes])) {
            throw new ParserException('Undefined variable length format.');
        }
        $f = self::$lengthFormatMap[$lengthBytes];
        list($len, $bytes) = $this->{$f}();
        return [$this->readAndHashBytes($len), $len + $bytes];
    }

    private function decodeTimestamp()
    {
        list($val, $bytes) = $this->decodeInt64();
        return [
            DateTimeResult::createFromFormat('U.u', $val / 1000),
            $bytes
        ];
    }

    private function decodeUuid()
    {
        $val = unpack('H32', $this->readAndHashBytes(16))[1];
        return [
            substr($val, 0, 8) . '-'
            . substr($val, 8, 4) . '-'
            . substr($val, 12, 4) . '-'
            . substr($val, 16, 4) . '-'
            . substr($val, 20, 12),
            16
        ];
    }
}
