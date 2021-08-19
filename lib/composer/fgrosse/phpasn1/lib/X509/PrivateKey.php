<?php
/*
 * This file is part of the PHPASN1 library.
 *
 * Copyright © Friedrich Große <friedrich.grosse@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FG\X509;

use FG\ASN1\OID;
use FG\ASN1\Universal\NullObject;
use FG\ASN1\Universal\Sequence;
use FG\ASN1\Universal\BitString;
use FG\ASN1\Universal\ObjectIdentifier;

class PrivateKey extends Sequence
{
    /**
     * @param string $hexKey
     * @param \FG\ASN1\ASNObject|string $algorithmIdentifierString
     */
    public function __construct($hexKey, $algorithmIdentifierString = OID::RSA_ENCRYPTION)
    {
        parent::__construct(
            new Sequence(
                new ObjectIdentifier($algorithmIdentifierString),
                new NullObject()
            ),
            new BitString($hexKey)
        );
    }
}
