<?php
/*
 * This file is part of the PHPASN1 library.
 *
 * Copyright © Friedrich Große <friedrich.grosse@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FG\X509\CSR;

use FG\ASN1\ASNObject;
use FG\X509\CertificateExtensions;
use FG\ASN1\OID;
use FG\ASN1\Parsable;
use FG\ASN1\Construct;
use FG\ASN1\Identifier;
use FG\ASN1\Universal\Set;
use FG\ASN1\Universal\Sequence;
use FG\ASN1\Universal\ObjectIdentifier;

class Attributes extends Construct implements Parsable
{
    public function getType()
    {
        return 0xA0;
    }

    public function addAttribute($objectIdentifier, Set $attribute)
    {
        if (is_string($objectIdentifier)) {
            $objectIdentifier = new ObjectIdentifier($objectIdentifier);
        }
        $attributeSequence = new Sequence($objectIdentifier, $attribute);
        $attributeSequence->getNumberOfLengthOctets();  // length and number of length octets is calculated
        $this->addChild($attributeSequence);
    }

    public static function fromBinary(&$binaryData, &$offsetIndex = 0)
    {
        self::parseIdentifier($binaryData[$offsetIndex], 0xA0, $offsetIndex++);
        $contentLength = self::parseContentLength($binaryData, $offsetIndex);
        $octetsToRead = $contentLength;

        $parsedObject = new self();
        while ($octetsToRead > 0) {
            $initialOffset = $offsetIndex; // used to calculate how much bits have been read
            self::parseIdentifier($binaryData[$offsetIndex], Identifier::SEQUENCE, $offsetIndex++);
            self::parseContentLength($binaryData, $offsetIndex);

            $objectIdentifier = ObjectIdentifier::fromBinary($binaryData, $offsetIndex);
            $oidString = $objectIdentifier->getContent();
            if ($oidString == OID::PKCS9_EXTENSION_REQUEST) {
                $attribute = CertificateExtensions::fromBinary($binaryData, $offsetIndex);
            } else {
                $attribute = ASNObject::fromBinary($binaryData, $offsetIndex);
            }

            $parsedObject->addAttribute($objectIdentifier, $attribute);
            $octetsToRead -= ($offsetIndex - $initialOffset);
        }

        $parsedObject->setContentLength($contentLength);

        return $parsedObject;
    }
}
