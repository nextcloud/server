<?php

namespace Egulias\EmailValidator\Warning;

class IPV6BadChar extends Warning
{
    const CODE = 74;

    public function __construct()
    {
        $this->message = 'Bad char in IPV6 domain literal';
        $this->rfcNumber = 5322;
    }
}
