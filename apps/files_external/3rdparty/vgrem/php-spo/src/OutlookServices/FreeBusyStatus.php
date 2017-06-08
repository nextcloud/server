<?php


namespace Office365\PHP\Client\OutlookServices;

/**
 * Specifies the availability status of an attendee for a meeting.
 */
class FreeBusyStatus
{
    const Unknown = -1;
    const Free = 0;
    const Tentative = 1;
    const Busy = 2;
    const Oof = 3;
    const WorkingElsewhere = 4;
}