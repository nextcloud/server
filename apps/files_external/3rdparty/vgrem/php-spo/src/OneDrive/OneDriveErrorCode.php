<?php

namespace Office365\PHP\Client\OneDrive;


abstract class OneDriveErrorCode
{
    const AccessDenied = 0;
    const ActivityLimitReached = 1;
    const GeneralException = 2;
    const InvalidRange = 3;
    const InvalidRequest = 4;
    const ItemNotFound = 5;
    const MalwareDetected = 6;
    const NameAlreadyExists = 7;
    const NotAllowed = 8;
    const NotSupported = 9;
    const ResourceModified = 10;
    const ResyncRequired = 11;
    const ServiceNotAvailable = 12;
    const Timeout = 13;
    const TooManyRedirects = 14;
    const QuotaLimitReached = 15;
    const Unauthenticated = 16;
}