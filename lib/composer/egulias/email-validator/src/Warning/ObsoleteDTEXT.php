<?php

namespace Egulias\EmailValidator\Warning;

class ObsoleteDTEXT extends Warning
{
    const CODE = 71;

    public function __construct()
    {
        $this->rfcNumber = 5322;
        $this->message = 'Obsolete DTEXT in domain literal';
    }
}
