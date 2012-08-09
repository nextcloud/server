<?php

/**
 * DateTime property
 *
 * this class got renamed to Sabre_VObject_Property_DateTime
 *
 * @package Sabre
 * @subpackage VObject
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 * @deprecated
 */
class Sabre_VObject_Element_DateTime extends Sabre_VObject_Property_DateTime {

    /**
     * Local 'floating' time
     */
    const LOCAL = 1;

    /**
     * UTC-based time
     */
    const UTC = 2;

    /**
     * Local time plus timezone
     */
    const LOCALTZ = 3;

    /**
     * Only a date, time is ignored
     */
    const DATE = 4;

}
