<?php


namespace Office365\PHP\Client\OutlookServices;


class MeetingMessageType
{
    const None = "None";
    const MeetingRequest = "MeetingRequest";
    const MeetingCancelled = "MeetingCancelled";
    const MeetingAccepted = "MeetingAccepted";
    const MeetingTentativelyAccepted = "MeetingTentativelyAccepted";
    const MeetingDeclined = "MeetingDeclined";
}