<?php

namespace Egulias\EmailValidator\Exception;

class DomainHyphened extends InvalidEmail
{
    const CODE = 144;
    const REASON = "Hyphen found in domain";
}
