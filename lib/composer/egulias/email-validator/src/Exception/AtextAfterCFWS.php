<?php

namespace Egulias\EmailValidator\Exception;

class AtextAfterCFWS extends InvalidEmail
{
    const CODE = 133;
    const REASON = "ATEXT found after CFWS";
}
