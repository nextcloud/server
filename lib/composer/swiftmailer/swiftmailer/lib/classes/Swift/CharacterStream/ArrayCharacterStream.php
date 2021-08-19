<?php

/*
 * This file is part of SwiftMailer.
 * (c) 2004-2009 Chris Corbyn
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * A CharacterStream implementation which stores characters in an internal array.
 *
 * @author Chris Corbyn
 */
class Swift_CharacterStream_ArrayCharacterStream implements Swift_CharacterStream
{
    /** A map of byte values and their respective characters */
    private static $charMap;

    /** A map of characters and their derivative byte values */
    private static $byteMap;

    /** The char reader (lazy-loaded) for the current charset */
    private $charReader;

    /** A factory for creating CharacterReader instances */
    private $charReaderFactory;

    /** The character set this stream is using */
    private $charset;

    /** Array of characters */
    private $array = [];

    /** Size of the array of character */
    private $array_size = [];

    /** The current character offset in the stream */
    private $offset = 0;

    /**
     * Create a new CharacterStream with the given $chars, if set.
     *
     * @param Swift_CharacterReaderFactory $factory for loading validators
     * @param string                       $charset used in the stream
     */
    public function __construct(Swift_CharacterReaderFactory $factory, $charset)
    {
        self::initializeMaps();
        $this->setCharacterReaderFactory($factory);
        $this->setCharacterSet($charset);
    }

    /**
     * Set the character set used in this CharacterStream.
     *
     * @param string $charset
     */
    public function setCharacterSet($charset)
    {
        $this->charset = $charset;
        $this->charReader = null;
    }

    /**
     * Set the CharacterReaderFactory for multi charset support.
     */
    public function setCharacterReaderFactory(Swift_CharacterReaderFactory $factory)
    {
        $this->charReaderFactory = $factory;
    }

    /**
     * Overwrite this character stream using the byte sequence in the byte stream.
     *
     * @param Swift_OutputByteStream $os output stream to read from
     */
    public function importByteStream(Swift_OutputByteStream $os)
    {
        if (!isset($this->charReader)) {
            $this->charReader = $this->charReaderFactory
                ->getReaderFor($this->charset);
        }

        $startLength = $this->charReader->getInitialByteSize();
        while (false !== $bytes = $os->read($startLength)) {
            $c = [];
            for ($i = 0, $len = \strlen($bytes); $i < $len; ++$i) {
                $c[] = self::$byteMap[$bytes[$i]];
            }
            $size = \count($c);
            $need = $this->charReader
                ->validateByteSequence($c, $size);
            if ($need > 0 &&
                false !== $bytes = $os->read($need)) {
                for ($i = 0, $len = \strlen($bytes); $i < $len; ++$i) {
                    $c[] = self::$byteMap[$bytes[$i]];
                }
            }
            $this->array[] = $c;
            ++$this->array_size;
        }
    }

    /**
     * Import a string a bytes into this CharacterStream, overwriting any existing
     * data in the stream.
     *
     * @param string $string
     */
    public function importString($string)
    {
        $this->flushContents();
        $this->write($string);
    }

    /**
     * Read $length characters from the stream and move the internal pointer
     * $length further into the stream.
     *
     * @param int $length
     *
     * @return string
     */
    public function read($length)
    {
        if ($this->offset == $this->array_size) {
            return false;
        }

        // Don't use array slice
        $arrays = [];
        $end = $length + $this->offset;
        for ($i = $this->offset; $i < $end; ++$i) {
            if (!isset($this->array[$i])) {
                break;
            }
            $arrays[] = $this->array[$i];
        }
        $this->offset += $i - $this->offset; // Limit function calls
        $chars = false;
        foreach ($arrays as $array) {
            $chars .= implode('', array_map('chr', $array));
        }

        return $chars;
    }

    /**
     * Read $length characters from the stream and return a 1-dimensional array
     * containing there octet values.
     *
     * @param int $length
     *
     * @return int[]
     */
    public function readBytes($length)
    {
        if ($this->offset == $this->array_size) {
            return false;
        }
        $arrays = [];
        $end = $length + $this->offset;
        for ($i = $this->offset; $i < $end; ++$i) {
            if (!isset($this->array[$i])) {
                break;
            }
            $arrays[] = $this->array[$i];
        }
        $this->offset += ($i - $this->offset); // Limit function calls

        return array_merge(...$arrays);
    }

    /**
     * Write $chars to the end of the stream.
     *
     * @param string $chars
     */
    public function write($chars)
    {
        if (!isset($this->charReader)) {
            $this->charReader = $this->charReaderFactory->getReaderFor(
                $this->charset);
        }

        $startLength = $this->charReader->getInitialByteSize();

        $fp = fopen('php://memory', 'w+b');
        fwrite($fp, $chars);
        unset($chars);
        fseek($fp, 0, SEEK_SET);

        $buffer = [0];
        $buf_pos = 1;
        $buf_len = 1;
        $has_datas = true;
        do {
            $bytes = [];
            // Buffer Filing
            if ($buf_len - $buf_pos < $startLength) {
                $buf = array_splice($buffer, $buf_pos);
                $new = $this->reloadBuffer($fp, 100);
                if ($new) {
                    $buffer = array_merge($buf, $new);
                    $buf_len = \count($buffer);
                    $buf_pos = 0;
                } else {
                    $has_datas = false;
                }
            }
            if ($buf_len - $buf_pos > 0) {
                $size = 0;
                for ($i = 0; $i < $startLength && isset($buffer[$buf_pos]); ++$i) {
                    ++$size;
                    $bytes[] = $buffer[$buf_pos++];
                }
                $need = $this->charReader->validateByteSequence(
                    $bytes, $size);
                if ($need > 0) {
                    if ($buf_len - $buf_pos < $need) {
                        $new = $this->reloadBuffer($fp, $need);

                        if ($new) {
                            $buffer = array_merge($buffer, $new);
                            $buf_len = \count($buffer);
                        }
                    }
                    for ($i = 0; $i < $need && isset($buffer[$buf_pos]); ++$i) {
                        $bytes[] = $buffer[$buf_pos++];
                    }
                }
                $this->array[] = $bytes;
                ++$this->array_size;
            }
        } while ($has_datas);

        fclose($fp);
    }

    /**
     * Move the internal pointer to $charOffset in the stream.
     *
     * @param int $charOffset
     */
    public function setPointer($charOffset)
    {
        if ($charOffset > $this->array_size) {
            $charOffset = $this->array_size;
        } elseif ($charOffset < 0) {
            $charOffset = 0;
        }
        $this->offset = $charOffset;
    }

    /**
     * Empty the stream and reset the internal pointer.
     */
    public function flushContents()
    {
        $this->offset = 0;
        $this->array = [];
        $this->array_size = 0;
    }

    private function reloadBuffer($fp, $len)
    {
        if (!feof($fp) && false !== ($bytes = fread($fp, $len))) {
            $buf = [];
            for ($i = 0, $len = \strlen($bytes); $i < $len; ++$i) {
                $buf[] = self::$byteMap[$bytes[$i]];
            }

            return $buf;
        }

        return false;
    }

    private static function initializeMaps()
    {
        if (!isset(self::$charMap)) {
            self::$charMap = [];
            for ($byte = 0; $byte < 256; ++$byte) {
                self::$charMap[$byte] = \chr($byte);
            }
            self::$byteMap = array_flip(self::$charMap);
        }
    }
}
