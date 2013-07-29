<?php

namespace Guzzle\Http;

use Guzzle\Common\Exception\RuntimeException;

/**
 * EntityBody decorator that can cache previously read bytes from a sequentially read tstream
 */
class CachingEntityBody extends AbstractEntityBodyDecorator
{
    /** @var EntityBody Remote stream used to actually pull data onto the buffer */
    protected $remoteStream;

    /** @var int The number of bytes to skip reading due to a write on the temporary buffer */
    protected $skipReadBytes = 0;

    /**
     * We will treat the buffer object as the body of the entity body
     * {@inheritdoc}
     */
    public function __construct(EntityBodyInterface $body)
    {
        $this->remoteStream = $body;
        $this->body = new EntityBody(fopen('php://temp', 'r+'));
    }

    /**
     * Will give the contents of the buffer followed by the exhausted remote stream.
     *
     * Warning: Loads the entire stream into memory
     *
     * @return string
     */
    public function __toString()
    {
        $pos = $this->ftell();
        $this->rewind();

        $str = '';
        while (!$this->isConsumed()) {
            $str .= $this->read(16384);
        }

        $this->seek($pos);

        return $str;
    }

    public function getSize()
    {
        return max($this->body->getSize(), $this->remoteStream->getSize());
    }

    /**
     * {@inheritdoc}
     * @throws RuntimeException When seeking with SEEK_END or when seeking past the total size of the buffer stream
     */
    public function seek($offset, $whence = SEEK_SET)
    {
        if ($whence == SEEK_SET) {
            $byte = $offset;
        } elseif ($whence == SEEK_CUR) {
            $byte = $offset + $this->ftell();
        } else {
            throw new RuntimeException(__CLASS__ . ' supports only SEEK_SET and SEEK_CUR seek operations');
        }

        // You cannot skip ahead past where you've read from the remote stream
        if ($byte > $this->body->getSize()) {
            throw new RuntimeException(
                "Cannot seek to byte {$byte} when the buffered stream only contains {$this->body->getSize()} bytes"
            );
        }

        return $this->body->seek($byte);
    }

    public function rewind()
    {
        return $this->seek(0);
    }

    /**
     * Does not support custom rewind functions
     *
     * @throws RuntimeException
     */
    public function setRewindFunction($callable)
    {
        throw new RuntimeException(__CLASS__ . ' does not support custom stream rewind functions');
    }

    public function read($length)
    {
        // Perform a regular read on any previously read data from the buffer
        $data = $this->body->read($length);
        $remaining = $length - strlen($data);

        // More data was requested so read from the remote stream
        if ($remaining) {
            // If data was written to the buffer in a position that would have been filled from the remote stream,
            // then we must skip bytes on the remote stream to emulate overwriting bytes from that position. This
            // mimics the behavior of other PHP stream wrappers.
            $remoteData = $this->remoteStream->read($remaining + $this->skipReadBytes);

            if ($this->skipReadBytes) {
                $len = strlen($remoteData);
                $remoteData = substr($remoteData, $this->skipReadBytes);
                $this->skipReadBytes = max(0, $this->skipReadBytes - $len);
            }

            $data .= $remoteData;
            $this->body->write($remoteData);
        }

        return $data;
    }

    public function write($string)
    {
        // When appending to the end of the currently read stream, you'll want to skip bytes from being read from
        // the remote stream to emulate other stream wrappers. Basically replacing bytes of data of a fixed length.
        $overflow = (strlen($string) + $this->ftell()) - $this->remoteStream->ftell();
        if ($overflow > 0) {
            $this->skipReadBytes += $overflow;
        }

        return $this->body->write($string);
    }

    /**
     * {@inheritdoc}
     * @link http://php.net/manual/en/function.fgets.php
     */
    public function readLine($maxLength = null)
    {
        $buffer = '';
        $size = 0;
        while (!$this->isConsumed()) {
            $byte = $this->read(1);
            $buffer .= $byte;
            // Break when a new line is found or the max length - 1 is reached
            if ($byte == PHP_EOL || ++$size == $maxLength - 1) {
                break;
            }
        }

        return $buffer;
    }

    public function isConsumed()
    {
        return $this->body->isConsumed() && $this->remoteStream->isConsumed();
    }

    /**
     * Close both the remote stream and buffer stream
     */
    public function close()
    {
        return $this->remoteStream->close() && $this->body->close();
    }

    public function setStream($stream, $size = 0)
    {
        $this->remoteStream->setStream($stream, $size);
    }

    public function getContentType()
    {
        return $this->remoteStream->getContentType();
    }

    public function getContentEncoding()
    {
        return $this->remoteStream->getContentEncoding();
    }

    public function getMetaData($key = null)
    {
        return $this->remoteStream->getMetaData($key);
    }

    public function getStream()
    {
        return $this->remoteStream->getStream();
    }

    public function getWrapper()
    {
        return $this->remoteStream->getWrapper();
    }

    public function getWrapperData()
    {
        return $this->remoteStream->getWrapperData();
    }

    public function getStreamType()
    {
        return $this->remoteStream->getStreamType();
    }

    public function getUri()
    {
        return $this->remoteStream->getUri();
    }

    /**
     * Always retrieve custom data from the remote stream
     * {@inheritdoc}
     */
    public function getCustomData($key)
    {
        return $this->remoteStream->getCustomData($key);
    }

    /**
     * Always set custom data on the remote stream
     * {@inheritdoc}
     */
    public function setCustomData($key, $value)
    {
        $this->remoteStream->setCustomData($key, $value);

        return $this;
    }
}
