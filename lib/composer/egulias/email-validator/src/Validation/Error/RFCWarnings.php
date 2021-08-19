<?php

namespace Egulias\EmailValidator\Validation\Error;

use Egulias\EmailValidator\Exception\InvalidEmail;

class RFCWarnings extends InvalidEmail
{
    const CODE = 997;
    const REASON = 'Warnings were found.';
}
