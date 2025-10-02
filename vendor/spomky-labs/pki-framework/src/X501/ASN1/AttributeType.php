<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\X501\ASN1;

use OutOfBoundsException;
use SpomkyLabs\Pki\ASN1\Element;
use SpomkyLabs\Pki\ASN1\Type\Primitive\ObjectIdentifier;
use SpomkyLabs\Pki\ASN1\Type\Primitive\PrintableString;
use SpomkyLabs\Pki\ASN1\Type\Primitive\UTF8String;
use SpomkyLabs\Pki\ASN1\Type\StringType;
use function array_key_exists;

/**
 * Implements *AttributeType* ASN.1 type.
 *
 * @see https://www.itu.int/ITU-T/formal-language/itu-t/x/x501/2012/InformationFramework.html#InformationFramework.AttributeType
 */
final class AttributeType
{
    // OID's from 2.5.4 arc
    final public const OID_OBJECT_CLASS = '2.5.4.0';

    final public const OID_ALIASED_ENTRY_NAME = '2.5.4.1';

    final public const OID_KNOWLEDGE_INFORMATION = '2.5.4.2';

    final public const OID_COMMON_NAME = '2.5.4.3';

    final public const OID_SURNAME = '2.5.4.4';

    final public const OID_SERIAL_NUMBER = '2.5.4.5';

    final public const OID_COUNTRY_NAME = '2.5.4.6';

    final public const OID_LOCALITY_NAME = '2.5.4.7';

    final public const OID_STATE_OR_PROVINCE_NAME = '2.5.4.8';

    final public const OID_STREET_ADDRESS = '2.5.4.9';

    final public const OID_ORGANIZATION_NAME = '2.5.4.10';

    final public const OID_ORGANIZATIONAL_UNIT_NAME = '2.5.4.11';

    final public const OID_TITLE = '2.5.4.12';

    final public const OID_DESCRIPTION = '2.5.4.13';

    final public const OID_SEARCH_GUIDE = '2.5.4.14';

    final public const OID_BUSINESS_CATEGORY = '2.5.4.15';

    final public const OID_POSTAL_ADDRESS = '2.5.4.16';

    final public const OID_POSTAL_CODE = '2.5.4.17';

    final public const OID_POST_OFFICE_BOX = '2.5.4.18';

    final public const OID_PHYSICAL_DELIVERY_OFFICE_NAME = '2.5.4.19';

    final public const OID_TELEPHONE_NUMBER = '2.5.4.20';

    final public const OID_TELEX_NUMBER = '2.5.4.21';

    final public const OID_TELETEX_TERMINAL_IDENTIFIER = '2.5.4.22';

    final public const OID_FACSIMILE_TELEPHONE_NUMBER = '2.5.4.23';

    final public const OID_X121_ADDRESS = '2.5.4.24';

    final public const OID_INTERNATIONAL_ISDN_NUMBER = '2.5.4.25';

    final public const OID_REGISTERED_ADDRESS = '2.5.4.26';

    final public const OID_DESTINATION_INDICATOR = '2.5.4.27';

    final public const OID_PREFERRED_DELIVERY_METHOD = '2.5.4.28';

    final public const OID_PRESENTATION_ADDRESS = '2.5.4.29';

    final public const OID_SUPPORTED_APPLICATION_CONTEXT = '2.5.4.30';

    final public const OID_MEMBER = '2.5.4.31';

    final public const OID_OWNER = '2.5.4.32';

    final public const OID_ROLE_OCCUPANT = '2.5.4.33';

    final public const OID_SEE_ALSO = '2.5.4.34';

    final public const OID_USER_PASSWORD = '2.5.4.35';

    final public const OID_USER_CERTIFICATE = '2.5.4.36';

    final public const OID_CA_CERTIFICATE = '2.5.4.37';

    final public const OID_AUTHORITY_REVOCATION_LIST = '2.5.4.38';

    final public const OID_CERTIFICATE_REVOCATION_LIST = '2.5.4.39';

    final public const OID_CROSS_CERTIFICATE_PAIR = '2.5.4.40';

    final public const OID_NAME = '2.5.4.41';

    final public const OID_GIVEN_NAME = '2.5.4.42';

    final public const OID_INITIALS = '2.5.4.43';

    final public const OID_GENERATION_QUALIFIER = '2.5.4.44';

    final public const OID_UNIQUE_IDENTIFIER = '2.5.4.45';

    final public const OID_DN_QUALIFIER = '2.5.4.46';

    final public const OID_ENHANCED_SEARCH_GUIDE = '2.5.4.47';

