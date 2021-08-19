<?php

namespace Egulias\EmailValidator\Exception;

class ExpectingDTEXT extends InvalidEmail
{
    const CODE = 129;
    const REASON = "Expected DTEXT";
}
