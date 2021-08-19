<?php

namespace libphonenumber;

use libphonenumber\Leniency\Possible;
use libphonenumber\Leniency\StrictGrouping;
use libphonenumber\Leniency\Valid;
use libphonenumber\Leniency\ExactGrouping;

class Leniency
{
    public static function POSSIBLE()
    {
        return new Possible;
    }

    public static function VALID()
    {
        return new Valid;
    }

    public static function STRICT_GROUPING()
    {
        return new StrictGrouping;
    }

    public static function EXACT_GROUPING()
    {
        return new ExactGrouping;
    }
}
