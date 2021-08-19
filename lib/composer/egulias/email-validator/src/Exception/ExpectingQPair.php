<?php

namespace Egulias\EmailValidator\Exception;

class ExpectingQPair extends InvalidEmail
{
    const CODE = 136;
    const REASON = "Expecting QPAIR";
}
