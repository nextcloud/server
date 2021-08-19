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

/**
 * The International Alphabet No.5 (IA5) references the encoding of the ASCII characters.
 *
 * Each character in the data is encoded as 1 byte.
 */
class IA5String extends AbstractString
{
    public function __construct($string)
    {
        parent::__construct($string);
        for ($i = 1; $i < 128; $i++) {
            $this->allowCharacter(chr($i));
        }
    }

    public function getType()
    {
        return Identifier::IA5_STRING;
    }
}
