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
 * This ASN.1 universal type contains the calendar date and time.
 *
 * The precision is one minute or one second and optionally a
 * local time differential from coordinated universal time.
 *
 * Decoding of this type will accept the Basic Encoding Rules (BER)
 * The encoding will comply with the Distinguished Encoding Rules (DER).
 */
class UTCTime extends AbstractTime implements Parsable
{
    public function getType()
    {
        return Identifier::UTC_TIME;
    }

    protected function calculateContentLength()
    {
        return 13; // Content is a string o the following format: YYMMDDhhmmssZ (13 octets)
    }

    protected function getEncodedValue()
    {
        return $this->value->format('ymdHis').'Z';
    }

    public static function fromBinary(&$binaryData, &$offsetIndex = 0)
    {
        self::parseIdentifier($binaryData[$offsetIndex], Identifier::UTC_TIME, $offsetIndex++);
        $contentLength = self::parseContentLength($binaryData, $offsetIndex, 11);

        $format = 'ymdGi';
        $dateTimeString = substr($binaryData, $offsetIndex, 10);
        $offsetIndex += 10;

        // extract optional seconds part
        if ($binaryData[$offsetIndex] != 'Z'
        && $binaryData[$offsetIndex] != '+'
        && $binaryData[$offsetIndex] != '-') {
            $dateTimeString .= substr($binaryData, $offsetIndex, 2);
            $offsetIndex += 2;
            $format .= 's';
        }

        $dateTime = \DateTime::createFromFormat($format, $dateTimeString, new \DateTimeZone('UTC'));

        // extract time zone settings
        if ($binaryData[$offsetIndex] == '+'
        || $binaryData[$offsetIndex] == '-') {
            $dateTime = static::extractTimeZoneData($binaryData, $offsetIndex, $dateTime);
        } elseif ($binaryData[$offsetIndex++] != 'Z') {
            throw new ParserException('Invalid UTC String', $offsetIndex);
        }

        $parsedObject = new self($dateTime);
        $parsedObject->setContentLength($contentLength);

        return $parsedObject;
    }
}
