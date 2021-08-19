<?php

namespace Egulias\EmailValidator\Warning;

class LocalTooLong extends Warning
{
    const CODE = 64;
    const LOCAL_PART_LENGTH = 64;

    public function __construct()
    {
        $this->message = 'Local part is too long, exceeds 64 chars (octets)';
        $this->rfcNumber = 5322;
    }
}