    final public const OID_PROTOCOL_INFORMATION = '2.5.4.48';

    final public const OID_DISTINGUISHED_NAME = '2.5.4.49';

    final public const OID_UNIQUE_MEMBER = '2.5.4.50';

    final public const OID_HOUSE_IDENTIFIER = '2.5.4.51';

    final public const OID_SUPPORTED_ALGORITHMS = '2.5.4.52';

    final public const OID_DELTA_REVOCATION_LIST = '2.5.4.53';

    final public const OID_DMD_NAME = '2.5.4.54';

    final public const OID_CLEARANCE = '2.5.4.55';

    final public const OID_DEFAULT_DIR_QOP = '2.5.4.56';

    final public const OID_ATTRIBUTE_INTEGRITY_INFO = '2.5.4.57';

    final public const OID_ATTRIBUTE_CERTIFICATE = '2.5.4.58';

    final public const OID_ATTRIBUTE_CERTIFICATE_REVOCATION_LIST = '2.5.4.59';

    final public const OID_CONF_KEY_INFO = '2.5.4.60';

    final public const OID_AA_CERTIFICATE = '2.5.4.61';

    final public const OID_ATTRIBUTE_DESCRIPTOR_CERTIFICATE = '2.5.4.62';

    final public const OID_ATTRIBUTE_AUTHORITY_REVOCATION_LIST = '2.5.4.63';

    final public const OID_FAMILY_INFORMATION = '2.5.4.64';

    final public const OID_PSEUDONYM = '2.5.4.65';

    final public const OID_COMMUNICATIONS_SERVICE = '2.5.4.66';

    final public const OID_COMMUNICATIONS_NETWORK = '2.5.4.67';

    final public const OID_CERTIFICATION_PRACTICE_STMT = '2.5.4.68';

    final public const OID_CERTIFICATE_POLICY = '2.5.4.69';

    final public const OID_PKI_PATH = '2.5.4.70';

    final public const OID_PRIV_POLICY = '2.5.4.71';

    final public const OID_ROLE = '2.5.4.72';

    final public const OID_DELEGATION_PATH = '2.5.4.73';

    final public const OID_PROT_PRIV_POLICY = '2.5.4.74';

    final public const OID_XML_PRIVILEGE_INFO = '2.5.4.75';

    final public const OID_XML_PRIV_POLICY = '2.5.4.76';

    final public const OID_UUID_PAIR = '2.5.4.77';

    final public const OID_TAG_OID = '2.5.4.78';

    final public const OID_UII_FORMAT = '2.5.4.79';

    final public const OID_UII_IN_URH = '2.5.4.80';

    final public const OID_CONTENT_URL = '2.5.4.81';

    final public const OID_PERMISSION = '2.5.4.82';

    final public const OID_URI = '2.5.4.83';

    final public const OID_PWD_ATTRIBUTE = '2.5.4.84';

    final public const OID_USER_PWD = '2.5.4.85';

    final public const OID_URN = '2.5.4.86';

    final public const OID_URL = '2.5.4.87';

    final public const OID_UTM_COORDINATES = '2.5.4.88';

    final public const OID_URNC = '2.5.4.89';

    final public const OID_UII = '2.5.4.90';

    final public const OID_EPC = '2.5.4.91';

    final public const OID_TAG_AFI = '2.5.4.92';

    final public const OID_EPC_FORMAT = '2.5.4.93';

    final public const OID_EPC_IN_URN = '2.5.4.94';

    final public const OID_LDAP_URL = '2.5.4.95';

    final public const OID_TAG_LOCATION = '2.5.4.96';

    final public const OID_ORGANIZATION_IDENTIFIER = '2.5.4.97';

    // Miscellany attribute OID's
    final public const OID_CLEARANCE_X501 = '2.5.1.5.55';

    /**
     * Default ASN.1 string types for attributes.
     *
     * Attributes not mapped here shall use UTF8String as a default type.
     *
     * @internal
     *
     * @var array<string, int>
     */
    private const MAP_ATTR_TO_STR_TYPE = [
        self::OID_DN_QUALIFIER => Element::TYPE_PRINTABLE_STRING,
        self::OID_COUNTRY_NAME => Element::TYPE_PRINTABLE_STRING,
        self::OID_SERIAL_NUMBER => Element::TYPE_PRINTABLE_STRING,
    ];

