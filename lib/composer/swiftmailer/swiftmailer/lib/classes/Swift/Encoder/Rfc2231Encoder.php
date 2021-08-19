<?php

/*
 * This file is part of SwiftMailer.
 * (c) 2004-2009 Chris Corbyn
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Handles RFC 2231 specified Encoding in Swift Mailer.
 *
 * @author Chris Corbyn
 */
class Swift_Encoder_Rfc2231Encoder implements Swift_Encoder
{
    /**
     * A character stream to use when reading a string as characters instead of bytes.
     *
     * @var Swift_CharacterStream
     */
    private $charStream;

    /**
     * Creates a new Rfc2231Encoder using the given character stream instance.
     */
    public function __construct(Swift_CharacterStream $charStream)
    {
        $this->charStream = $charStream;
    }

    /**
     * Takes an unencoded string and produces a string encoded according to
     * RFC 2231 from it.
     *
     * @param string $string
     * @param int    $firstLineOffset
     * @param int    $maxLineLength   optional, 0 indicates the default of 75 bytes
     *
     * @return string
     */
    public function encodeString($string, $firstLineOffset = 0, $maxLineLength = 0)
    {
        $lines = [];
        $lineCount = 0;
        $lines[] = '';
        $currentLine = &$lines[$lineCount++];

        if (0 >= $maxLineLength) {
            $maxLineLength = 75;
        }

        $this->charStream->flushContents();
        $this->charStream->importString($string);

        $thisLineLength = $maxLineLength - $firstLineOffset;

        while (false !== $char = $this->charStream->read(4)) {
            $encodedChar = rawurlencode($char);
            if (0 != \strlen($currentLine)
                && \strlen($currentLine.$encodedChar) > $thisLineLength) {
                $lines[] = '';
                $currentLine = &$lines[$lineCount++];
                $thisLineLength = $maxLineLength;
            }
            $currentLine .= $encodedChar;
        }

        return implode("\r\n", $lines);
    }

    /**
     * Updates the charset used.
     *
     * @param string $charset
     */
    public function charsetChanged($charset)
    {
        $this->charStream->setCharacterSet($charset);
    }

    /**
     * Make a deep copy of object.
     */
    public function __clone()
    {
        $this->charStream = clone $this->charStream;
    }
}
