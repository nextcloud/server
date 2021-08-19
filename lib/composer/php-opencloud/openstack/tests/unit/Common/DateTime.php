<?php

namespace OpenStack\Test\Common;

class DateTime extends \DateTime
{
    public static function factory($time)
    {
        return new static($time);
    }

    public function toIso8601()
    {
        return $this->format(self::ISO8601);
    }
}
