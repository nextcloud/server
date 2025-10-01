<?php

namespace Sabre\VObject\Property\VCard;

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use Sabre\VObject\DateTimeParser;
use Sabre\VObject\InvalidDataException;
use Sabre\VObject\Property;
use Sabre\Xml;

/**
 * DateAndOrTime property.
 *
 * This object encodes DATE-AND-OR-TIME values.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class DateAndOrTime extends Property
{
    /**
     * Field separator.
     *
     * @var string
     */
    public $delimiter = '';

    /**
     * Returns the type of value.
     *
     * This corresponds to the VALUE= parameter. Every property also has a
     * 'default' valueType.
     *
     * @return string
     */
    public function getValueType()
    {
        return 'DATE-AND-OR-TIME';
    }

    /**
     * Sets a multi-valued property.
     *
     * You may also specify DateTimeInterface objects here.
     */
    public function setParts(array $parts)
    {
        if (count($parts) > 1) {
            throw new \InvalidArgumentException('Only one value allowed');
        }
        if (isset($parts[0]) && $parts[0] instanceof DateTimeInterface) {
            $this->setDateTime($parts[0]);
        } else {
            parent::setParts($parts);
        }
    }

    /**
     * Updates the current value.
     *
     * This may be either a single, or multiple strings in an array.
     *
     * Instead of strings, you may also use DateTimeInterface here.
     *
     * @param string|array|DateTimeInterface $value
     */
    public function setValue($value)
    {
        if ($value instanceof DateTimeInterface) {
            $this->setDateTime($value);
        } else {
            parent::setValue($value);
        }
    }

    /**
     * Sets the property as a DateTime object.
     */
    public function setDateTime(DateTimeInterface $dt)
    {
        $tz = $dt->getTimeZone();
        $isUtc = in_array($tz->getName(), ['UTC', 'GMT', 'Z']);

        if ($isUtc) {
            $value = $dt->format('Ymd\\THis\\Z');
        } else {
            // Calculating the offset.
            $value = $dt->format('Ymd\\THisO');
        }

        $this->value = $value;
    }

    /**
     * Returns a date-time value.
     *
     * Note that if this property contained more than 1 date-time, only the
     * first will be returned. To get an array with multiple values, call
     * getDateTimes.
     *
     * If no time was specified, we will always use midnight (in the default
     * timezone) as the time.
     *
     * If parts of the date were omitted, such as the year, we will grab the
     * current values for those. So at the time of writing, if the year was
     * omitted, we would have filled in 2014.
     *
     * @return DateTimeImmutable
     */
    public function getDateTime()
    {
        $now = new DateTime();

        $tzFormat = 0 === $now->getTimezone()->getOffset($now) ? '\\Z' : 'O';
        $nowParts = DateTimeParser::parseVCardDateTime($now->format('Ymd\\This'.$tzFormat));

        $dateParts = DateTimeParser::parseVCardDateTime($this->getValue());

        // This sets all the missing parts to the current date/time.
        // So if the year was missing for a birthday, we're making it 'this
        // year'.
        foreach ($dateParts as $k => $v) {
            if (is_null($v)) {
                $dateParts[$k] = $nowParts[$k];
            }
        }

        return new DateTimeImmutable("$dateParts[year]-$dateParts[month]-$dateParts[date] $dateParts[hour]:$dateParts[minute]:$dateParts[second] $dateParts[timezone]");
    }

    /**
     * Returns the value, in the format it should be encoded for json.
     *
     * This method must always return an array.
     *
     * @return array
     */
    public function getJsonValue()
    {
        $parts = DateTimeParser::parseVCardDateTime($this->getValue());

        $dateStr = '';

        // Year
        if (!is_null($parts['year'])) {
            $dateStr .= $parts['year'];

            if (!is_null($parts['month'])) {
                // If a year and a month is set, we need to insert a separator
                // dash.
                $dateStr .= '-';
            }
        } else {
            if (!is_null($parts['month']) || !is_null($parts['date'])) {
                // Inserting two dashes
                $dateStr .= '--';
            }
        }

        // Month
        if (!is_null($parts['month'])) {
            $dateStr .= $parts['month'];

            if (isset($parts['date'])) {
                // If month and date are set, we need the separator dash.
                $dateStr .= '-';
            }
        } elseif (isset($parts['date'])) {
            // If the month is empty, and a date is set, we need a 'empty
            // dash'
            $dateStr .= '-';
        }

        // Date
        if (!is_null($parts['date'])) {
            $dateStr .= $parts['date'];
        }

        // Early exit if we don't have a time string.
        if (is_null($parts['hour']) && is_null($parts['minute']) && is_null($parts['second'])) {
            return [$dateStr];
        }

        $dateStr .= 'T';

        // Hour
        if (!is_null($parts['hour'])) {
            $dateStr .= $parts['hour'];

            if (!is_null($parts['minute'])) {
                $dateStr .= ':';
            }
        } else {
            // We know either minute or second _must_ be set, so we insert a
            // dash for an empty value.
            $dateStr .= '-';
        }

        // Minute
        if (!is_null($parts['minute'])) {
            $dateStr .= $parts['minute'];

            if (!is_null($parts['second'])) {
                $dateStr .= ':';
            }
        } elseif (isset($parts['second'])) {
            // Dash for empty minute
            $dateStr .= '-';
        }

        // Second
        if (!is_null($parts['second'])) {
            $dateStr .= $parts['second'];
        }

        // Timezone
        if (!is_null($parts['timezone'])) {
            $dateStr .= $parts['timezone'];
        }

        return [$dateStr];
    }

    /**
     * This method serializes only the value of a property. This is used to
     * create xCard or xCal documents.
     *
     * @param Xml\Writer $writer XML writer
     */
    protected function xmlSerializeValue(Xml\Writer $writer)
    {
        $valueType = strtolower($this->getValueType());
        $parts = DateTimeParser::parseVCardDateAndOrTime($this->getValue());
        $value = '';

        // $d = defined
        $d = function ($part) use ($parts) {
            return !is_null($parts[$part]);
        };

        // $r = read
        $r = function ($part) use ($parts) {
            return $parts[$part];
        };

        // From the Relax NG Schema.
        //
        // # 4.3.1
        // value-date = element date {
        //     xsd:string { pattern = "\d{8}|\d{4}-\d\d|--\d\d(\d\d)?|---\d\d" }
        //   }
        if (($d('year') || $d('month') || $d('date'))
            && (!$d('hour') && !$d('minute') && !$d('second') && !$d('timezone'))) {
            if ($d('year') && $d('month') && $d('date')) {
                $value .= $r('year').$r('month').$r('date');
            } elseif ($d('year') && $d('month') && !$d('date')) {
                $value .= $r('year').'-'.$r('month');
            } elseif (!$d('year') && $d('month')) {
                $value .= '--'.$r('month').$r('date');
            } elseif (!$d('year') && !$d('month') && $d('date')) {
                $value .= '---'.$r('date');
            }

            // # 4.3.2
        // value-time = element time {
        //     xsd:string { pattern = "(\d\d(\d\d(\d\d)?)?|-\d\d(\d\d?)|--\d\d)"
        //                          ~ "(Z|[+\-]\d\d(\d\d)?)?" }
        //   }
        } elseif ((!$d('year') && !$d('month') && !$d('date'))
                  && ($d('hour') || $d('minute') || $d('second'))) {
            if ($d('hour')) {
                $value .= $r('hour').$r('minute').$r('second');
            } elseif ($d('minute')) {
                $value .= '-'.$r('minute').$r('second');
            } elseif ($d('second')) {
                $value .= '--'.$r('second');
            }

            $value .= $r('timezone');

        // # 4.3.3
        // value-date-time = element date-time {
        //     xsd:string { pattern = "(\d{8}|--\d{4}|---\d\d)T\d\d(\d\d(\d\d)?)?"
        //                          ~ "(Z|[+\-]\d\d(\d\d)?)?" }
        //   }
        } elseif ($d('date') && $d('hour')) {
            if ($d('year') && $d('month') && $d('date')) {
                $value .= $r('year').$r('month').$r('date');
            } elseif (!$d('year') && $d('month') && $d('date')) {
                $value .= '--'.$r('month').$r('date');
            } elseif (!$d('year') && !$d('month') && $d('date')) {
                $value .= '---'.$r('date');
            }

            $value .= 'T'.$r('hour').$r('minute').$r('second').
                      $r('timezone');
        }

        $writer->writeElement($valueType, $value);
    }

    /**
     * Sets a raw value coming from a mimedir (iCalendar/vCard) file.
     *
     * This has been 'unfolded', so only 1 line will be passed. Unescaping is
     * not yet done, but parameters are not included.
     *
     * @param string $val
     */
    public function setRawMimeDirValue($val)
    {
        $this->setValue($val);
    }

    /**
     * Returns a raw mime-dir representation of the value.
     *
     * @return string
     */
    public function getRawMimeDirValue()
    {
        return implode($this->delimiter, $this->getParts());
    }

    /**
     * Validates the node for correctness.
     *
     * The following options are supported:
     *   Node::REPAIR - May attempt to automatically repair the problem.
     *
     * This method returns an array with detected problems.
     * Every element has the following properties:
     *
     *  * level - problem level.
     *  * message - A human-readable string describing the issue.
     *  * node - A reference to the problematic node.
     *
     * The level means:
     *   1 - The issue was repaired (only happens if REPAIR was turned on)
     *   2 - An inconsequential issue
     *   3 - A severe issue.
     *
     * @param int $options
     *
     * @return array
     */
    public function validate($options = 0)
    {
        $messages = parent::validate($options);
        $value = $this->getValue();

        try {
            DateTimeParser::parseVCardDateTime($value);
        } catch (InvalidDataException $e) {
            $messages[] = [
                'level' => 3,
                'message' => 'The supplied value ('.$value.') is not a correct DATE-AND-OR-TIME property',
                'node' => $this,
            ];
        }

        return $messages;
    }
}
