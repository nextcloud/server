<?php

namespace Egulias\EmailValidator\Exception;

class UnclosedQuotedString extends InvalidEmail
{
    const CODE = 145;
    const REASON = "Unclosed quoted string";
}
