<?php


namespace Office365\PHP\Client\OutlookServices;


class ChangeType
{
    const Created = 1;
    const Updated = 2;
    const Deleted = 4;
    const Acknowledgment = 8;
    const Missed = 16;
}