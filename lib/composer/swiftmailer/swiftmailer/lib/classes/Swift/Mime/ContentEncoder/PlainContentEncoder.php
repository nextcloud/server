<?php

/*
 * This file is part of SwiftMailer.
 * (c) 2004-2009 Chris Corbyn
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Handles binary/7/8-bit Transfer Encoding in Swift Mailer.
 *
 * When sending 8-bit content over SMTP, you should use
 * Swift_Transport_Esmtp_EightBitMimeHandler to enable the 8BITMIME SMTP
 * extension.
 *
 * @author Chris Corbyn
 */
class Swift_Mime_ContentEncoder_PlainContentEncoder implements Swift_Mime_ContentEncoder
{
    /**
     * The name of this encoding scheme (probably 7bit or 8bit).
     *
     * @var string
     */
    private $name;

    /**
     * True if canonical transformations should be done.
     *
     * @var bool
     */
    private $canonical;

    /**
     * Creates a new PlainContentEncoder with $name (probably 7bit or 8bit).
     *
     * @param string $name
     * @param bool   $canonical if canonicalization transformation should be done
     */
    public function __construct($name, $canonical = false)
    {
        $this->name = $name;
        $this->canonical = $canonical;
    }

    /**
     * Encode a given string to produce an encoded string.
     *
     * @param string $string
     * @param int    $firstLineOffset ignored
     * @param int    $maxLineLength   - 0 means no wrapping will occur
     *
     * @return string
     */
    public function encodeString($string, $firstLineOffset = 0, $maxLineLength = 0)
    {
        if ($this->canonical) {
            $string = $this->canonicalize($string);
        }

        return $this->safeWordwrap($string, $maxLineLength, "\r\n");
    }

    /**
     * Encode stream $in to stream $out.
     *
     * @param int $firstLineOffset ignored
     * @param int $maxLineLength   optional, 0 means no wrapping will occur
     */
    public function encodeByteStream(Swift_OutputByteStream $os, Swift_InputByteStream $is, $firstLineOffset = 0, $maxLineLength = 0)
    {
        $leftOver = '';
        while (false !== $bytes = $os->read(8192)) {
            $toencode = $leftOver.$bytes;
            if ($this->canonical) {
                $toencode = $this->canonicalize($toencode);
            }
            $wrapped = $this->safeWordwrap($toencode, $maxLineLength, "\r\n");
            $lastLinePos = strrpos($wrapped, "\r\n");
            $leftOver = substr($wrapped, $lastLinePos);
            $wrapped = substr($wrapped, 0, $lastLinePos);

            $is->write($wrapped);
        }
        if (\strlen($leftOver)) {
            $is->write($leftOver);
        }
    }

    /**
     * Get the name of this encoding scheme.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Not used.
     */
    public function charsetChanged($charset)
    {
    }

    /**
     * A safer (but weaker) wordwrap for unicode.
     *
     * @param string $string
     * @param int    $length
     * @param string $le
     *
     * @return string
     */
    private function safeWordwrap($string, $length = 75, $le = "\r\n")
    {
        if (0 >= $length) {
            return $string;
        }

        $originalLines = explode($le, $string);

        $lines = [];
        $lineCount = 0;

        foreach ($originalLines as $originalLine) {
            $lines[] = '';
            $currentLine = &$lines[$lineCount++];

            //$chunks = preg_split('/(?<=[\ \t,\.!\?\-&\+\/])/', $originalLine);
            $chunks = preg_split('/(?<=\s)/', $originalLine);

            foreach ($chunks as $chunk) {
                if (0 != \strlen($currentLine)
                    && \strlen($currentLine.$chunk) > $length) {
                    $lines[] = '';
                    $currentLine = &$lines[$lineCount++];
                }
                $currentLine .= $chunk;
            }
        }

        return implode("\r\n", $lines);
    }

    /**
     * Canonicalize string input (fix CRLF).
     *
     * @param string $string
     *
     * @return string
     */
    private function canonicalize($string)
    {
        return str_replace(
            ["\r\n", "\r", "\n"],
            ["\n", "\n", "\r\n"],
            $string
            );
    }
}
