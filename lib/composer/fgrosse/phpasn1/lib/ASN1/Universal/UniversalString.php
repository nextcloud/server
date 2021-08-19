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

class UniversalString extends AbstractString
{
    /**
     * Creates a new ASN.1 Universal String.
     * TODO The encodable characters of this type are not yet checked.
     *
     * @see http://en.wikipedia.org/wiki/Universal_Character_Set
     *
     * @param string $string
     */
    public function __construct($string)
    {
        $this->value = $string;
        $this->allowAll();
    }

    public function getType()
    {
        return Identifier::UNIVERSAL_STRING;
    }
}
