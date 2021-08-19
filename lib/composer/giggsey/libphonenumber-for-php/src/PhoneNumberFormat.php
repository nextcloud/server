<?php

namespace libphonenumber;

/**
 * INTERNATIONAL and NATIONAL formats are consistent with the definition in ITU-T Recommendation
 * E123. For example, the number of the Google Switzerland office will be written as
 * "+41 44 668 1800" in INTERNATIONAL format, and as "044 668 1800" in NATIONAL format.
 * E164 format is as per INTERNATIONAL format but with no formatting applied, e.g.
 * "+41446681800". RFC3966 is as per INTERNATIONAL format, but with all spaces and other
 * separating symbols replaced with a hyphen, and with any phone number extension appended with
 * ";ext=". It also will have a prefix of "tel:" added, e.g. "tel:+41-44-668-1800".
 *
 * Note: If you are considering storing the number in a neutral format, you are highly advised to
 * use the PhoneNumber class.
 */
class PhoneNumberFormat
{
    const E164 = 0;
    const INTERNATIONAL = 1;
    const NATIONAL = 2;
    const RFC3966 = 3;
}
