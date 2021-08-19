<?php

namespace Safe;

use DateInterval;
use DateTimeInterface;
use DateTimeZone;
use Safe\Exceptions\DatetimeException;

/** this class implements a safe version of the Datetime class */
class DateTime extends \DateTime
{
    //switch from regular datetime to safe version
    private static function createFromRegular(\DateTime $datetime): self
    {
        return new self($datetime->format('Y-m-d H:i:s.u'), $datetime->getTimezone());
    }

    /**
     * @param string $format
     * @param string $time
     * @param DateTimeZone|null $timezone
     * @throws DatetimeException
     */
    public static function createFromFormat($format, $time, $timezone = null): self
    {
        $datetime = parent::createFromFormat($format, $time, $timezone);
        if ($datetime === false) {
            throw DatetimeException::createFromPhpError();
        }
        return self::createFromRegular($datetime);
    }

    /**
     * @param DateTimeInterface $datetime2 The date to compare to.
     * @param boolean $absolute [optional] Whether to return absolute difference.
     * @return DateInterval The DateInterval object representing the difference between the two dates.
     * @throws DatetimeException
     */
    public function diff($datetime2, $absolute = false): DateInterval
    {
        /** @var \DateInterval|false $result */
        $result = parent::diff($datetime2, $absolute);
        if ($result === false) {
            throw DatetimeException::createFromPhpError();
        }
        return $result;
    }

    /**
     * @param string $modify A date/time string. Valid formats are explained in <a href="https://secure.php.net/manual/en/datetime.formats.php">Date and Time Formats</a>.
     * @return DateTime Returns the DateTime object for method chaining.
     * @throws DatetimeException
     */
    public function modify($modify): self
    {
        /** @var DateTime|false $result */
        $result = parent::modify($modify);
        if ($result === false) {
            throw DatetimeException::createFromPhpError();
        }
        return $result;
    }

    /**
     * @param int $year
     * @param int $month
     * @param int $day
     * @return DateTime
     * @throws DatetimeException
     */
    public function setDate($year, $month, $day): self
    {
        /** @var DateTime|false $result */
        $result = parent::setDate($year, $month, $day);
        if ($result === false) {
            throw DatetimeException::createFromPhpError();
        }
        return $result;
    }
}
