<?php

/*
 * This file is part of SwiftMailer.
 * (c) 2004-2009 Chris Corbyn
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Handles Quoted Printable (QP) Transfer Encoding in Swift Mailer.
 *
 * @author     Chris Corbyn
 */
class Swift_Mime_ContentEncoder_QpContentEncoder extends Swift_Encoder_QpEncoder implements Swift_Mime_ContentEncoder
{
    protected $dotEscape;

    /**
     * Creates a new QpContentEncoder for the given CharacterStream.
     *
     * @param Swift_CharacterStream $charStream to use for reading characters
     * @param Swift_StreamFilter    $filter     if canonicalization should occur
     * @param bool                  $dotEscape  if dot stuffing workaround must be enabled
     */
    public function __construct(Swift_CharacterStream $charStream, Swift_StreamFilter $filter = null, $dotEscape = false)
    {
        $this->dotEscape = $dotEscape;
        parent::__construct($charStream, $filter);
    }

    public function __sleep()
    {
        return ['charStream', 'filter', 'dotEscape'];
    }

    protected function getSafeMapShareId()
    {
        return static::class.($this->dotEscape ? '.dotEscape' : '');
    }

    protected function initSafeMap()
    {
        parent::initSafeMap();
        if ($this->dotEscape) {
            /* Encode . as =2e for buggy remote servers */
            unset($this->safeMap[0x2e]);
        }
    }

    /**
     * Encode stream $in to stream $out.
     *
     * QP encoded strings have a maximum line length of 76 characters.
     * If the first line needs to be shorter, indicate the difference with
     * $firstLineOffset.
     *
     * @param Swift_OutputByteStream $os              output stream
     * @param Swift_InputByteStream  $is              input stream
     * @param int                    $firstLineOffset
     * @param int                    $maxLineLength
     */
    public function encodeByteStream(Swift_OutputByteStream $os, Swift_InputByteStream $is, $firstLineOffset = 0, $maxLineLength = 0)
    {
        if ($maxLineLength > 76 || $maxLineLength <= 0) {
            $maxLineLength = 76;
        }

        $thisLineLength = $maxLineLength - $firstLineOffset;

        $this->charStream->flushContents();
        $this->charStream->importByteStream($os);

        $currentLine = '';
        $prepend = '';
        $size = $lineLen = 0;

        while (false !== $bytes = $this->nextSequence()) {
            // If we're filtering the input
            if (isset($this->filter)) {
                // If we can't filter because we need more bytes
                while ($this->filter->shouldBuffer($bytes)) {
                    // Then collect bytes into the buffer
                    if (false === $moreBytes = $this->nextSequence(1)) {
                        break;
                    }

                    foreach ($moreBytes as $b) {
                        $bytes[] = $b;
                    }
                }
                // And filter them
                $bytes = $this->filter->filter($bytes);
            }

            $enc = $this->encodeByteSequence($bytes, $size);

            $i = strpos($enc, '=0D=0A');
            $newLineLength = $lineLen + (false === $i ? $size : $i);

            if ($currentLine && $newLineLength >= $thisLineLength) {
                $is->write($prepend.$this->standardize($currentLine));
                $currentLine = '';
                $prepend = "=\r\n";
                $thisLineLength = $maxLineLength;
                $lineLen = 0;
            }

            $currentLine .= $enc;

            if (false === $i) {
                $lineLen += $size;
            } else {
                // 6 is the length of '=0D=0A'.
                $lineLen = $size - strrpos($enc, '=0D=0A') - 6;
            }
        }
        if (\strlen($currentLine)) {
            $is->write($prepend.$this->standardize($currentLine));
        }
    }

    /**
     * Get the name of this encoding scheme.
     * Returns the string 'quoted-printable'.
     *
     * @return string
     */
    public function getName()
    {
        return 'quoted-printable';
    }
}
