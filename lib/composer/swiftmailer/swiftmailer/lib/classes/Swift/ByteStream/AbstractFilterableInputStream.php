<?php

/*
 * This file is part of SwiftMailer.
 * (c) 2004-2009 Chris Corbyn
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Provides the base functionality for an InputStream supporting filters.
 *
 * @author Chris Corbyn
 */
abstract class Swift_ByteStream_AbstractFilterableInputStream implements Swift_InputByteStream, Swift_Filterable
{
    /**
     * Write sequence.
     */
    protected $sequence = 0;

    /**
     * StreamFilters.
     *
     * @var Swift_StreamFilter[]
     */
    private $filters = [];

    /**
     * A buffer for writing.
     */
    private $writeBuffer = '';

    /**
     * Bound streams.
     *
     * @var Swift_InputByteStream[]
     */
    private $mirrors = [];

    /**
     * Commit the given bytes to the storage medium immediately.
     *
     * @param string $bytes
     */
    abstract protected function doCommit($bytes);

    /**
     * Flush any buffers/content with immediate effect.
     */
    abstract protected function flush();

    /**
     * Add a StreamFilter to this InputByteStream.
     *
     * @param string $key
     */
    public function addFilter(Swift_StreamFilter $filter, $key)
    {
        $this->filters[$key] = $filter;
    }

    /**
     * Remove an already present StreamFilter based on its $key.
     *
     * @param string $key
     */
    public function removeFilter($key)
    {
        unset($this->filters[$key]);
    }

    /**
     * Writes $bytes to the end of the stream.
     *
     * @param string $bytes
     *
     * @throws Swift_IoException
     *
     * @return int
     */
    public function write($bytes)
    {
        $this->writeBuffer .= $bytes;
        foreach ($this->filters as $filter) {
            if ($filter->shouldBuffer($this->writeBuffer)) {
                return;
            }
        }
        $this->doWrite($this->writeBuffer);

        return ++$this->sequence;
    }

    /**
     * For any bytes that are currently buffered inside the stream, force them
     * off the buffer.
     *
     * @throws Swift_IoException
     */
    public function commit()
    {
        $this->doWrite($this->writeBuffer);
    }

    /**
     * Attach $is to this stream.
     *
     * The stream acts as an observer, receiving all data that is written.
     * All {@link write()} and {@link flushBuffers()} operations will be mirrored.
     */
    public function bind(Swift_InputByteStream $is)
    {
        $this->mirrors[] = $is;
    }

    /**
     * Remove an already bound stream.
     *
     * If $is is not bound, no errors will be raised.
     * If the stream currently has any buffered data it will be written to $is
     * before unbinding occurs.
     */
    public function unbind(Swift_InputByteStream $is)
    {
        foreach ($this->mirrors as $k => $stream) {
            if ($is === $stream) {
                if ('' !== $this->writeBuffer) {
                    $stream->write($this->writeBuffer);
                }
                unset($this->mirrors[$k]);
            }
        }
    }

    /**
     * Flush the contents of the stream (empty it) and set the internal pointer
     * to the beginning.
     *
     * @throws Swift_IoException
     */
    public function flushBuffers()
    {
        if ('' !== $this->writeBuffer) {
            $this->doWrite($this->writeBuffer);
        }
        $this->flush();

        foreach ($this->mirrors as $stream) {
            $stream->flushBuffers();
        }
    }

    /** Run $bytes through all filters */
    private function filter($bytes)
    {
        foreach ($this->filters as $filter) {
            $bytes = $filter->filter($bytes);
        }

        return $bytes;
    }

    /** Just write the bytes to the stream */
    private function doWrite($bytes)
    {
        $this->doCommit($this->filter($bytes));

        foreach ($this->mirrors as $stream) {
            $stream->write($bytes);
        }

        $this->writeBuffer = '';
    }
}
