<?php

namespace Sabre\VObject\Component;

use Sabre\VObject;

/**
 * The VFreeBusy component
 *
 * This component adds functionality to a component, specific for VFREEBUSY
 * components.
 *
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class VFreeBusy extends VObject\Component {

    /**
     * Checks based on the contained FREEBUSY information, if a timeslot is
     * available.
     *
     * @param DateTime $start
     * @param Datetime $end
     * @return bool
     */
    public function isFree(\DateTime $start, \Datetime $end) {

        foreach($this->select('FREEBUSY') as $freebusy) {

            // We are only interested in FBTYPE=BUSY (the default),
            // FBTYPE=BUSY-TENTATIVE or FBTYPE=BUSY-UNAVAILABLE.
            if (isset($freebusy['FBTYPE']) && strtoupper(substr((string)$freebusy['FBTYPE'],0,4))!=='BUSY') {
                continue;
            }

            // The freebusy component can hold more than 1 value, separated by
            // commas.
            $periods = explode(',', (string)$freebusy);

            foreach($periods as $period) {
                // Every period is formatted as [start]/[end]. The start is an
                // absolute UTC time, the end may be an absolute UTC time, or
                // duration (relative) value.
                list($busyStart, $busyEnd) = explode('/', $period);

                $busyStart = VObject\DateTimeParser::parse($busyStart);
                $busyEnd = VObject\DateTimeParser::parse($busyEnd);
                if ($busyEnd instanceof \DateInterval) {
                    $tmp = clone $busyStart;
                    $tmp->add($busyEnd);
                    $busyEnd = $tmp;
                }

                if($start < $busyEnd && $end > $busyStart) {
                    return false;
                }

            }

        }

        return true;

    }

}

