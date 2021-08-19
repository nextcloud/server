<?php
/*
 * This file is part of the PHPASN1 library.
 *
 * Copyright © Friedrich Große <friedrich.grosse@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FG\ASN1\Universal;

use FG\ASN1\AbstractString;
use FG\ASN1\Identifier;

class NumericString extends AbstractString
{
    /**
     * Creates a new ASN.1 NumericString.
     *
     * The following characters are permitted:
     * Digits                0,1, ... 9
     * SPACE                 (space)
     *
     * @param string $string
     */
    public function __construct($string)
    {
        $this->value = $string;
        $this->allowNumbers();
        $this->allowSpaces();
    }

    public function getType()
    {
        return Identifier::NUMERIC_STRING;
    }
}
