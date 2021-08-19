<?php

namespace Safe;

use DateInterval;
use DateTime;
use DateTimeInterface;
use DateTimeZone;
use Safe\Exceptions\DatetimeException;

/**
 * This class is used to implement a safe version of the DatetimeImmutable class.
 * While it technically overloads \DateTimeImmutable for typehint compatibility,
 * it is actually used as a wrapper of \DateTimeImmutable, mostly to be able to overwrite functions like getTimestamp() while still being able to edit milliseconds via setTime().
 */
class DateTimeImmutable extends \DateTimeImmutable
{
    /**
     * @var \DateTimeImmutable
     */
    private $innerDateTime;

    /**
     * DateTimeImmutable constructor.
     * @param string $time
     * @param DateTimeZone|null $timezone
     * @throws \Exception
     */
    public function __construct($time = 'now', $timezone = null)
    {
        parent::__construct($time, $timezone);
        $this->innerDateTime = new parent($time, $timezone);
    }

    //switch between regular datetime and safe version
    public static function createFromRegular(\DateTimeImmutable $datetime): self
    {
        $safeDatetime = new self($datetime->format('Y-m-d H:i:s.u'), $datetime->getTimezone()); //we need to also update the wrapper to not break the operators '<' and '>'
        $safeDatetime->innerDateTime = $datetime; //to make sure we don't lose information because of the format().
        return $safeDatetime;
    }

    //usefull if you need to switch back to regular DateTimeImmutable (for example when using DatePeriod)
    public function getInnerDateTime(): \DateTimeImmutable
    {
        return $this->innerDateTime;
    }

    /////////////////////////////////////////////////////////////////////////////
    // overload functions with false errors

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
     * @param string $format
     * @return string
     * @throws DatetimeException
     */
    public function format($format): string
    {
        /** @var string|false $result */
        $result = $this->innerDateTime->format($format);
        if ($result === false) {
            throw DatetimeException::createFromPhpError();
        }
        return $result;
    }

    /**
     * @param DateTimeInterface $datetime2
     * @param bool $absolute
     * @return DateInterval
     * @throws DatetimeException
     */
    public function diff($datetime2, $absolute = false): DateInterval
    {
        /** @var \DateInterval|false $result */
        $result = $this->innerDateTime->diff($datetime2, $absolute);
        if ($result === false) {
            throw DatetimeException::createFromPhpError();
        }
        return $result;
    }

    /**
     * @param string $modify
     * @return DateTimeImmutable
     * @throws DatetimeException
     */
    public function modify($modify): self
    {
        /** @var \DateTimeImmutable|false $result */
        $result = $this->innerDateTime->modify($modify);
        if ($result === false) {
            throw DatetimeException::createFromPhpError();
        }
        return self::createFromRegular($result); //we have to recreate a safe datetime because modify create a new instance of \DateTimeImmutable
    }

    /**
     * @param int $year
     * @param int $month
     * @param int $day
     * @return DateTimeImmutable
     * @throws DatetimeException
     */
    public function setDate($year, $month, $day): self
    {
        /** @var \DateTimeImmutable|false $result */
        $result = $this->innerDateTime->setDate($year, $month, $day);
        if ($result === false) {
            throw DatetimeException::createFromPhpError();
        }
        return self::createFromRegular($result); //we have to recreate a safe datetime because modify create a new instance of \DateTimeImmutable
    }

    /**
     * @param int $year
     * @param int $week
     * @param int $day
     * @return DateTimeImmutable
     * @throws DatetimeException
     */
    public function setISODate($year, $week, $day = 1): self
    {
        /** @var \DateTimeImmutable|false $result */
        $result = $this->innerDateTime->setISODate($year, $week, $day);
        if ($result === false) {
            throw DatetimeException::createFromPhpError();
        }
        return self::createFromRegular($result); //we have to recreate a safe datetime because modify create a new instance of \DateTimeImmutable
    }

    /**
     * @param int $hour
     * @param int $minute
     * @param int $second
     * @param int $microseconds
     * @return DateTimeImmutable
     * @throws DatetimeException
     */
    public function setTime($hour, $minute, $second = 0, $microseconds = 0): self
    {
        /** @var \DateTimeImmutable|false $result */
        $result = $this->innerDateTime->setTime($hour, $minute, $second, $microseconds);
        if ($result === false) {
            throw DatetimeException::createFromPhpError();
        }
        return self::createFromRegular($result);
    }

    /**
     * @param int $unixtimestamp
     * @return DateTimeImmutable
     * @throws DatetimeException
     */
    public function setTimestamp($unixtimestamp): self
    {
        /** @var \DateTimeImmutable|false $result */
        $result = $this->innerDateTime->setTimestamp($unixtimestamp);
        if ($result === false) {
            throw DatetimeException::createFromPhpError();
        }
        return self::createFromRegular($result);
    }

    /**
     * @param DateTimeZone $timezone
     * @return DateTimeImmutable
     * @throws DatetimeException
     */
    public function setTimezone($timezone): self
    {
        /** @var \DateTimeImmutable|false $result */
        $result = $this->innerDateTime->setTimezone($timezone);
        if ($result === false) {
            throw DatetimeException::createFromPhpError();
        }
        return self::createFromRegular($result);
    }

    /**
     * @param DateInterval $interval
     * @return DateTimeImmutable
     * @throws DatetimeException
     */
    public function sub($interval): self
    {
        /** @var \DateTimeImmutable|false $result */
        $result = $this->innerDateTime->sub($interval);
        if ($result === false) {
            throw DatetimeException::createFromPhpError();
        }
        return self::createFromRegular($result);
    }

    /**
     * @throws DatetimeException
     */
    public function getOffset(): int
    {
        /** @var int|false $result */
        $result = $this->innerDateTime->getOffset();
        if ($result === false) {
            throw DatetimeException::createFromPhpError();
        }
        return $result;
    }

    //////////////////////////////////////////////////////////////////////////////////////////
    //overload getters to use the inner datetime immutable instead of itself

    /**
     * @param DateInterval $interval
     * @return DateTimeImmutable
     */
    public function add($interval): self
    {
        return self::createFromRegular($this->innerDateTime->add($interval));
    }

    /**
     * @param DateTime $dateTime
     * @return DateTimeImmutable
     */
    public static function createFromMutable($dateTime): self
    {
        return self::createFromRegular(parent::createFromMutable($dateTime));
    }

    /**
     * @param mixed[] $array
     * @return DateTimeImmutable
     */
    public static function __set_state($array): self
    {
        return self::createFromRegular(parent::__set_state($array));
    }

    public function getTimezone(): DateTimeZone
    {
        return $this->innerDateTime->getTimezone();
    }

    public function getTimestamp(): int
    {
        return $this->innerDateTime->getTimestamp();
    }
}
