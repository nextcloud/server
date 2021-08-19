<?php

/*
 * This file is part of SwiftMailer.
 * (c) 2004-2009 Chris Corbyn
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Allows reading and writing of bytes to and from a file.
 *
 * @author     Chris Corbyn
 */
class Swift_ByteStream_FileByteStream extends Swift_ByteStream_AbstractFilterableInputStream implements Swift_FileStream
{
    /** The internal pointer offset */
    private $offset = 0;

    /** The path to the file */
    private $path;

    /** The mode this file is opened in for writing */
    private $mode;

    /** A lazy-loaded resource handle for reading the file */
    private $reader;

    /** A lazy-loaded resource handle for writing the file */
    private $writer;

    /** If stream is seekable true/false, or null if not known */
    private $seekable = null;

    /**
     * Create a new FileByteStream for $path.
     *
     * @param string $path
     * @param bool   $writable if true
     */
    public function __construct($path, $writable = false)
    {
        if (empty($path)) {
            throw new Swift_IoException('The path cannot be empty');
        }
        $this->path = $path;
        $this->mode = $writable ? 'w+b' : 'rb';
    }

    /**
     * Get the complete path to the file.
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Reads $length bytes from the stream into a string and moves the pointer
     * through the stream by $length.
     *
     * If less bytes exist than are requested the
     * remaining bytes are given instead. If no bytes are remaining at all, boolean
     * false is returned.
     *
     * @param int $length
     *
     * @return string|bool
     *
     * @throws Swift_IoException
     */
    public function read($length)
    {
        $fp = $this->getReadHandle();
        if (!feof($fp)) {
            $bytes = fread($fp, $length);
            $this->offset = ftell($fp);

            // If we read one byte after reaching the end of the file
            // feof() will return false and an empty string is returned
            if ((false === $bytes || '' === $bytes) && feof($fp)) {
                $this->resetReadHandle();

                return false;
            }

            return $bytes;
        }

        $this->resetReadHandle();

        return false;
    }

    /**
     * Move the internal read pointer to $byteOffset in the stream.
     *
     * @param int $byteOffset
     *
     * @return bool
     */
    public function setReadPointer($byteOffset)
    {
        if (isset($this->reader)) {
            $this->seekReadStreamToPosition($byteOffset);
        }
        $this->offset = $byteOffset;
    }

    /** Just write the bytes to the file */
    protected function doCommit($bytes)
    {
        fwrite($this->getWriteHandle(), $bytes);
        $this->resetReadHandle();
    }

    /** Not used */
    protected function flush()
    {
    }

    /** Get the resource for reading */
    private function getReadHandle()
    {
        if (!isset($this->reader)) {
            $pointer = @fopen($this->path, 'rb');
            if (!$pointer) {
                throw new Swift_IoException('Unable to open file for reading ['.$this->path.']');
            }
            $this->reader = $pointer;
            if (0 != $this->offset) {
                $this->getReadStreamSeekableStatus();
                $this->seekReadStreamToPosition($this->offset);
            }
        }

        return $this->reader;
    }

    /** Get the resource for writing */
    private function getWriteHandle()
    {
        if (!isset($this->writer)) {
            if (!$this->writer = fopen($this->path, $this->mode)) {
                throw new Swift_IoException('Unable to open file for writing ['.$this->path.']');
            }
        }

        return $this->writer;
    }

    /** Force a reload of the resource for reading */
    private function resetReadHandle()
    {
        if (isset($this->reader)) {
            fclose($this->reader);
            $this->reader = null;
        }
    }

    /** Check if ReadOnly Stream is seekable */
    private function getReadStreamSeekableStatus()
    {
        $metas = stream_get_meta_data($this->reader);
        $this->seekable = $metas['seekable'];
    }

    /** Streams in a readOnly stream ensuring copy if needed */
    private function seekReadStreamToPosition($offset)
    {
        if (null === $this->seekable) {
            $this->getReadStreamSeekableStatus();
        }
        if (false === $this->seekable) {
            $currentPos = ftell($this->reader);
            if ($currentPos < $offset) {
                $toDiscard = $offset - $currentPos;
                fread($this->reader, $toDiscard);

                return;
            }
            $this->copyReadStream();
        }
        fseek($this->reader, $offset, SEEK_SET);
    }

    /** Copy a readOnly Stream to ensure seekability */
    private function copyReadStream()
    {
        if ($tmpFile = fopen('php://temp/maxmemory:4096', 'w+b')) {
            /* We have opened a php:// Stream Should work without problem */
        } elseif (\function_exists('sys_get_temp_dir') && is_writable(sys_get_temp_dir()) && ($tmpFile = tmpfile())) {
            /* We have opened a tmpfile */
        } else {
            throw new Swift_IoException('Unable to copy the file to make it seekable, sys_temp_dir is not writable, php://memory not available');
        }
        $currentPos = ftell($this->reader);
        fclose($this->reader);
        $source = fopen($this->path, 'rb');
        if (!$source) {
            throw new Swift_IoException('Unable to open file for copying ['.$this->path.']');
        }
        fseek($tmpFile, 0, SEEK_SET);
        while (!feof($source)) {
            fwrite($tmpFile, fread($source, 4096));
        }
        fseek($tmpFile, $currentPos, SEEK_SET);
        fclose($source);
        $this->reader = $tmpFile;
    }
}
