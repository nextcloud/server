<?php

namespace Sabre\VObject\Recur;

use DateTimeImmutable;
use DateTimeInterface;
use Iterator;
use Sabre\VObject\DateTimeParser;
use Sabre\VObject\InvalidDataException;
use Sabre\VObject\Property;

/**
 * RRuleParser.
 *
 * This class receives an RRULE string, and allows you to iterate to get a list
 * of dates in that recurrence.
 *
 * For instance, passing: FREQ=DAILY;LIMIT=5 will cause the iterator to contain
 * 5 items, one for each day.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class RRuleIterator implements Iterator
{
    /**
     * Creates the Iterator.
     *
     * @param string|array $rrule
     */
    public function __construct($rrule, DateTimeInterface $start)
    {
        $this->startDate = $start;
        $this->parseRRule($rrule);
        $this->currentDate = clone $this->startDate;
    }

    /* Implementation of the Iterator interface {{{ */

    public function current()
    {
        if (!$this->valid()) {
            return;
        }

        return clone $this->currentDate;
    }

    /**
     * Returns the current item number.
     *
     * @return int
     */
    public function key()
    {
        return $this->counter;
    }

    /**
     * Returns whether the current item is a valid item for the recurrence
     * iterator. This will return false if we've gone beyond the UNTIL or COUNT
     * statements.
     *
     * @return bool
     */
    public function valid()
    {
        if (null === $this->currentDate) {
            return false;
        }
        if (!is_null($this->count)) {
            return $this->counter < $this->count;
        }

        return is_null($this->until) || $this->currentDate <= $this->until;
    }

    /**
     * Resets the iterator.
     */
    public function rewind()
    {
        $this->currentDate = clone $this->startDate;
        $this->counter = 0;
    }

    /**
     * Goes on to the next iteration.
     */
    public function next()
    {
        // Otherwise, we find the next event in the normal RRULE
        // sequence.
        switch ($this->frequency) {
            case 'hourly':
                $this->nextHourly();
                break;

            case 'daily':
                $this->nextDaily();
                break;

            case 'weekly':
                $this->nextWeekly();
                break;

            case 'monthly':
                $this->nextMonthly();
                break;

            case 'yearly':
                $this->nextYearly();
                break;
        }
        ++$this->counter;
    }

    /* End of Iterator implementation }}} */

    /**
     * Returns true if this recurring event never ends.
     *
     * @return bool
     */
    public function isInfinite()
    {
        return !$this->count && !$this->until;
    }

    /**
     * This method allows you to quickly go to the next occurrence after the
     * specified date.
     */
    public function fastForward(DateTimeInterface $dt)
    {
        while ($this->valid() && $this->currentDate < $dt) {
            $this->next();
        }
    }

    /**
     * The reference start date/time for the rrule.
     *
     * All calculations are based on this initial date.
     *
     * @var DateTimeInterface
     */
    protected $startDate;

    /**
     * The date of the current iteration. You can get this by calling
     * ->current().
     *
     * @var DateTimeInterface
     */
    protected $currentDate;

    /**
     * Frequency is one of: secondly, minutely, hourly, daily, weekly, monthly,
     * yearly.
     *
     * @var string
     */
    protected $frequency;

    /**
     * The number of recurrences, or 'null' if infinitely recurring.
     *
     * @var int
     */
    protected $count;

    /**
     * The interval.
     *
     * If for example frequency is set to daily, interval = 2 would mean every
     * 2 days.
     *
     * @var int
     */
    protected $interval = 1;

    /**
     * The last instance of this recurrence, inclusively.
     *
     * @var DateTimeInterface|null
     */
    protected $until;

    /**
     * Which seconds to recur.
     *
     * This is an array of integers (between 0 and 60)
     *
     * @var array
     */
    protected $bySecond;

    /**
     * Which minutes to recur.
     *
     * This is an array of integers (between 0 and 59)
     *
     * @var array
     */
    protected $byMinute;

    /**
     * Which hours to recur.
     *
     * This is an array of integers (between 0 and 23)
     *
     * @var array
     */
    protected $byHour;

    /**
     * The current item in the list.
     *
     * You can get this number with the key() method.
     *
     * @var int
     */
    protected $counter = 0;

    /**
     * Which weekdays to recur.
     *
     * This is an array of weekdays
     *
     * This may also be preceded by a positive or negative integer. If present,
     * this indicates the nth occurrence of a specific day within the monthly or
     * yearly rrule. For instance, -2TU indicates the second-last tuesday of
     * the month, or year.
     *
     * @var array
     */
    protected $byDay;

    /**
     * Which days of the month to recur.
     *
     * This is an array of days of the months (1-31). The value can also be
     * negative. -5 for instance means the 5th last day of the month.
     *
     * @var array
     */
    protected $byMonthDay;

    /**
     * Which days of the year to recur.
     *
     * This is an array with days of the year (1 to 366). The values can also
     * be negative. For instance, -1 will always represent the last day of the
     * year. (December 31st).
     *
     * @var array
     */
    protected $byYearDay;

    /**
     * Which week numbers to recur.
     *
     * This is an array of integers from 1 to 53. The values can also be
     * negative. -1 will always refer to the last week of the year.
     *
     * @var array
     */
    protected $byWeekNo;

    /**
     * Which months to recur.
     *
     * This is an array of integers from 1 to 12.
     *
     * @var array
     */
    protected $byMonth;

    /**
     * Which items in an existing st to recur.
     *
     * These numbers work together with an existing by* rule. It specifies
     * exactly which items of the existing by-rule to filter.
     *
     * Valid values are 1 to 366 and -1 to -366. As an example, this can be
     * used to recur the last workday of the month.
     *
     * This would be done by setting frequency to 'monthly', byDay to
     * 'MO,TU,WE,TH,FR' and bySetPos to -1.
     *
     * @var array
     */
    protected $bySetPos;

    /**
     * When the week starts.
     *
     * @var string
     */
    protected $weekStart = 'MO';

    /* Functions that advance the iterator {{{ */

    /**
     * Does the processing for advancing the iterator for hourly frequency.
     */
    protected function nextHourly()
    {
        $this->currentDate = $this->currentDate->modify('+'.$this->interval.' hours');
    }

    /**
     * Does the processing for advancing the iterator for daily frequency.
     */
    protected function nextDaily()
    {
        if (!$this->byHour && !$this->byDay) {
            $this->currentDate = $this->currentDate->modify('+'.$this->interval.' days');

            return;
        }

        $recurrenceHours = [];
        if (!empty($this->byHour)) {
            $recurrenceHours = $this->getHours();
        }

        $recurrenceDays = [];
        if (!empty($this->byDay)) {
            $recurrenceDays = $this->getDays();
        }

        $recurrenceMonths = [];
        if (!empty($this->byMonth)) {
            $recurrenceMonths = $this->getMonths();
        }

        do {
            if ($this->byHour) {
                if ('23' == $this->currentDate->format('G')) {
                    // to obey the interval rule
                    $this->currentDate = $this->currentDate->modify('+'.($this->interval - 1).' days');
                }

                $this->currentDate = $this->currentDate->modify('+1 hours');
            } else {
                $this->currentDate = $this->currentDate->modify('+'.$this->interval.' days');
            }

            // Current month of the year
            $currentMonth = $this->currentDate->format('n');

            // Current day of the week
            $currentDay = $this->currentDate->format('w');

            // Current hour of the day
            $currentHour = $this->currentDate->format('G');
        } while (
            ($this->byDay && !in_array($currentDay, $recurrenceDays)) ||
            ($this->byHour && !in_array($currentHour, $recurrenceHours)) ||
            ($this->byMonth && !in_array($currentMonth, $recurrenceMonths))
        );
    }

    /**
     * Does the processing for advancing the iterator for weekly frequency.
     */
    protected function nextWeekly()
    {
        if (!$this->byHour && !$this->byDay) {
            $this->currentDate = $this->currentDate->modify('+'.$this->interval.' weeks');

            return;
        }

        $recurrenceHours = [];
        if ($this->byHour) {
            $recurrenceHours = $this->getHours();
        }

        $recurrenceDays = [];
        if ($this->byDay) {
            $recurrenceDays = $this->getDays();
        }

        // First day of the week:
        $firstDay = $this->dayMap[$this->weekStart];

        do {
            if ($this->byHour) {
                $this->currentDate = $this->currentDate->modify('+1 hours');
            } else {
                $this->currentDate = $this->currentDate->modify('+1 days');
            }

            // Current day of the week
            $currentDay = (int) $this->currentDate->format('w');

            // Current hour of the day
            $currentHour = (int) $this->currentDate->format('G');

            // We need to roll over to the next week
            if ($currentDay === $firstDay && (!$this->byHour || '0' == $currentHour)) {
                $this->currentDate = $this->currentDate->modify('+'.($this->interval - 1).' weeks');

                // We need to go to the first day of this week, but only if we
                // are not already on this first day of this week.
                if ($this->currentDate->format('w') != $firstDay) {
                    $this->currentDate = $this->currentDate->modify('last '.$this->dayNames[$this->dayMap[$this->weekStart]]);
                }
            }

            // We have a match
        } while (($this->byDay && !in_array($currentDay, $recurrenceDays)) || ($this->byHour && !in_array($currentHour, $recurrenceHours)));
    }

    /**
     * Does the processing for advancing the iterator for monthly frequency.
     */
    protected function nextMonthly()
    {
        $currentDayOfMonth = $this->currentDate->format('j');
        if (!$this->byMonthDay && !$this->byDay) {
            // If the current day is higher than the 28th, rollover can
            // occur to the next month. We Must skip these invalid
            // entries.
            if ($currentDayOfMonth < 29) {
                $this->currentDate = $this->currentDate->modify('+'.$this->interval.' months');
            } else {
                $increase = 0;
                do {
                    ++$increase;
                    $tempDate = clone $this->currentDate;
                    $tempDate = $tempDate->modify('+ '.($this->interval * $increase).' months');
                } while ($tempDate->format('j') != $currentDayOfMonth);
                $this->currentDate = $tempDate;
            }

            return;
        }

        $occurrence = -1;
        while (true) {
            $occurrences = $this->getMonthlyOccurrences();

            foreach ($occurrences as $occurrence) {
                // The first occurrence thats higher than the current
                // day of the month wins.
                if ($occurrence > $currentDayOfMonth) {
                    break 2;
                }
            }

            // If we made it all the way here, it means there were no
            // valid occurrences, and we need to advance to the next
            // month.
            //
            // This line does not currently work in hhvm. Temporary workaround
            // follows:
            // $this->currentDate->modify('first day of this month');
            $this->currentDate = new DateTimeImmutable($this->currentDate->format('Y-m-1 H:i:s'), $this->currentDate->getTimezone());
            // end of workaround
            $this->currentDate = $this->currentDate->modify('+ '.$this->interval.' months');

            // This goes to 0 because we need to start counting at the
            // beginning.
            $currentDayOfMonth = 0;

            // For some reason the "until" parameter was not being used here,
            // that's why the workaround of the 10000 year bug was needed at all
            // let's stop it before the "until" parameter date
            if ($this->until && $this->currentDate->getTimestamp() >= $this->until->getTimestamp()) {
                return;
            }

            // To prevent running this forever (better: until we hit the max date of DateTimeImmutable) we simply
            // stop at 9999-12-31. Looks like the year 10000 problem is not solved in php ....
            if ($this->currentDate->getTimestamp() > 253402300799) {
                $this->currentDate = null;

                return;
            }
        }

        $this->currentDate = $this->currentDate->setDate(
            (int) $this->currentDate->format('Y'),
            (int) $this->currentDate->format('n'),
            (int) $occurrence
        );
    }

    /**
     * Does the processing for advancing the iterator for yearly frequency.
     */
    protected function nextYearly()
    {
        $currentMonth = $this->currentDate->format('n');
        $currentYear = $this->currentDate->format('Y');
        $currentDayOfMonth = $this->currentDate->format('j');

        // No sub-rules, so we just advance by year
        if (empty($this->byMonth)) {
            // Unless it was a leap day!
            if (2 == $currentMonth && 29 == $currentDayOfMonth) {
                $counter = 0;
                do {
                    ++$counter;
                    // Here we increase the year count by the interval, until
                    // we hit a date that's also in a leap year.
                    //
                    // We could just find the next interval that's dividable by
                    // 4, but that would ignore the rule that there's no leap
                    // year every year that's dividable by a 100, but not by
                    // 400. (1800, 1900, 2100). So we just rely on the datetime
                    // functions instead.
                    $nextDate = clone $this->currentDate;
                    $nextDate = $nextDate->modify('+ '.($this->interval * $counter).' years');
                } while (2 != $nextDate->format('n'));

                $this->currentDate = $nextDate;

                return;
            }

            if (null !== $this->byWeekNo) { // byWeekNo is an array with values from -53 to -1, or 1 to 53
                $dayOffsets = [];
                if ($this->byDay) {
                    foreach ($this->byDay as $byDay) {
                        $dayOffsets[] = $this->dayMap[$byDay];
                    }
                } else {   // default is Monday
                    $dayOffsets[] = 1;
                }

                $currentYear = $this->currentDate->format('Y');

                while (true) {
                    $checkDates = [];

                    // loop through all WeekNo and Days to check all the combinations
                    foreach ($this->byWeekNo as $byWeekNo) {
                        foreach ($dayOffsets as $dayOffset) {
                            $date = clone $this->currentDate;
                            $date = $date->setISODate($currentYear, $byWeekNo, $dayOffset);

                            if ($date > $this->currentDate) {
                                $checkDates[] = $date;
                            }
                        }
                    }

                    if (count($checkDates) > 0) {
                        $this->currentDate = min($checkDates);

                        return;
                    }

                    // if there is no date found, check the next year
                    $currentYear += $this->interval;
                }
            }

            if (null !== $this->byYearDay) { // byYearDay is an array with values from -366 to -1, or 1 to 366
                $dayOffsets = [];
                if ($this->byDay) {
                    foreach ($this->byDay as $byDay) {
                        $dayOffsets[] = $this->dayMap[$byDay];
                    }
                } else {   // default is Monday-Sunday
                    $dayOffsets = [1, 2, 3, 4, 5, 6, 7];
                }

                $currentYear = $this->currentDate->format('Y');

                while (true) {
                    $checkDates = [];

                    // loop through all YearDay and Days to check all the combinations
                    foreach ($this->byYearDay as $byYearDay) {
                        $date = clone $this->currentDate;
                        $date = $date->setDate($currentYear, 1, 1);
                        if ($byYearDay > 0) {
                            $date = $date->add(new \DateInterval('P'.$byYearDay.'D'));
                        } else {
                            $date = $date->sub(new \DateInterval('P'.abs($byYearDay).'D'));
                        }

                        if ($date > $this->currentDate && in_array($date->format('N'), $dayOffsets)) {
                            $checkDates[] = $date;
                        }
                    }

                    if (count($checkDates) > 0) {
                        $this->currentDate = min($checkDates);

                        return;
                    }

                    // if there is no date found, check the next year
                    $currentYear += $this->interval;
                }
            }

            // The easiest form
            $this->currentDate = $this->currentDate->modify('+'.$this->interval.' years');

            return;
        }

        $currentMonth = $this->currentDate->format('n');
        $currentYear = $this->currentDate->format('Y');
        $currentDayOfMonth = $this->currentDate->format('j');

        $advancedToNewMonth = false;

        // If we got a byDay or getMonthDay filter, we must first expand
        // further.
        if ($this->byDay || $this->byMonthDay) {
            $occurrence = -1;
            while (true) {
                $occurrences = $this->getMonthlyOccurrences();

                foreach ($occurrences as $occurrence) {
                    // The first occurrence that's higher than the current
                    // day of the month wins.
                    // If we advanced to the next month or year, the first
                    // occurrence is always correct.
                    if ($occurrence > $currentDayOfMonth || $advancedToNewMonth) {
                        break 2;
                    }
                }

                // If we made it here, it means we need to advance to
                // the next month or year.
                $currentDayOfMonth = 1;
                $advancedToNewMonth = true;
                do {
                    ++$currentMonth;
                    if ($currentMonth > 12) {
                        $currentYear += $this->interval;
                        $currentMonth = 1;
                    }
                } while (!in_array($currentMonth, $this->byMonth));

                $this->currentDate = $this->currentDate->setDate(
                    (int) $currentYear,
                    (int) $currentMonth,
                    (int) $currentDayOfMonth
                );
            }

            // If we made it here, it means we got a valid occurrence
            $this->currentDate = $this->currentDate->setDate(
                (int) $currentYear,
                (int) $currentMonth,
                (int) $occurrence
            );

            return;
        } else {
            // These are the 'byMonth' rules, if there are no byDay or
            // byMonthDay sub-rules.
            do {
                ++$currentMonth;
                if ($currentMonth > 12) {
                    $currentYear += $this->interval;
                    $currentMonth = 1;
                }
            } while (!in_array($currentMonth, $this->byMonth));
            $this->currentDate = $this->currentDate->setDate(
                (int) $currentYear,
                (int) $currentMonth,
                (int) $currentDayOfMonth
            );

            return;
        }
    }

    /* }}} */

    /**
     * This method receives a string from an RRULE property, and populates this
     * class with all the values.
     *
     * @param string|array $rrule
     */
    protected function parseRRule($rrule)
    {
        if (is_string($rrule)) {
            $rrule = Property\ICalendar\Recur::stringToArray($rrule);
        }

        foreach ($rrule as $key => $value) {
            $key = strtoupper($key);
            switch ($key) {
                case 'FREQ':
                    $value = strtolower($value);
                    if (!in_array(
                        $value,
                        ['secondly', 'minutely', 'hourly', 'daily', 'weekly', 'monthly', 'yearly']
                    )) {
                        throw new InvalidDataException('Unknown value for FREQ='.strtoupper($value));
                    }
                    $this->frequency = $value;
                    break;

                case 'UNTIL':
                    $this->until = DateTimeParser::parse($value, $this->startDate->getTimezone());

                    // In some cases events are generated with an UNTIL=
                    // parameter before the actual start of the event.
                    //
                    // Not sure why this is happening. We assume that the
                    // intention was that the event only recurs once.
                    //
                    // So we are modifying the parameter so our code doesn't
                    // break.
                    if ($this->until < $this->startDate) {
                        $this->until = $this->startDate;
                    }
                    break;

                case 'INTERVAL':
                case 'COUNT':
                    $val = (int) $value;
                    if ($val < 1) {
                        throw new InvalidDataException(strtoupper($key).' in RRULE must be a positive integer!');
                    }
                    $key = strtolower($key);
                    $this->$key = $val;
                    break;

                case 'BYSECOND':
                    $this->bySecond = (array) $value;
                    break;

                case 'BYMINUTE':
                    $this->byMinute = (array) $value;
                    break;

                case 'BYHOUR':
                    $this->byHour = (array) $value;
                    break;

                case 'BYDAY':
                    $value = (array) $value;
                    foreach ($value as $part) {
                        if (!preg_match('#^  (-|\+)? ([1-5])? (MO|TU|WE|TH|FR|SA|SU) $# xi', $part)) {
                            throw new InvalidDataException('Invalid part in BYDAY clause: '.$part);
                        }
                    }
                    $this->byDay = $value;
                    break;

                case 'BYMONTHDAY':
                    $this->byMonthDay = (array) $value;
                    break;

                case 'BYYEARDAY':
                    $this->byYearDay = (array) $value;
                    foreach ($this->byYearDay as $byYearDay) {
                        if (!is_numeric($byYearDay) || (int) $byYearDay < -366 || 0 == (int) $byYearDay || (int) $byYearDay > 366) {
                            throw new InvalidDataException('BYYEARDAY in RRULE must have value(s) from 1 to 366, or -366 to -1!');
                        }
                    }
                    break;

                case 'BYWEEKNO':
                    $this->byWeekNo = (array) $value;
                    foreach ($this->byWeekNo as $byWeekNo) {
                        if (!is_numeric($byWeekNo) || (int) $byWeekNo < -53 || 0 == (int) $byWeekNo || (int) $byWeekNo > 53) {
                            throw new InvalidDataException('BYWEEKNO in RRULE must have value(s) from 1 to 53, or -53 to -1!');
                        }
                    }
                    break;

                case 'BYMONTH':
                    $this->byMonth = (array) $value;
                    foreach ($this->byMonth as $byMonth) {
                        if (!is_numeric($byMonth) || (int) $byMonth < 1 || (int) $byMonth > 12) {
                            throw new InvalidDataException('BYMONTH in RRULE must have value(s) between 1 and 12!');
                        }
                    }
                    break;

                case 'BYSETPOS':
                    $this->bySetPos = (array) $value;
                    break;

                case 'WKST':
                    $this->weekStart = strtoupper($value);
                    break;

                default:
                    throw new InvalidDataException('Not supported: '.strtoupper($key));
            }
        }
    }

    /**
     * Mappings between the day number and english day name.
     *
     * @var array
     */
    protected $dayNames = [
        0 => 'Sunday',
        1 => 'Monday',
        2 => 'Tuesday',
        3 => 'Wednesday',
        4 => 'Thursday',
        5 => 'Friday',
        6 => 'Saturday',
    ];

    /**
     * Returns all the occurrences for a monthly frequency with a 'byDay' or
     * 'byMonthDay' expansion for the current month.
     *
     * The returned list is an array of integers with the day of month (1-31).
     *
     * @return array
     */
    protected function getMonthlyOccurrences()
    {
        $startDate = clone $this->currentDate;

        $byDayResults = [];

        // Our strategy is to simply go through the byDays, advance the date to
        // that point and add it to the results.
        if ($this->byDay) {
            foreach ($this->byDay as $day) {
                $dayName = $this->dayNames[$this->dayMap[substr($day, -2)]];

                // Dayname will be something like 'wednesday'. Now we need to find
                // all wednesdays in this month.
                $dayHits = [];

                // workaround for missing 'first day of the month' support in hhvm
                $checkDate = new \DateTime($startDate->format('Y-m-1'));
                // workaround modify always advancing the date even if the current day is a $dayName in hhvm
                if ($checkDate->format('l') !== $dayName) {
                    $checkDate = $checkDate->modify($dayName);
                }

                do {
                    $dayHits[] = $checkDate->format('j');
                    $checkDate = $checkDate->modify('next '.$dayName);
                } while ($checkDate->format('n') === $startDate->format('n'));

                // So now we have 'all wednesdays' for month. It is however
                // possible that the user only really wanted the 1st, 2nd or last
                // wednesday.
                if (strlen($day) > 2) {
                    $offset = (int) substr($day, 0, -2);

                    if ($offset > 0) {
                        // It is possible that the day does not exist, such as a
                        // 5th or 6th wednesday of the month.
                        if (isset($dayHits[$offset - 1])) {
                            $byDayResults[] = $dayHits[$offset - 1];
                        }
                    } else {
                        // if it was negative we count from the end of the array
                        // might not exist, fx. -5th tuesday
                        if (isset($dayHits[count($dayHits) + $offset])) {
                            $byDayResults[] = $dayHits[count($dayHits) + $offset];
                        }
                    }
                } else {
                    // There was no counter (first, second, last wednesdays), so we
                    // just need to add the all to the list).
                    $byDayResults = array_merge($byDayResults, $dayHits);
                }
            }
        }

        $byMonthDayResults = [];
        if ($this->byMonthDay) {
            foreach ($this->byMonthDay as $monthDay) {
                // Removing values that are out of range for this month
                if ($monthDay > $startDate->format('t') ||
                    $monthDay < 0 - $startDate->format('t')) {
                    continue;
                }
                if ($monthDay > 0) {
                    $byMonthDayResults[] = $monthDay;
                } else {
                    // Negative values
                    $byMonthDayResults[] = $startDate->format('t') + 1 + $monthDay;
                }
            }
        }

        // If there was just byDay or just byMonthDay, they just specify our
        // (almost) final list. If both were provided, then byDay limits the
        // list.
        if ($this->byMonthDay && $this->byDay) {
            $result = array_intersect($byMonthDayResults, $byDayResults);
        } elseif ($this->byMonthDay) {
            $result = $byMonthDayResults;
        } else {
            $result = $byDayResults;
        }
        $result = array_unique($result);
        sort($result, SORT_NUMERIC);

        // The last thing that needs checking is the BYSETPOS. If it's set, it
        // means only certain items in the set survive the filter.
        if (!$this->bySetPos) {
            return $result;
        }

        $filteredResult = [];
        foreach ($this->bySetPos as $setPos) {
            if ($setPos < 0) {
                $setPos = count($result) + ($setPos + 1);
            }
            if (isset($result[$setPos - 1])) {
                $filteredResult[] = $result[$setPos - 1];
            }
        }

        sort($filteredResult, SORT_NUMERIC);

        return $filteredResult;
    }

    /**
     * Simple mapping from iCalendar day names to day numbers.
     *
     * @var array
     */
    protected $dayMap = [
        'SU' => 0,
        'MO' => 1,
        'TU' => 2,
        'WE' => 3,
        'TH' => 4,
        'FR' => 5,
        'SA' => 6,
    ];

    protected function getHours()
    {
        $recurrenceHours = [];
        foreach ($this->byHour as $byHour) {
            $recurrenceHours[] = $byHour;
        }

        return $recurrenceHours;
    }

    protected function getDays()
    {
        $recurrenceDays = [];
        foreach ($this->byDay as $byDay) {
            // The day may be preceded with a positive (+n) or
            // negative (-n) integer. However, this does not make
            // sense in 'weekly' so we ignore it here.
            $recurrenceDays[] = $this->dayMap[substr($byDay, -2)];
        }

        return $recurrenceDays;
    }

    protected function getMonths()
    {
        $recurrenceMonths = [];
        foreach ($this->byMonth as $byMonth) {
            $recurrenceMonths[] = $byMonth;
        }

        return $recurrenceMonths;
    }
}
