<?php

namespace Sabre\VObject;

/**
 * This class provides a list of global defaults for vobject.
 *
 * Some of these started to appear in various classes, so it made a bit more
 * sense to centralize them, so it's easier for user to find and change these.
 *
 * The global nature of them does mean that changing the settings for one
 * instance has a global influence.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class Settings
{
    /**
     * The minimum date we accept for various calculations with dates, such as
     * recurrences.
     *
     * The choice of 1900 is pretty arbitrary, but it covers most common
     * use-cases. In particular, it covers birthdates for virtually everyone
     * alive on earth, which is less than 5 people at the time of writing.
     */
    public static $minDate = '1900-01-01';

    /**
     * The maximum date we accept for various calculations with dates, such as
     * recurrences.
     *
     * The choice of 2100 is pretty arbitrary, but should cover most
     * appointments made for many years to come.
     */
    public static $maxDate = '2100-01-01';

    /**
     * The maximum number of recurrences that will be generated.
     *
     * This setting limits the maximum of recurring events that this library
     * generates in its recurrence iterators.
     *
     * This is a security measure. Without this, it would be possible to craft
     * specific events that recur many, many times, potentially DDOSing the
     * server.
     *
     * The default (3500) allows creation of a daily event that goes on for 10
     * years, which is hopefully long enough for most.
     *
     * Set this value to -1 to disable this control altogether.
     */
    public static $maxRecurrences = 3500;
}
