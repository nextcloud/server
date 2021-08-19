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

class PrintableString extends AbstractString
{
    /**
     * Creates a new ASN.1 PrintableString.
     *
     * The ITU-T X.680 Table 8 permits the following characters:
     * Latin capital letters A,B, ... Z
     * Latin small letters   a,b, ... z
     * Digits                0,1, ... 9
     * SPACE                 (space)
     * APOSTROPHE            '
     * LEFT PARENTHESIS      (
     * RIGHT PARENTHESIS     )
     * PLUS SIGN             +
     * COMMA                 ,
     * HYPHEN-MINUS          -
     * FULL STOP             .
     * SOLIDUS               /
     * COLON                 :
     * EQUALS SIGN           =
     * QUESTION MARK         ?
     *
     * @param string $string
     */
    public function __construct($string)
    {
        $this->value = $string;
        $this->allowNumbers();
        $this->allowAllLetters();
        $this->allowSpaces();
        $this->allowCharacters("'", '(', ')', '+', '-', '.', ',', '/', ':', '=', '?');
    }

    public function getType()
    {
        return Identifier::PRINTABLE_STRING;
    }
}
