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

use FG\ASN1\Composite\RelativeDistinguishedName;
use FG\ASN1\Identifier;
use FG\ASN1\OID;
use FG\ASN1\Parsable;
use FG\ASN1\Composite\RDNString;
use FG\ASN1\Universal\Sequence;

class CertificateSubject extends Sequence implements Parsable
{
    private $commonName;
    private $email;
    private $organization;
    private $locality;
    private $state;
    private $country;
    private $organizationalUnit;

    /**
     * @param string $commonName
     * @param string $email
     * @param string $organization
     * @param string $locality
     * @param string $state
     * @param string $country
     * @param string $organizationalUnit
     */
    public function __construct($commonName, $email, $organization, $locality, $state, $country, $organizationalUnit)
    {
        parent::__construct(
            new RDNString(OID::COUNTRY_NAME, $country),
            new RDNString(OID::STATE_OR_PROVINCE_NAME, $state),
            new RDNString(OID::LOCALITY_NAME, $locality),
            new RDNString(OID::ORGANIZATION_NAME, $organization),
            new RDNString(OID::OU_NAME, $organizationalUnit),
            new RDNString(OID::COMMON_NAME, $commonName),
            new RDNString(OID::PKCS9_EMAIL, $email)
        );

        $this->commonName = $commonName;
        $this->email = $email;
        $this->organization = $organization;
        $this->locality = $locality;
        $this->state = $state;
        $this->country = $country;
        $this->organizationalUnit = $organizationalUnit;
    }

    public function getCommonName()
    {
        return $this->commonName;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function getOrganization()
    {
        return $this->organization;
    }

    public function getLocality()
    {
        return $this->locality;
    }

    public function getState()
    {
        return $this->state;
    }

    public function getCountry()
    {
        return $this->country;
    }

    public function getOrganizationalUnit()
    {
        return $this->organizationalUnit;
    }

    public static function fromBinary(&$binaryData, &$offsetIndex = 0)
    {
        self::parseIdentifier($binaryData[$offsetIndex], Identifier::SEQUENCE, $offsetIndex++);
        $contentLength = self::parseContentLength($binaryData, $offsetIndex);

        $names = [];
        $octetsToRead = $contentLength;
        while ($octetsToRead > 0) {
            $relativeDistinguishedName = RelativeDistinguishedName::fromBinary($binaryData, $offsetIndex);
            $octetsToRead -= $relativeDistinguishedName->getObjectLength();
            $names[] = $relativeDistinguishedName;
        }
    }
}
