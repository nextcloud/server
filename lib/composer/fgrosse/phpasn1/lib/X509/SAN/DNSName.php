<?php
/*
 * This file is part of the PHPASN1 library.
 *
 * Copyright © Friedrich Große <friedrich.grosse@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FG\X509\SAN;

use FG\ASN1\Universal\GeneralString;

class DNSName extends GeneralString
{
    const IDENTIFIER = 0x82; // not sure yet why this is the identifier used in SAN extensions

    public function __construct($dnsNameString)
    {
        parent::__construct($dnsNameString);
    }

    public function getType()
    {
        return self::IDENTIFIER;
    }
}
