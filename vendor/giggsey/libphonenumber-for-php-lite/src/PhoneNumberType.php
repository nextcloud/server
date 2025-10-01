<?php

declare(strict_types=1);

namespace libphonenumber;

/**
 * Type of phone numbers.
 */
enum PhoneNumberType: int
{
    case FIXED_LINE = 0;
    case MOBILE = 1;
    /**
     * In some regions (e.g. the USA), it is impossible to distinguish between fixed-line and
     * mobile numbers by looking at the phone number itself.
     */
    case FIXED_LINE_OR_MOBILE = 2;
    /**
     * Freephone lines
     */
    case TOLL_FREE = 3;
    case PREMIUM_RATE = 4;
    /**
     * The cost of this call is shared between the caller and the recipient, and is hence typically
     * less than PREMIUM_RATE calls. See // http://en.wikipedia.org/wiki/Shared_Cost_Service for
     * more information.
     */
    case SHARED_COST = 5;
    /**
     * Voice over IP numbers. This includes TSoIP (Telephony Service over IP).
     */
    case VOIP = 6;
    /**
     * A personal number is associated with a particular person, and may be routed to either a
     * MOBILE or FIXED_LINE number. Some more information can be found here:
     * http://en.wikipedia.org/wiki/Personal_Numbers
     */
    case PERSONAL_NUMBER = 7;
    case PAGER = 8;
    /**
     * Used for "Universal Access Numbers" or "Company Numbers". They may be further routed to
     * specific offices, but allow one number to be used for a company.
     */
    case UAN = 9;
    /**
     * A phone number is of type UNKNOWN when it does not fit any of the known patterns for a
     * specific region.
     */
    case UNKNOWN = 10;

    case EMERGENCY = 27;
    case VOICEMAIL = 28;
    case SHORT_CODE = 29;
    case STANDARD_RATE = 30;
}
