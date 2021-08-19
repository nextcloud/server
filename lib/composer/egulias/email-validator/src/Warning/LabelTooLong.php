<?php

namespace Egulias\EmailValidator\Warning;

class LabelTooLong extends Warning
{
    const CODE = 63;

    public function __construct()
    {
        $this->message = 'Label too long';
        $this->rfcNumber = 5322;
    }
}
