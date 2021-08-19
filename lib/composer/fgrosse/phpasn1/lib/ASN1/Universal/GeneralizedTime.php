<?php
/*
 * This file is part of the PHPASN1 library.
 *
 * Copyright © Friedrich Große <friedrich.grosse@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FG\ASN1\Universal;

use FG\ASN1\AbstractTime;
use FG\ASN1\Parsable;
use FG\ASN1\Identifier;
use FG\ASN1\Exception\ParserException;

/**
 * This ASN.1 universal type contains date and time information according to ISO 8601.
 *
 * The type consists of values representing:
 * a) a calendar date, as defined in ISO 8601; and
 * b) a time of day, to any of the precisions defined in ISO 8601, except for the hours value 24 which shall not be used; and
 * c) the local time differential factor as defined in ISO 8601.
 *
 * Decoding of this type will accept the Basic Encoding Rules (BER)
 * The encoding will comply with the Distinguished Encoding Rules (DER).
 */
class GeneralizedTime extends AbstractTime implements Parsable
{
    private $microseconds;

    public function __construct($dateTime = null, $dateTimeZone = 'UTC')
    {
        parent::__construct($dateTime, $dateTimeZone);
        $this->microseconds = $this->value->format('u');
        if ($this->containsFractionalSecondsElement()) {
            // DER requires us to remove trailing zeros
            $this->microseconds = preg_replace('/([1-9]+)0+$/', '$1', $this->microseconds);
        }
    }

    public function getType()
    {
        return Identifier::GENERALIZED_TIME;
    }

    protected function calculateContentLength()
    {
        $contentSize = 15; // YYYYMMDDHHmmSSZ

        if ($this->containsFractionalSecondsElement()) {
            $contentSize += 1 + strlen($this->microseconds);
        }

        return $contentSize;
    }

    public function containsFractionalSecondsElement()
    {
        return intval($this->microseconds) > 0;
    }

    protected function getEncodedValue()
    {
        $encodedContent = $this->value->format('YmdHis');
        if ($this->containsFractionalSecondsElement()) {
            $encodedContent .= ".{$this->microseconds}";
        }

        return $encodedContent.'Z';
    }

    public function __toString()
    {
        if ($this->containsFractionalSecondsElement()) {
            return $this->value->format("Y-m-d\tH:i:s.uP");
        } else {
            return $this->value->format("Y-m-d\tH:i:sP");
        }
    }

    public static function fromBinary(&$binaryData, &$offsetIndex = 0)
    {
        self::parseIdentifier($binaryData[$offsetIndex], Identifier::GENERALIZED_TIME, $offsetIndex++);
        $lengthOfMinimumTimeString = 14; // YYYYMMDDHHmmSS
        $contentLength = self::parseContentLength($binaryData, $offsetIndex, $lengthOfMinimumTimeString);
        $maximumBytesToRead = $contentLength;

        $format = 'YmdGis';
        $content = substr($binaryData, $offsetIndex, $contentLength);
        $dateTimeString = substr($content, 0, $lengthOfMinimumTimeString);
        $offsetIndex += $lengthOfMinimumTimeString;
        $maximumBytesToRead -= $lengthOfMinimumTimeString;

        if ($contentLength == $lengthOfMinimumTimeString) {
            $localTimeZone = new \DateTimeZone(date_default_timezone_get());
            $dateTime = \DateTime::createFromFormat($format, $dateTimeString, $localTimeZone);
        } else {
            if ($binaryData[$offsetIndex] == '.') {
                $maximumBytesToRead--; // account for the '.'
                $nrOfFractionalSecondElements = 1; // account for the '.'

                while ($maximumBytesToRead > 0
                      && $binaryData[$offsetIndex + $nrOfFractionalSecondElements] != '+'
                      && $binaryData[$offsetIndex + $nrOfFractionalSecondElements] != '-'
                      && $binaryData[$offsetIndex + $nrOfFractionalSecondElements] != 'Z') {
                    $nrOfFractionalSecondElements++;
                    $maximumBytesToRead--;
                }

                $dateTimeString .= substr($binaryData, $offsetIndex, $nrOfFractionalSecondElements);
                $offsetIndex += $nrOfFractionalSecondElements;
                $format .= '.u';
            }

            $dateTime = \DateTime::createFromFormat($format, $dateTimeString, new \DateTimeZone('UTC'));

            if ($maximumBytesToRead > 0) {
                if ($binaryData[$offsetIndex] == '+'
                || $binaryData[$offsetIndex] == '-') {
                    $dateTime = static::extractTimeZoneData($binaryData, $offsetIndex, $dateTime);
                } elseif ($binaryData[$offsetIndex++] != 'Z') {
                    throw new ParserException('Invalid ISO 8601 Time String', $offsetIndex);
                }
            }
        }

        $parsedObject = new self($dateTime);
        $parsedObject->setContentLength($contentLength);

        return $parsedObject;
    }
}