    /**
     * OID to attribute names mapping.
     *
     * First name is the primary name. If there's more than one name, others may be used as an alias.
     *
     * Generated using ldap-attribs.py.
     *
     * @internal
     *
     * @var array<string, array<string>>
     */
    private const MAP_OID_TO_NAME = [
        '0.9.2342.19200300.100.1.1' => ['uid', 'userid'],
        '0.9.2342.19200300.100.1.2' => ['textEncodedORAddress'],
        '0.9.2342.19200300.100.1.3' => ['mail', 'rfc822Mailbox'],
        '0.9.2342.19200300.100.1.4' => ['info'],
        '0.9.2342.19200300.100.1.5' => ['drink', 'favouriteDrink'],
        '0.9.2342.19200300.100.1.6' => ['roomNumber'],
        '0.9.2342.19200300.100.1.7' => ['photo'],
        '0.9.2342.19200300.100.1.8' => ['userClass'],
        '0.9.2342.19200300.100.1.9' => ['host'],
        '0.9.2342.19200300.100.1.10' => ['manager'],
        '0.9.2342.19200300.100.1.11' => ['documentIdentifier'],
        '0.9.2342.19200300.100.1.12' => ['documentTitle'],
        '0.9.2342.19200300.100.1.13' => ['documentVersion'],
        '0.9.2342.19200300.100.1.14' => ['documentAuthor'],
        '0.9.2342.19200300.100.1.15' => ['documentLocation'],
        '0.9.2342.19200300.100.1.20' => ['homePhone', 'homeTelephoneNumber'],
        '0.9.2342.19200300.100.1.21' => ['secretary'],
        '0.9.2342.19200300.100.1.22' => ['otherMailbox'],
        '0.9.2342.19200300.100.1.25' => ['dc', 'domainComponent'],
        '0.9.2342.19200300.100.1.26' => ['aRecord'],
        '0.9.2342.19200300.100.1.27' => ['mDRecord'],
        '0.9.2342.19200300.100.1.28' => ['mXRecord'],
        '0.9.2342.19200300.100.1.29' => ['nSRecord'],
        '0.9.2342.19200300.100.1.30' => ['sOARecord'],
        '0.9.2342.19200300.100.1.31' => ['cNAMERecord'],
        '0.9.2342.19200300.100.1.37' => ['associatedDomain'],
        '0.9.2342.19200300.100.1.38' => ['associatedName'],
        '0.9.2342.19200300.100.1.39' => ['homePostalAddress'],
        '0.9.2342.19200300.100.1.40' => ['personalTitle'],
        '0.9.2342.19200300.100.1.41' => ['mobile', 'mobileTelephoneNumber'],
        '0.9.2342.19200300.100.1.42' => ['pager', 'pagerTelephoneNumber'],
        '0.9.2342.19200300.100.1.43' => ['co', 'friendlyCountryName'],
        '0.9.2342.19200300.100.1.44' => ['uniqueIdentifier'],
        '0.9.2342.19200300.100.1.45' => ['organizationalStatus'],
        '0.9.2342.19200300.100.1.46' => ['janetMailbox'],
        '0.9.2342.19200300.100.1.47' => ['mailPreferenceOption'],
        '0.9.2342.19200300.100.1.48' => ['buildingName'],
        '0.9.2342.19200300.100.1.49' => ['dSAQuality'],
        '0.9.2342.19200300.100.1.50' => ['singleLevelQuality'],
        '0.9.2342.19200300.100.1.51' => ['subtreeMinimumQuality'],
        '0.9.2342.19200300.100.1.52' => ['subtreeMaximumQuality'],
        '0.9.2342.19200300.100.1.53' => ['personalSignature'],
        '0.9.2342.19200300.100.1.54' => ['dITRedirect'],
        '0.9.2342.19200300.100.1.55' => ['audio'],
        '0.9.2342.19200300.100.1.56' => ['documentPublisher'],
        '0.9.2342.19200300.100.1.60' => ['jpegPhoto'],
        '1.2.840.113549.1.9.1' => ['email', 'emailAddress', 'pkcs9email'],
        '1.2.840.113556.1.2.102' => ['memberOf'],
        '1.3.6.1.1.1.1.0' => ['uidNumber'],
        '1.3.6.1.1.1.1.1' => ['gidNumber'],
        '1.3.6.1.1.1.1.2' => ['gecos'],
        '1.3.6.1.1.1.1.3' => ['homeDirectory'],
        '1.3.6.1.1.1.1.4' => ['loginShell'],
        '1.3.6.1.1.1.1.5' => ['shadowLastChange'],
        '1.3.6.1.1.1.1.6' => ['shadowMin'],
        '1.3.6.1.1.1.1.7' => ['shadowMax'],
        '1.3.6.1.1.1.1.8' => ['shadowWarning'],
        '1.3.6.1.1.1.1.9' => ['shadowInactive'],
        '1.3.6.1.1.1.1.10' => ['shadowExpire'],
        '1.3.6.1.1.1.1.11' => ['shadowFlag'],
        '1.3.6.1.1.1.1.12' => ['memberUid'],
        '1.3.6.1.1.1.1.13' => ['memberNisNetgroup'],
        '1.3.6.1.1.1.1.14' => ['nisNetgroupTriple'],
        '1.3.6.1.1.1.1.15' => ['ipServicePort'],
        '1.3.6.1.1.1.1.16' => ['ipServiceProtocol'],
        '1.3.6.1.1.1.1.17' => ['ipProtocolNumber'],
        '1.3.6.1.1.1.1.18' => ['oncRpcNumber'],
        '1.3.6.1.1.1.1.19' => ['ipHostNumber'],
        '1.3.6.1.1.1.1.20' => ['ipNetworkNumber'],
        '1.3.6.1.1.1.1.21' => ['ipNetmaskNumber'],
        '1.3.6.1.1.1.1.22' => ['macAddress'],
        '1.3.6.1.1.1.1.23' => ['bootParameter'],
        '1.3.6.1.1.1.1.24' => ['bootFile'],
        '1.3.6.1.1.1.1.26' => ['nisMapName'],
        '1.3.6.1.1.1.1.27' => ['nisMapEntry'],
        '1.3.6.1.1.4' => ['vendorName'],
        '1.3.6.1.1.5' => ['vendorVersion'],
        '1.3.6.1.1.16.4' => ['entryUUID'],
        '1.3.6.1.1.20' => ['entryDN'],
        '2.5.4.0' => ['objectClass'],
        '2.5.4.1' => ['aliasedObjectName', 'aliasedEntryName'],
        '2.5.4.2' => ['knowledgeInformation'],
        '2.5.4.3' => ['cn', 'commonName'],
        '2.5.4.4' => ['sn', 'surname'],
        '2.5.4.5' => ['serialNumber'],
        '2.5.4.6' => ['c', 'countryName'],
        '2.5.4.7' => ['l', 'localityName'],
        '2.5.4.8' => ['st', 'stateOrProvinceName'],
        '2.5.4.9' => ['street', 'streetAddress'],
        '2.5.4.10' => ['o', 'organizationName'],
        '2.5.4.11' => ['ou', 'organizationalUnitName'],
        '2.5.4.12' => ['title'],
        '2.5.4.13' => ['description'],
        '2.5.4.14' => ['searchGuide'],
        '2.5.4.15' => ['businessCategory'],
        '2.5.4.16' => ['postalAddress'],
        '2.5.4.17' => ['postalCode'],
        '2.5.4.18' => ['postOfficeBox'],
        '2.5.4.19' => ['physicalDeliveryOfficeName'],
        '2.5.4.20' => ['telephoneNumber'],
        '2.5.4.21' => ['telexNumber'],
        '2.5.4.22' => ['teletexTerminalIdentifier'],
        '2.5.4.23' => ['facsimileTelephoneNumber', 'fax'],
        '2.5.4.24' => ['x121Address'],
        '2.5.4.25' => ['internationaliSDNNumber'],
        '2.5.4.26' => ['registeredAddress'],
        '2.5.4.27' => ['destinationIndicator'],
        '2.5.4.28' => ['preferredDeliveryMethod'],
        '2.5.4.29' => ['presentationAddress'],
        '2.5.4.30' => ['supportedApplicationContext'],
        '2.5.4.31' => ['member'],
        '2.5.4.32' => ['owner'],
        '2.5.4.33' => ['roleOccupant'],
        '2.5.4.34' => ['seeAlso'],
        '2.5.4.35' => ['userPassword'],
        '2.5.4.36' => ['userCertificate'],
        '2.5.4.37' => ['cACertificate'],
        '2.5.4.38' => ['authorityRevocationList'],
        '2.5.4.39' => ['certificateRevocationList'],
        '2.5.4.40' => ['crossCertificatePair'],
        '2.5.4.41' => ['name'],
        '2.5.4.42' => ['givenName', 'gn'],
        '2.5.4.43' => ['initials'],
        '2.5.4.44' => ['generationQualifier'],
        '2.5.4.45' => ['x500UniqueIdentifier'],
        '2.5.4.46' => ['dnQualifier'],
        '2.5.4.47' => ['enhancedSearchGuide'],
        '2.5.4.48' => ['protocolInformation'],
        '2.5.4.49' => ['distinguishedName'],
        '2.5.4.50' => ['uniqueMember'],
        '2.5.4.51' => ['houseIdentifier'],
        '2.5.4.52' => ['supportedAlgorithms'],
        '2.5.4.53' => ['deltaRevocationList'],
        '2.5.4.54' => ['dmdName'],
        '2.5.4.65' => ['pseudonym'],
        '2.5.18.1' => ['createTimestamp'],
        '2.5.18.2' => ['modifyTimestamp'],
        '2.5.18.3' => ['creatorsName'],
        '2.5.18.4' => ['modifiersName'],
        '2.5.18.5' => ['administrativeRole'],
        '2.5.18.6' => ['subtreeSpecification'],
        '2.5.18.9' => ['hasSubordinates'],
        '2.5.18.10' => ['subschemaSubentry'],
        '2.5.21.1' => ['dITStructureRules'],
        '2.5.21.2' => ['dITContentRules'],
        '2.5.21.4' => ['matchingRules'],
        '2.5.21.5' => ['attributeTypes'],
        '2.5.21.6' => ['objectClasses'],
        '2.5.21.7' => ['nameForms'],
        '2.5.21.8' => ['matchingRuleUse'],
        '2.5.21.9' => ['structuralObjectClass'],
        '2.16.840.1.113730.3.1.1' => ['carLicense'],
        '2.16.840.1.113730.3.1.2' => ['departmentNumber'],
        '2.16.840.1.113730.3.1.3' => ['employeeNumber'],
        '2.16.840.1.113730.3.1.4' => ['employeeType'],
        '2.16.840.1.113730.3.1.34' => ['ref'],
        '2.16.840.1.113730.3.1.39' => ['preferredLanguage'],
        '2.16.840.1.113730.3.1.40' => ['userSMIMECertificate'],
        '2.16.840.1.113730.3.1.216' => ['userPKCS12'],
        '2.16.840.1.113730.3.1.241' => ['displayName'],
    ];

