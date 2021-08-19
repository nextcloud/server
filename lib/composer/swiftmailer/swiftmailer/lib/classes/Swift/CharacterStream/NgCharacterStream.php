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
 * @author     Xavier De Cock <xdecock@gmail.com>
 */
class Swift_CharacterStream_NgCharacterStream implements Swift_CharacterStream
{
    /**
     * The char reader (lazy-loaded) for the current charset.
     *
     * @var Swift_CharacterReader
     */
    private $charReader;

    /**
     * A factory for creating CharacterReader instances.
     *
     * @var Swift_CharacterReaderFactory
     */
    private $charReaderFactory;

    /**
     * The character set this stream is using.
     *
     * @var string
     */
    private $charset;

    /**
     * The data's stored as-is.
     *
     * @var string
     */
    private $datas = '';

    /**
     * Number of bytes in the stream.
     *
     * @var int
     */
    private $datasSize = 0;

    /**
     * Map.
     *
     * @var mixed
     */
    private $map;

    /**
     * Map Type.
     *
     * @var int
     */
    private $mapType = 0;

    /**
     * Number of characters in the stream.
     *
     * @var int
     */
    private $charCount = 0;

    /**
     * Position in the stream.
     *
     * @var int
     */
    private $currentPos = 0;

    /**
     * Constructor.
     *
     * @param string $charset
     */
    public function __construct(Swift_CharacterReaderFactory $factory, $charset)
    {
        $this->setCharacterReaderFactory($factory);
        $this->setCharacterSet($charset);
    }

    /* -- Changing parameters of the stream -- */

    /**
     * Set the character set used in this CharacterStream.
     *
     * @param string $charset
     */
    public function setCharacterSet($charset)
    {
        $this->charset = $charset;
        $this->charReader = null;
        $this->mapType = 0;
    }

    /**
     * Set the CharacterReaderFactory for multi charset support.
     */
    public function setCharacterReaderFactory(Swift_CharacterReaderFactory $factory)
    {
        $this->charReaderFactory = $factory;
    }

    /**
     * @see Swift_CharacterStream::flushContents()
     */
    public function flushContents()
    {
        $this->datas = null;
        $this->map = null;
        $this->charCount = 0;
        $this->currentPos = 0;
        $this->datasSize = 0;
    }

    /**
     * @see Swift_CharacterStream::importByteStream()
     */
    public function importByteStream(Swift_OutputByteStream $os)
    {
        $this->flushContents();
        $blocks = 512;
        $os->setReadPointer(0);
        while (false !== ($read = $os->read($blocks))) {
            $this->write($read);
        }
    }

    /**
     * @see Swift_CharacterStream::importString()
     *
     * @param string $string
     */
    public function importString($string)
    {
        $this->flushContents();
        $this->write($string);
    }

    /**
     * @see Swift_CharacterStream::read()
     *
     * @param int $length
     *
     * @return string
     */
    public function read($length)
    {
        if ($this->currentPos >= $this->charCount) {
            return false;
        }
        $ret = false;
        $length = ($this->currentPos + $length > $this->charCount) ? $this->charCount - $this->currentPos : $length;
        switch ($this->mapType) {
            case Swift_CharacterReader::MAP_TYPE_FIXED_LEN:
                $len = $length * $this->map;
                $ret = substr($this->datas,
                        $this->currentPos * $this->map,
                        $len);
                $this->currentPos += $length;
                break;

            case Swift_CharacterReader::MAP_TYPE_INVALID:
                $ret = '';
                for (; $this->currentPos < $length; ++$this->currentPos) {
                    if (isset($this->map[$this->currentPos])) {
                        $ret .= '?';
                    } else {
                        $ret .= $this->datas[$this->currentPos];
                    }
                }
                break;

            case Swift_CharacterReader::MAP_TYPE_POSITIONS:
                $end = $this->currentPos + $length;
                $end = $end > $this->charCount ? $this->charCount : $end;
                $ret = '';
                $start = 0;
                if ($this->currentPos > 0) {
                    $start = $this->map['p'][$this->currentPos - 1];
                }
                $to = $start;
                for (; $this->currentPos < $end; ++$this->currentPos) {
                    if (isset($this->map['i'][$this->currentPos])) {
                        $ret .= substr($this->datas, $start, $to - $start).'?';
                        $start = $this->map['p'][$this->currentPos];
                    } else {
                        $to = $this->map['p'][$this->currentPos];
                    }
                }
                $ret .= substr($this->datas, $start, $to - $start);
                break;
        }

        return $ret;
    }

    /**
     * @see Swift_CharacterStream::readBytes()
     *
     * @param int $length
     *
     * @return int[]
     */
    public function readBytes($length)
    {
        $read = $this->read($length);
        if (false !== $read) {
            $ret = array_map('ord', str_split($read, 1));

            return $ret;
        }

        return false;
    }

    /**
     * @see Swift_CharacterStream::setPointer()
     *
     * @param int $charOffset
     */
    public function setPointer($charOffset)
    {
        if ($this->charCount < $charOffset) {
            $charOffset = $this->charCount;
        }
        $this->currentPos = $charOffset;
    }

    /**
     * @see Swift_CharacterStream::write()
     *
     * @param string $chars
     */
    public function write($chars)
    {
        if (!isset($this->charReader)) {
            $this->charReader = $this->charReaderFactory->getReaderFor(
                $this->charset);
            $this->map = [];
            $this->mapType = $this->charReader->getMapType();
        }
        $ignored = '';
        $this->datas .= $chars;
        $this->charCount += $this->charReader->getCharPositions(substr($this->datas, $this->datasSize), $this->datasSize, $this->map, $ignored);
        if (false !== $ignored) {
            $this->datasSize = \strlen($this->datas) - \strlen($ignored);
        } else {
            $this->datasSize = \strlen($this->datas);
        }
    }
}
