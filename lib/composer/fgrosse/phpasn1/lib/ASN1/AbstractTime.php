<?php
/*
 * This file is part of the PHPASN1 library.
 *
 * Copyright © Friedrich Große <friedrich.grosse@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FG\ASN1;

use DateInterval;
use DateTime;
use DateTimeZone;
use Exception;

abstract class AbstractTime extends ASNObject
{
    /** @var DateTime */
    protected $value;

    public function __construct($dateTime = null, $dateTimeZone = 'UTC')
    {
        if ($dateTime == null || is_string($dateTime)) {
            $timeZone = new DateTimeZone($dateTimeZone);
            $dateTimeObject = new DateTime($dateTime, $timeZone);
            if ($dateTimeObject == false) {
                $errorMessage = $this->getLastDateTimeErrors();
                $className = Identifier::getName($this->getType());
                throw new Exception(sprintf("Could not create %s from date time string '%s': %s", $className, $dateTime, $errorMessage));
            }
            $dateTime = $dateTimeObject;
        } elseif (!$dateTime instanceof DateTime) {
            throw new Exception('Invalid first argument for some instance of AbstractTime constructor');
        }

        $this->value = $dateTime;
    }

    public function getContent()
    {
        return $this->value;
    }

    protected function getLastDateTimeErrors()
    {
        $messages = '';
        $lastErrors = DateTime::getLastErrors();
        foreach ($lastErrors['errors'] as $errorMessage) {
            $messages .= "{$errorMessage}, ";
        }

        return substr($messages, 0, -2);
    }

    public function __toString()
    {
        return $this->value->format("Y-m-d\tH:i:s");
    }

    protected static function extractTimeZoneData(&$binaryData, &$offsetIndex, DateTime $dateTime)
    {
        $sign = $binaryData[$offsetIndex++];
        $timeOffsetHours   = intval(substr($binaryData, $offsetIndex, 2));
        $timeOffsetMinutes = intval(substr($binaryData, $offsetIndex + 2, 2));
        $offsetIndex += 4;

        $interval = new DateInterval("PT{$timeOffsetHours}H{$timeOffsetMinutes}M");
        if ($sign == '+') {
            $dateTime->sub($interval);
        } else {
            $dateTime->add($interval);
        }

        return $dateTime;
    }
}
