<?php

namespace Egulias\EmailValidator\Exception;

class ExpectingDomainLiteralClose extends InvalidEmail
{
    const CODE = 137;
    const REASON = "Closing bracket ']' for domain literal not found";
}
