<?php

namespace Egulias\EmailValidator\Exception;

class NoLocalPart extends InvalidEmail
{
    const CODE = 130;
    const REASON = "No local part";
}
