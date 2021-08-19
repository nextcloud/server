<?php

namespace Egulias\EmailValidator\Exception;

class NoDomainPart extends InvalidEmail
{
    const CODE = 131;
    const REASON = "No Domain part";
}
