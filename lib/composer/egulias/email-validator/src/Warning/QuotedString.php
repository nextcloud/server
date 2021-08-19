<?php

namespace Egulias\EmailValidator\Warning;

class QuotedString extends Warning
{
    const CODE = 11;

    /**
     * @param scalar $prevToken
     * @param scalar $postToken
     */
    public function __construct($prevToken, $postToken)
    {
        $this->message = "Quoted String found between $prevToken and $postToken";
    }
}
