<?php

namespace Egulias\EmailValidator\Exception;

class DotAtEnd extends InvalidEmail
{
    const CODE = 142;
    const REASON = "Dot at the end";
}
