<?php


namespace Office365\PHP\Client\SharePoint;


use Office365\PHP\Client\Runtime\Utilities\EnumType;

class FieldType extends EnumType
{
    const Invalid = 0;
    const Integer = 1;
    const Text = 2;
    const Note = 3;
    const DateTime = 4;
    const Counter = 5;
    const Choice = 6;
    const Lookup = 7;
    const Boolean = 8;
    const Number = 9;
    const Currency = 10;
    const URL = 11;
    const Computed = 12;
    const Threading = 13;
    const Guid = 14;
    const MultiChoice = 15;
    const GridChoice = 16;
    const Calculated = 17;
    const File = 18;
    const Attachments = 19;
    const User = 20;
    const Recurrence = 21;
    const CrossProjectLink = 22;
    const ModStat = 23;
    const Error = 24;
    const ContentTypeId = 25;
    const PageSeparator = 26;
    const ThreadIndex = 27;
    const WorkflowStatus = 28;
    const AllDayEvent = 29;
    const WorkflowEventType = 30;
    const Geolocation = 31;
    const OutcomeChoice = 32;
    const MaxItems = 33;
}