    /**
     * @param string $_oid OID in dotted format
     */
    private function __construct(
        protected string $_oid
    ) {
    }

    public static function create(string $oid): self
    {
        return new self($oid);
    }

    /**
     * Initialize from ASN.1.
     */
    public static function fromASN1(ObjectIdentifier $oi): self
    {
        return self::create($oi->oid());
    }

    /**
     * Initialize from attribute name.
     */
    public static function fromName(string $name): self
    {
        $oid = self::attrNameToOID($name);
        return self::create($oid);
    }

    /**
     * Get OID of the attribute.
     *
     * @return string OID in dotted format
     */
    public function oid(): string
    {
        return $this->_oid;
    }

    /**
     * Get name of the attribute.
     */
    public function typeName(): string
    {
        if (array_key_exists($this->_oid, self::MAP_OID_TO_NAME)) {
            return self::MAP_OID_TO_NAME[$this->_oid][0];
        }
        return $this->_oid;
    }

    /**
     * Generate ASN.1 element.
     */
    public function toASN1(): ObjectIdentifier
    {
        return ObjectIdentifier::create($this->_oid);
    }

    /**
     * Convert attribute name to OID.
     *
     * @param string $name Primary attribute name or an alias
     *
     * @return string OID in dotted format
     */
    public static function attrNameToOID(string $name): string
    {
        // if already in OID form
        if (preg_match('/^[0-9]+(?:\.[0-9]+)*$/', $name) === 1) {
            return $name;
        }
        $map = self::_oidReverseMap();
        $k = mb_strtolower($name, '8bit');
        if (! isset($map[$k])) {
            throw new OutOfBoundsException("No OID for {$name}.");
        }
        return $map[$k];
    }

    /**
     * Get ASN.1 string for given attribute type.
     *
     * @param string $oid Attribute OID
     * @param string $str String
     */
    public static function asn1StringForType(string $oid, string $str): StringType
    {
        if (! array_key_exists($oid, self::MAP_ATTR_TO_STR_TYPE)) {
            return UTF8String::create($str);
        }
        return PrintableString::create($str);
    }

    /**
     * Get name to OID lookup map.
     *
     * @return array<string>
     */
    private static function _oidReverseMap(): array
    {
        static $map;
        if (! isset($map)) {
            $map = [];
            // for each attribute type
            foreach (self::MAP_OID_TO_NAME as $oid => $names) {
                // for primary name and aliases
                foreach ($names as $name) {
                    $map[mb_strtolower($name, '8bit')] = $oid;
                }
            }
        }
        return $map;
    }
}
