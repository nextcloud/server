<?php

namespace Egulias\EmailValidator\Exception;

class ConsecutiveDot extends InvalidEmail
{
    const CODE = 132;
    const REASON = "Consecutive DOT";
}
