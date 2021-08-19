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

use FG\ASN1\Exception\ParserException;
use FG\ASN1\ASNObject;
use FG\ASN1\OID;
use FG\ASN1\Parsable;
use FG\ASN1\Identifier;
use FG\ASN1\Universal\Sequence;

/**
 * See section 8.3.2.1 of ITU-T X.509.
 */
class SubjectAlternativeNames extends ASNObject implements Parsable
{
    private $alternativeNamesSequence;

    public function __construct()
    {
        $this->alternativeNamesSequence = new Sequence();
    }

    protected function calculateContentLength()
    {
        return $this->alternativeNamesSequence->getObjectLength();
    }

    public function getType()
    {
        return Identifier::OCTETSTRING;
    }

    public function addDomainName(DNSName $domainName)
    {
        $this->alternativeNamesSequence->addChild($domainName);
    }

    public function addIP(IPAddress $ip)
    {
        $this->alternativeNamesSequence->addChild($ip);
    }

    public function getContent()
    {
        return $this->alternativeNamesSequence->getContent();
    }

    protected function getEncodedValue()
    {
        return $this->alternativeNamesSequence->getBinary();
    }

    public static function fromBinary(&$binaryData, &$offsetIndex = 0)
    {
        self::parseIdentifier($binaryData[$offsetIndex], Identifier::OCTETSTRING, $offsetIndex++);
        $contentLength = self::parseContentLength($binaryData, $offsetIndex);

        if ($contentLength < 2) {
            throw new ParserException('Can not parse Subject Alternative Names: The Sequence within the octet string after the Object identifier '.OID::CERT_EXT_SUBJECT_ALT_NAME." is too short ({$contentLength} octets)", $offsetIndex);
        }

        $offsetOfSequence = $offsetIndex;
        $sequence = Sequence::fromBinary($binaryData, $offsetIndex);
        $offsetOfSequence += $sequence->getNumberOfLengthOctets() + 1;

        if ($sequence->getObjectLength() != $contentLength) {
            throw new ParserException('Can not parse Subject Alternative Names: The Sequence length does not match the length of the surrounding octet string', $offsetIndex);
        }

        $parsedObject = new self();
        /** @var \FG\ASN1\ASNObject $object */
        foreach ($sequence as $object) {
            if ($object->getType() == DNSName::IDENTIFIER) {
                $domainName = DNSName::fromBinary($binaryData, $offsetOfSequence);
                $parsedObject->addDomainName($domainName);
            } elseif ($object->getType() == IPAddress::IDENTIFIER) {
                $ip = IPAddress::fromBinary($binaryData, $offsetOfSequence);
                $parsedObject->addIP($ip);
            } else {
                throw new ParserException('Could not parse Subject Alternative Name: Only DNSName and IP SANs are currently supported', $offsetIndex);
            }
        }

        $parsedObject->getBinary(); // Determine the number of content octets and object sizes once (just to let the equality unit tests pass :/ )
        return $parsedObject;
    }
}
