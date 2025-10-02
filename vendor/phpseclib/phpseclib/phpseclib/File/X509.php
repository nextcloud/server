<?php

/**
 * Pure-PHP X.509 Parser
 *
 * PHP version 5
 *
 * Encode and decode X.509 certificates.
 *
 * The extensions are from {@link http://tools.ietf.org/html/rfc5280 RFC5280} and
 * {@link http://web.archive.org/web/19961027104704/http://www3.netscape.com/eng/security/cert-exts.html Netscape Certificate Extensions}.
 *
 * Note that loading an X.509 certificate and resaving it may invalidate the signature.  The reason being that the signature is based on a
 * portion of the certificate that contains optional parameters with default values.  ie. if the parameter isn't there the default value is
 * used.  Problem is, if the parameter is there and it just so happens to have the default value there are two ways that that parameter can
 * be encoded.  It can be encoded explicitly or left out all together.  This would effect the signature value and thus may invalidate the
 * the certificate all together unless the certificate is re-signed.
 *
 * @category  File
 * @package   X509
 * @author    Jim Wigginton <terrafrost@php.net>
 * @copyright 2012 Jim Wigginton
 * @license   http://www.opensource.org/licenses/mit-license.html  MIT License
 * @link      http://phpseclib.sourceforge.net
 */

namespace phpseclib\File;

use phpseclib\Crypt\Hash;
use phpseclib\Crypt\Random;
use phpseclib\Crypt\RSA;
use phpseclib\File\ASN1\Element;
use phpseclib\Math\BigInteger;
use DateTime;
use DateTimeZone;

/**
 * Pure-PHP X.509 Parser
 *
 * @package X509
 * @author  Jim Wigginton <terrafrost@php.net>
 * @access  public
 */
class X509
{
    /**
     * Flag to only accept signatures signed by certificate authorities
     *
     * Not really used anymore but retained all the same to suppress E_NOTICEs from old installs
     *
     * @access public
     */
    const VALIDATE_SIGNATURE_BY_CA = 1;

    /**#@+
     * @access public
     * @see \phpseclib\File\X509::getDN()
    */
    /**
     * Return internal array representation
     */
    const DN_ARRAY = 0;
    /**
     * Return string
     */
    const DN_STRING = 1;
    /**
     * Return ASN.1 name string
     */
    const DN_ASN1 = 2;
    /**
     * Return OpenSSL compatible array
     */
    const DN_OPENSSL = 3;
    /**
     * Return canonical ASN.1 RDNs string
     */
    const DN_CANON = 4;
    /**
     * Return name hash for file indexing
     */
    const DN_HASH = 5;
    /**#@-*/

    /**#@+
     * @access public
     * @see \phpseclib\File\X509::saveX509()
     * @see \phpseclib\File\X509::saveCSR()
     * @see \phpseclib\File\X509::saveCRL()
    */
    /**
     * Save as PEM
     *
     * ie. a base64-encoded PEM with a header and a footer
     */
    const FORMAT_PEM = 0;
    /**
     * Save as DER
     */
    const FORMAT_DER = 1;
    /**
     * Save as a SPKAC
     *
     * Only works on CSRs. Not currently supported.
     */
    const FORMAT_SPKAC = 2;
    /**
     * Auto-detect the format
     *
     * Used only by the load*() functions
     */
    const FORMAT_AUTO_DETECT = 3;
    /**#@-*/

    /**
     * Attribute value disposition.
     * If disposition is >= 0, this is the index of the target value.
     */
    const ATTR_ALL = -1; // All attribute values (array).
    const ATTR_APPEND = -2; // Add a value.
    const ATTR_REPLACE = -3; // Clear first, then add a value.

    /**
     * ASN.1 syntax for X.509 certificates
     *
     * @var array
     * @access private
     */
    var $Certificate;

    /**#@+
     * ASN.1 syntax for various extensions
     *
     * @access private
     */
    var $DirectoryString;
    var $PKCS9String;
    var $AttributeValue;
    var $Extensions;
    var $KeyUsage;
    var $ExtKeyUsageSyntax;
    var $BasicConstraints;
    var $KeyIdentifier;
    var $CRLDistributionPoints;
    var $AuthorityKeyIdentifier;
    var $CertificatePolicies;
    var $AuthorityInfoAccessSyntax;
    var $SubjectInfoAccessSyntax;
    var $SubjectAltName;
    var $SubjectDirectoryAttributes;
    var $PrivateKeyUsagePeriod;
    var $IssuerAltName;
    var $PolicyMappings;
    var $NameConstraints;

    var $CPSuri;
    var $UserNotice;

    var $netscape_cert_type;
    var $netscape_comment;
    var $netscape_ca_policy_url;

    var $Name;
    var $RelativeDistinguishedName;
    var $CRLNumber;
    var $CRLReason;
    var $IssuingDistributionPoint;
    var $InvalidityDate;
    var $CertificateIssuer;
    var $HoldInstructionCode;
    var $SignedPublicKeyAndChallenge;
    /**#@-*/

    /**#@+
     * ASN.1 syntax for various DN attributes
     *
     * @access private
     */
    var $PostalAddress;
    /**#@-*/

    /**
     * ASN.1 syntax for Certificate Signing Requests (RFC2986)
     *
     * @var array
     * @access private
     */
    var $CertificationRequest;

    /**
     * ASN.1 syntax for Certificate Revocation Lists (RFC5280)
     *
     * @var array
     * @access private
     */
    var $CertificateList;

    /**
     * Distinguished Name
     *
     * @var array
     * @access private
     */
    var $dn;

    /**
     * Public key
     *
     * @var string
     * @access private
     */
    var $publicKey;

    /**
     * Private key
     *
     * @var string
     * @access private
     */
    var $privateKey;

    /**
     * Object identifiers for X.509 certificates
     *
     * @var array
     * @access private
     * @link http://en.wikipedia.org/wiki/Object_identifier
     */
    var $oids;

    /**
     * The certificate authorities
     *
     * @var array
     * @access private
     */
    var $CAs;

    /**
     * The currently loaded certificate
     *
     * @var array
     * @access private
     */
    var $currentCert;

    /**
     * The signature subject
     *
     * There's no guarantee \phpseclib\File\X509 is going to re-encode an X.509 cert in the same way it was originally
     * encoded so we take save the portion of the original cert that the signature would have made for.
     *
     * @var string
     * @access private
     */
    var $signatureSubject;

    /**
     * Certificate Start Date
     *
     * @var string
     * @access private
     */
    var $startDate;

    /**
     * Certificate End Date
     *
     * @var string
     * @access private
     */
    var $endDate;

    /**
     * Serial Number
     *
     * @var string
     * @access private
     */
    var $serialNumber;

    /**
     * Key Identifier
     *
     * See {@link http://tools.ietf.org/html/rfc5280#section-4.2.1.1 RFC5280#section-4.2.1.1} and
     * {@link http://tools.ietf.org/html/rfc5280#section-4.2.1.2 RFC5280#section-4.2.1.2}.
     *
     * @var string
     * @access private
     */
    var $currentKeyIdentifier;

    /**
     * CA Flag
     *
     * @var bool
     * @access private
     */
    var $caFlag = false;

    /**
     * SPKAC Challenge
     *
     * @var string
     * @access private
     */
    var $challenge;

    /**
     * Recursion Limit
     *
     * @var int
     * @access private
     */
    static $recur_limit = 5;

    /**
     * URL fetch flag
     *
     * @var bool
     * @access private
     */
    static $disable_url_fetch = false;

    /**
     * Default Constructor.
     *
     * @return \phpseclib\File\X509
     * @access public
     */
    function __construct()
    {
        // Explicitly Tagged Module, 1988 Syntax
        // http://tools.ietf.org/html/rfc5280#appendix-A.1

        $this->DirectoryString = array(
            'type'     => ASN1::TYPE_CHOICE,
            'children' => array(
                'teletexString'   => array('type' => ASN1::TYPE_TELETEX_STRING),
                'printableString' => array('type' => ASN1::TYPE_PRINTABLE_STRING),
                'universalString' => array('type' => ASN1::TYPE_UNIVERSAL_STRING),
                'utf8String'      => array('type' => ASN1::TYPE_UTF8_STRING),
                'bmpString'       => array('type' => ASN1::TYPE_BMP_STRING)
            )
        );

        $this->PKCS9String = array(
            'type'     => ASN1::TYPE_CHOICE,
            'children' => array(
                'ia5String'       => array('type' => ASN1::TYPE_IA5_STRING),
                'directoryString' => $this->DirectoryString
            )
        );

        $this->AttributeValue = array('type' => ASN1::TYPE_ANY);

        $AttributeType = array('type' => ASN1::TYPE_OBJECT_IDENTIFIER);

        $AttributeTypeAndValue = array(
            'type'     => ASN1::TYPE_SEQUENCE,
            'children' => array(
                'type' => $AttributeType,
                'value'=> $this->AttributeValue
            )
        );

        /*
        In practice, RDNs containing multiple name-value pairs (called "multivalued RDNs") are rare,
        but they can be useful at times when either there is no unique attribute in the entry or you
        want to ensure that the entry's DN contains some useful identifying information.

        - https://www.opends.org/wiki/page/DefinitionRelativeDistinguishedName
        */
        $this->RelativeDistinguishedName = array(
            'type'     => ASN1::TYPE_SET,
            'min'      => 1,
            'max'      => -1,
            'children' => $AttributeTypeAndValue
        );

        // http://tools.ietf.org/html/rfc5280#section-4.1.2.4
        $RDNSequence = array(
            'type'     => ASN1::TYPE_SEQUENCE,
            // RDNSequence does not define a min or a max, which means it doesn't have one
            'min'      => 0,
            'max'      => -1,
            'children' => $this->RelativeDistinguishedName
        );

        $this->Name = array(
            'type'     => ASN1::TYPE_CHOICE,
            'children' => array(
                'rdnSequence' => $RDNSequence
            )
        );

        // http://tools.ietf.org/html/rfc5280#section-4.1.1.2
        $AlgorithmIdentifier = array(
            'type'     => ASN1::TYPE_SEQUENCE,
            'children' => array(
                'algorithm'  => array('type' => ASN1::TYPE_OBJECT_IDENTIFIER),
                'parameters' => array(
                                    'type'     => ASN1::TYPE_ANY,
                                    'optional' => true
                                )
            )
        );

        /*
           A certificate using system MUST reject the certificate if it encounters
           a critical extension it does not recognize; however, a non-critical
           extension may be ignored if it is not recognized.

           http://tools.ietf.org/html/rfc5280#section-4.2
        */
        $Extension = array(
            'type'     => ASN1::TYPE_SEQUENCE,
            'children' => array(
                'extnId'   => array('type' => ASN1::TYPE_OBJECT_IDENTIFIER),
                'critical' => array(
                                  'type'     => ASN1::TYPE_BOOLEAN,
                                  'optional' => true,
                                  'default'  => false
                              ),
                'extnValue' => array('type' => ASN1::TYPE_OCTET_STRING)
            )
        );

        $this->Extensions = array(
            'type'     => ASN1::TYPE_SEQUENCE,
            'min'      => 1,
            // technically, it's MAX, but we'll assume anything < 0 is MAX
            'max'      => -1,
            // if 'children' isn't an array then 'min' and 'max' must be defined
            'children' => $Extension
        );

        $SubjectPublicKeyInfo = array(
            'type'     => ASN1::TYPE_SEQUENCE,
            'children' => array(
                'algorithm'        => $AlgorithmIdentifier,
                'subjectPublicKey' => array('type' => ASN1::TYPE_BIT_STRING)
            )
        );

        $UniqueIdentifier = array('type' => ASN1::TYPE_BIT_STRING);

        $Time = array(
            'type'     => ASN1::TYPE_CHOICE,
            'children' => array(
                'utcTime'     => array('type' => ASN1::TYPE_UTC_TIME),
                'generalTime' => array('type' => ASN1::TYPE_GENERALIZED_TIME)
            )
        );

        // http://tools.ietf.org/html/rfc5280#section-4.1.2.5
        $Validity = array(
            'type'     => ASN1::TYPE_SEQUENCE,
            'children' => array(
                'notBefore' => $Time,
                'notAfter'  => $Time
            )
        );

        $CertificateSerialNumber = array('type' => ASN1::TYPE_INTEGER);

        $Version = array(
            'type'    => ASN1::TYPE_INTEGER,
            'mapping' => array('v1', 'v2', 'v3')
        );

        // assert($TBSCertificate['children']['signature'] == $Certificate['children']['signatureAlgorithm'])
        $TBSCertificate = array(
            'type'     => ASN1::TYPE_SEQUENCE,
            'children' => array(
                // technically, default implies optional, but we'll define it as being optional, none-the-less, just to
                // reenforce that fact
                'version'             => array(
                                             'constant' => 0,
                                             'optional' => true,
                                             'explicit' => true,
                                             'default'  => 'v1'
                                         ) + $Version,
                'serialNumber'         => $CertificateSerialNumber,
                'signature'            => $AlgorithmIdentifier,
                'issuer'               => $this->Name,
                'validity'             => $Validity,
                'subject'              => $this->Name,
                'subjectPublicKeyInfo' => $SubjectPublicKeyInfo,
                // implicit means that the T in the TLV structure is to be rewritten, regardless of the type
                'issuerUniqueID'       => array(
                                               'constant' => 1,
                                               'optional' => true,
                                               'implicit' => true
                                           ) + $UniqueIdentifier,
                'subjectUniqueID'       => array(
                                               'constant' => 2,
                                               'optional' => true,
                                               'implicit' => true
                                           ) + $UniqueIdentifier,
                // <http://tools.ietf.org/html/rfc2459#page-74> doesn't use the EXPLICIT keyword but if
                // it's not IMPLICIT, it's EXPLICIT
                'extensions'            => array(
                                               'constant' => 3,
                                               'optional' => true,
                                               'explicit' => true
                                           ) + $this->Extensions
            )
        );

        $this->Certificate = array(
            'type'     => ASN1::TYPE_SEQUENCE,
            'children' => array(
                 'tbsCertificate'     => $TBSCertificate,
                 'signatureAlgorithm' => $AlgorithmIdentifier,
                 'signature'          => array('type' => ASN1::TYPE_BIT_STRING)
            )
        );

        $this->KeyUsage = array(
            'type'    => ASN1::TYPE_BIT_STRING,
            'mapping' => array(
                'digitalSignature',
                'nonRepudiation',
                'keyEncipherment',
                'dataEncipherment',
                'keyAgreement',
                'keyCertSign',
                'cRLSign',
                'encipherOnly',
                'decipherOnly'
            )
        );

        $this->BasicConstraints = array(
            'type'     => ASN1::TYPE_SEQUENCE,
            'children' => array(
                'cA'                => array(
                                                 'type'     => ASN1::TYPE_BOOLEAN,
                                                 'optional' => true,
                                                 'default'  => false
                                       ),
                'pathLenConstraint' => array(
                                                 'type' => ASN1::TYPE_INTEGER,
                                                 'optional' => true
                                       )
            )
        );

        $this->KeyIdentifier = array('type' => ASN1::TYPE_OCTET_STRING);

        $OrganizationalUnitNames = array(
            'type'     => ASN1::TYPE_SEQUENCE,
            'min'      => 1,
            'max'      => 4, // ub-organizational-units
            'children' => array('type' => ASN1::TYPE_PRINTABLE_STRING)
        );

        $PersonalName = array(
            'type'     => ASN1::TYPE_SET,
            'children' => array(
                'surname'              => array(
                                           'type' => ASN1::TYPE_PRINTABLE_STRING,
                                           'constant' => 0,
                                           'optional' => true,
                                           'implicit' => true
                                         ),
                'given-name'           => array(
                                           'type' => ASN1::TYPE_PRINTABLE_STRING,
                                           'constant' => 1,
                                           'optional' => true,
                                           'implicit' => true
                                         ),
                'initials'             => array(
                                           'type' => ASN1::TYPE_PRINTABLE_STRING,
                                           'constant' => 2,
                                           'optional' => true,
                                           'implicit' => true
                                         ),
                'generation-qualifier' => array(
                                           'type' => ASN1::TYPE_PRINTABLE_STRING,
                                           'constant' => 3,
                                           'optional' => true,
                                           'implicit' => true
                                         )
            )
        );

        $NumericUserIdentifier = array('type' => ASN1::TYPE_NUMERIC_STRING);

        $OrganizationName = array('type' => ASN1::TYPE_PRINTABLE_STRING);

        $PrivateDomainName = array(
            'type'     => ASN1::TYPE_CHOICE,
            'children' => array(
                'numeric'   => array('type' => ASN1::TYPE_NUMERIC_STRING),
                'printable' => array('type' => ASN1::TYPE_PRINTABLE_STRING)
            )
        );

        $TerminalIdentifier = array('type' => ASN1::TYPE_PRINTABLE_STRING);

        $NetworkAddress = array('type' => ASN1::TYPE_NUMERIC_STRING);

        $AdministrationDomainName = array(
            'type'     => ASN1::TYPE_CHOICE,
            // if class isn't present it's assumed to be \phpseclib\File\ASN1::CLASS_UNIVERSAL or
            // (if constant is present) \phpseclib\File\ASN1::CLASS_CONTEXT_SPECIFIC
            'class'    => ASN1::CLASS_APPLICATION,
            'cast'     => 2,
            'children' => array(
                'numeric'   => array('type' => ASN1::TYPE_NUMERIC_STRING),
                'printable' => array('type' => ASN1::TYPE_PRINTABLE_STRING)
            )
        );

        $CountryName = array(
            'type'     => ASN1::TYPE_CHOICE,
            // if class isn't present it's assumed to be \phpseclib\File\ASN1::CLASS_UNIVERSAL or
            // (if constant is present) \phpseclib\File\ASN1::CLASS_CONTEXT_SPECIFIC
            'class'    => ASN1::CLASS_APPLICATION,
            'cast'     => 1,
            'children' => array(
                'x121-dcc-code'        => array('type' => ASN1::TYPE_NUMERIC_STRING),
                'iso-3166-alpha2-code' => array('type' => ASN1::TYPE_PRINTABLE_STRING)
            )
        );

        $AnotherName = array(
            'type'     => ASN1::TYPE_SEQUENCE,
            'children' => array(
                 'type-id' => array('type' => ASN1::TYPE_OBJECT_IDENTIFIER),
                 'value'   => array(
                                  'type' => ASN1::TYPE_ANY,
                                  'constant' => 0,
                                  'optional' => true,
                                  'explicit' => true
                              )
            )
        );

        $ExtensionAttribute = array(
            'type'     => ASN1::TYPE_SEQUENCE,
            'children' => array(
                 'extension-attribute-type'  => array(
                                                    'type' => ASN1::TYPE_PRINTABLE_STRING,
                                                    'constant' => 0,
                                                    'optional' => true,
                                                    'implicit' => true
                                                ),
                 'extension-attribute-value' => array(
                                                    'type' => ASN1::TYPE_ANY,
                                                    'constant' => 1,
                                                    'optional' => true,
                                                    'explicit' => true
                                                )
            )
        );

        $ExtensionAttributes = array(
            'type'     => ASN1::TYPE_SET,
            'min'      => 1,
            'max'      => 256, // ub-extension-attributes
            'children' => $ExtensionAttribute
        );

        $BuiltInDomainDefinedAttribute = array(
            'type'     => ASN1::TYPE_SEQUENCE,
            'children' => array(
                 'type'  => array('type' => ASN1::TYPE_PRINTABLE_STRING),
                 'value' => array('type' => ASN1::TYPE_PRINTABLE_STRING)
            )
        );

        $BuiltInDomainDefinedAttributes = array(
            'type'     => ASN1::TYPE_SEQUENCE,
            'min'      => 1,
            'max'      => 4, // ub-domain-defined-attributes
            'children' => $BuiltInDomainDefinedAttribute
        );

        $BuiltInStandardAttributes =  array(
            'type'     => ASN1::TYPE_SEQUENCE,
            'children' => array(
                'country-name'               => array('optional' => true) + $CountryName,
                'administration-domain-name' => array('optional' => true) + $AdministrationDomainName,
                'network-address'            => array(
                                                 'constant' => 0,
                                                 'optional' => true,
                                                 'implicit' => true
                                               ) + $NetworkAddress,
                'terminal-identifier'        => array(
                                                 'constant' => 1,
                                                 'optional' => true,
                                                 'implicit' => true
                                               ) + $TerminalIdentifier,
                'private-domain-name'        => array(
                                                 'constant' => 2,
                                                 'optional' => true,
                                                 'explicit' => true
                                               ) + $PrivateDomainName,
                'organization-name'          => array(
                                                 'constant' => 3,
                                                 'optional' => true,
                                                 'implicit' => true
                                               ) + $OrganizationName,
                'numeric-user-identifier'    => array(
                                                 'constant' => 4,
                                                 'optional' => true,
                                                 'implicit' => true
                                               ) + $NumericUserIdentifier,
                'personal-name'              => array(
                                                 'constant' => 5,
                                                 'optional' => true,
                                                 'implicit' => true
                                               ) + $PersonalName,
                'organizational-unit-names'  => array(
                                                 'constant' => 6,
                                                 'optional' => true,
                                                 'implicit' => true
                                               ) + $OrganizationalUnitNames
            )
        );

        $ORAddress = array(
            'type'     => ASN1::TYPE_SEQUENCE,
            'children' => array(
                 'built-in-standard-attributes'       => $BuiltInStandardAttributes,
                 'built-in-domain-defined-attributes' => array('optional' => true) + $BuiltInDomainDefinedAttributes,
                 'extension-attributes'               => array('optional' => true) + $ExtensionAttributes
            )
        );

        $EDIPartyName = array(
            'type'     => ASN1::TYPE_SEQUENCE,
            'children' => array(
                 'nameAssigner' => array(
                                    'constant' => 0,
                                    'optional' => true,
                                    'implicit' => true
                                ) + $this->DirectoryString,
                 // partyName is technically required but \phpseclib\File\ASN1 doesn't currently support non-optional constants and
                 // setting it to optional gets the job done in any event.
                 'partyName'    => array(
                                    'constant' => 1,
                                    'optional' => true,
                                    'implicit' => true
                                ) + $this->DirectoryString
            )
        );

        $GeneralName = array(
            'type'     => ASN1::TYPE_CHOICE,
            'children' => array(
                'otherName'                 => array(
                                                 'constant' => 0,
                                                 'optional' => true,
                                                 'implicit' => true
                                               ) + $AnotherName,
                'rfc822Name'                => array(
                                                 'type' => ASN1::TYPE_IA5_STRING,
                                                 'constant' => 1,
                                                 'optional' => true,
                                                 'implicit' => true
                                               ),
                'dNSName'                   => array(
                                                 'type' => ASN1::TYPE_IA5_STRING,
                                                 'constant' => 2,
                                                 'optional' => true,
                                                 'implicit' => true
                                               ),
                'x400Address'               => array(
                                                 'constant' => 3,
                                                 'optional' => true,
                                                 'implicit' => true
                                               ) + $ORAddress,
                'directoryName'             => array(
                                                 'constant' => 4,
                                                 'optional' => true,
                                                 'explicit' => true
                                               ) + $this->Name,
                'ediPartyName'              => array(
                                                 'constant' => 5,
                                                 'optional' => true,
                                                 'implicit' => true
                                               ) + $EDIPartyName,
                'uniformResourceIdentifier' => array(
                                                 'type' => ASN1::TYPE_IA5_STRING,
                                                 'constant' => 6,
                                                 'optional' => true,
                                                 'implicit' => true
                                               ),
                'iPAddress'                 => array(
                                                 'type' => ASN1::TYPE_OCTET_STRING,
                                                 'constant' => 7,
                                                 'optional' => true,
                                                 'implicit' => true
                                               ),
                'registeredID'              => array(
                                                 'type' => ASN1::TYPE_OBJECT_IDENTIFIER,
                                                 'constant' => 8,
                                                 'optional' => true,
                                                 'implicit' => true
                                               )
            )
        );

        $GeneralNames = array(
            'type'     => ASN1::TYPE_SEQUENCE,
            'min'      => 1,
            'max'      => -1,
            'children' => $GeneralName
        );

        $this->IssuerAltName = $GeneralNames;

        $ReasonFlags = array(
            'type'    => ASN1::TYPE_BIT_STRING,
            'mapping' => array(
                'unused',
                'keyCompromise',
                'cACompromise',
                'affiliationChanged',
                'superseded',
                'cessationOfOperation',
                'certificateHold',
                'privilegeWithdrawn',
                'aACompromise'
            )
        );

        $DistributionPointName = array(
            'type'     => ASN1::TYPE_CHOICE,
            'children' => array(
                'fullName'                => array(
                                                 'constant' => 0,
                                                 'optional' => true,
                                                 'implicit' => true
                                       ) + $GeneralNames,
                'nameRelativeToCRLIssuer' => array(
                                                 'constant' => 1,
                                                 'optional' => true,
                                                 'implicit' => true
                                       ) + $this->RelativeDistinguishedName
            )
        );

        $DistributionPoint = array(
            'type'     => ASN1::TYPE_SEQUENCE,
            'children' => array(
                'distributionPoint' => array(
                                                 'constant' => 0,
                                                 'optional' => true,
                                                 'explicit' => true
                                       ) + $DistributionPointName,
                'reasons'           => array(
                                                 'constant' => 1,
                                                 'optional' => true,
                                                 'implicit' => true
                                       ) + $ReasonFlags,
                'cRLIssuer'         => array(
                                                 'constant' => 2,
                                                 'optional' => true,
                                                 'implicit' => true
                                       ) + $GeneralNames
            )
        );

        $this->CRLDistributionPoints = array(
            'type'     => ASN1::TYPE_SEQUENCE,
            'min'      => 1,
            'max'      => -1,
            'children' => $DistributionPoint
        );

        $this->AuthorityKeyIdentifier = array(
            'type'     => ASN1::TYPE_SEQUENCE,
            'children' => array(
                'keyIdentifier'             => array(
                                                 'constant' => 0,
                                                 'optional' => true,
                                                 'implicit' => true
                                               ) + $this->KeyIdentifier,
                'authorityCertIssuer'       => array(
                                                 'constant' => 1,
                                                 'optional' => true,
                                                 'implicit' => true
                                               ) + $GeneralNames,
                'authorityCertSerialNumber' => array(
                                                 'constant' => 2,
                                                 'optional' => true,
                                                 'implicit' => true
                                               ) + $CertificateSerialNumber
            )
        );

        $PolicyQualifierId = array('type' => ASN1::TYPE_OBJECT_IDENTIFIER);

        $PolicyQualifierInfo = array(
            'type'     => ASN1::TYPE_SEQUENCE,
            'children' => array(
                'policyQualifierId' => $PolicyQualifierId,
                'qualifier'         => array('type' => ASN1::TYPE_ANY)
            )
        );

        $CertPolicyId = array('type' => ASN1::TYPE_OBJECT_IDENTIFIER);

        $PolicyInformation = array(
            'type'     => ASN1::TYPE_SEQUENCE,
            'children' => array(
                'policyIdentifier' => $CertPolicyId,
                'policyQualifiers' => array(
                                          'type'     => ASN1::TYPE_SEQUENCE,
                                          'min'      => 0,
                                          'max'      => -1,
                                          'optional' => true,
                                          'children' => $PolicyQualifierInfo
                                      )
            )
        );

        $this->CertificatePolicies = array(
            'type'     => ASN1::TYPE_SEQUENCE,
            'min'      => 1,
            'max'      => -1,
            'children' => $PolicyInformation
        );

        $this->PolicyMappings = array(
            'type'     => ASN1::TYPE_SEQUENCE,
            'min'      => 1,
            'max'      => -1,
            'children' => array(
                              'type'     => ASN1::TYPE_SEQUENCE,
                              'children' => array(
                                  'issuerDomainPolicy' => $CertPolicyId,
                                  'subjectDomainPolicy' => $CertPolicyId
                              )
                       )
        );

        $KeyPurposeId = array('type' => ASN1::TYPE_OBJECT_IDENTIFIER);

        $this->ExtKeyUsageSyntax = array(
            'type'     => ASN1::TYPE_SEQUENCE,
            'min'      => 1,
            'max'      => -1,
            'children' => $KeyPurposeId
        );

        $AccessDescription = array(
            'type'     => ASN1::TYPE_SEQUENCE,
            'children' => array(
                'accessMethod'   => array('type' => ASN1::TYPE_OBJECT_IDENTIFIER),
                'accessLocation' => $GeneralName
            )
        );

        $this->AuthorityInfoAccessSyntax = array(
            'type'     => ASN1::TYPE_SEQUENCE,
            'min'      => 1,
            'max'      => -1,
            'children' => $AccessDescription
        );

        $this->SubjectInfoAccessSyntax = array(
            'type'     => ASN1::TYPE_SEQUENCE,
            'min'      => 1,
            'max'      => -1,
            'children' => $AccessDescription
        );

        $this->SubjectAltName = $GeneralNames;

        $this->PrivateKeyUsagePeriod = array(
            'type'     => ASN1::TYPE_SEQUENCE,
            'children' => array(
                'notBefore' => array(
                                                 'constant' => 0,
                                                 'optional' => true,
                                                 'implicit' => true,
                                                 'type' => ASN1::TYPE_GENERALIZED_TIME),
                'notAfter'  => array(
                                                 'constant' => 1,
                                                 'optional' => true,
                                                 'implicit' => true,
                                                 'type' => ASN1::TYPE_GENERALIZED_TIME)
            )
        );

        $BaseDistance = array('type' => ASN1::TYPE_INTEGER);

        $GeneralSubtree = array(
            'type'     => ASN1::TYPE_SEQUENCE,
            'children' => array(
                'base'    => $GeneralName,
                'minimum' => array(
                                 'constant' => 0,
                                 'optional' => true,
                                 'implicit' => true,
                                 'default' => new BigInteger(0)
                             ) + $BaseDistance,
                'maximum' => array(
                                 'constant' => 1,
                                 'optional' => true,
                                 'implicit' => true,
                             ) + $BaseDistance
            )
        );

        $GeneralSubtrees = array(
            'type'     => ASN1::TYPE_SEQUENCE,
            'min'      => 1,
            'max'      => -1,
            'children' => $GeneralSubtree
        );

        $this->NameConstraints = array(
            'type'     => ASN1::TYPE_SEQUENCE,
            'children' => array(
                'permittedSubtrees' => array(
                                           'constant' => 0,
                                           'optional' => true,
                                           'implicit' => true
                                       ) + $GeneralSubtrees,
                'excludedSubtrees'  => array(
                                           'constant' => 1,
                                           'optional' => true,
                                           'implicit' => true
                                       ) + $GeneralSubtrees
            )
        );

        $this->CPSuri = array('type' => ASN1::TYPE_IA5_STRING);

        $DisplayText = array(
            'type'     => ASN1::TYPE_CHOICE,
            'children' => array(
                'ia5String'     => array('type' => ASN1::TYPE_IA5_STRING),
                'visibleString' => array('type' => ASN1::TYPE_VISIBLE_STRING),
                'bmpString'     => array('type' => ASN1::TYPE_BMP_STRING),
                'utf8String'    => array('type' => ASN1::TYPE_UTF8_STRING)
            )
        );

        $NoticeReference = array(
            'type'     => ASN1::TYPE_SEQUENCE,
            'children' => array(
                'organization'  => $DisplayText,
                'noticeNumbers' => array(
                                       'type'     => ASN1::TYPE_SEQUENCE,
                                       'min'      => 1,
                                       'max'      => 200,
                                       'children' => array('type' => ASN1::TYPE_INTEGER)
                                   )
            )
        );

        $this->UserNotice = array(
            'type'     => ASN1::TYPE_SEQUENCE,
            'children' => array(
                'noticeRef' => array(
                                           'optional' => true,
                                           'implicit' => true
                                       ) + $NoticeReference,
                'explicitText'  => array(
                                           'optional' => true,
                                           'implicit' => true
                                       ) + $DisplayText
            )
        );

        // mapping is from <http://www.mozilla.org/projects/security/pki/nss/tech-notes/tn3.html>
        $this->netscape_cert_type = array(
            'type'    => ASN1::TYPE_BIT_STRING,
            'mapping' => array(
                'SSLClient',
                'SSLServer',
                'Email',
                'ObjectSigning',
                'Reserved',
                'SSLCA',
                'EmailCA',
                'ObjectSigningCA'
            )
        );

        $this->netscape_comment = array('type' => ASN1::TYPE_IA5_STRING);
        $this->netscape_ca_policy_url = array('type' => ASN1::TYPE_IA5_STRING);

        // attribute is used in RFC2986 but we're using the RFC5280 definition

        $Attribute = array(
            'type'     => ASN1::TYPE_SEQUENCE,
            'children' => array(
                'type' => $AttributeType,
                'value'=> array(
                              'type'     => ASN1::TYPE_SET,
                              'min'      => 1,
                              'max'      => -1,
                              'children' => $this->AttributeValue
                          )
            )
        );

        $this->SubjectDirectoryAttributes = array(
            'type'     => ASN1::TYPE_SEQUENCE,
            'min'      => 1,
            'max'      => -1,
            'children' => $Attribute
        );

        // adapted from <http://tools.ietf.org/html/rfc2986>

        $Attributes = array(
            'type'     => ASN1::TYPE_SET,
            'min'      => 1,
            'max'      => -1,
            'children' => $Attribute
        );

        $CertificationRequestInfo = array(
            'type'     => ASN1::TYPE_SEQUENCE,
            'children' => array(
                'version'       => array(
                                       'type' => ASN1::TYPE_INTEGER,
                                       'mapping' => array('v1')
                                   ),
                'subject'       => $this->Name,
                'subjectPKInfo' => $SubjectPublicKeyInfo,
                'attributes'    => array(
                                       'constant' => 0,
                                       'optional' => true,
                                       'implicit' => true
                                   ) + $Attributes,
            )
        );

        $this->CertificationRequest = array(
            'type'     => ASN1::TYPE_SEQUENCE,
            'children' => array(
                'certificationRequestInfo' => $CertificationRequestInfo,
                'signatureAlgorithm'       => $AlgorithmIdentifier,
                'signature'                => array('type' => ASN1::TYPE_BIT_STRING)
            )
        );

        $RevokedCertificate = array(
            'type'     => ASN1::TYPE_SEQUENCE,
            'children' => array(
                              'userCertificate'    => $CertificateSerialNumber,
                              'revocationDate'     => $Time,
                              'crlEntryExtensions' => array(
                                                          'optional' => true
                                                      ) + $this->Extensions
                          )
        );

        $TBSCertList = array(
            'type'     => ASN1::TYPE_SEQUENCE,
            'children' => array(
                'version'             => array(
                                             'optional' => true,
                                             'default'  => 'v1'
                                         ) + $Version,
                'signature'           => $AlgorithmIdentifier,
                'issuer'              => $this->Name,
                'thisUpdate'          => $Time,
                'nextUpdate'          => array(
                                             'optional' => true
                                         ) + $Time,
                'revokedCertificates' => array(
                                             'type'     => ASN1::TYPE_SEQUENCE,
                                             'optional' => true,
                                             'min'      => 0,
                                             'max'      => -1,
                                             'children' => $RevokedCertificate
                                         ),
                'crlExtensions'       => array(
                                             'constant' => 0,
                                             'optional' => true,
                                             'explicit' => true
                                         ) + $this->Extensions
            )
        );

        $this->CertificateList = array(
            'type'     => ASN1::TYPE_SEQUENCE,
            'children' => array(
                'tbsCertList'        => $TBSCertList,
                'signatureAlgorithm' => $AlgorithmIdentifier,
                'signature'          => array('type' => ASN1::TYPE_BIT_STRING)
            )
        );

        $this->CRLNumber = array('type' => ASN1::TYPE_INTEGER);

        $this->CRLReason = array('type' => ASN1::TYPE_ENUMERATED,
           'mapping' => array(
                            'unspecified',
                            'keyCompromise',
                            'cACompromise',
                            'affiliationChanged',
                            'superseded',
                            'cessationOfOperation',
                            'certificateHold',
                            // Value 7 is not used.
                            8 => 'removeFromCRL',
                            'privilegeWithdrawn',
                            'aACompromise'
            )
        );

        $this->IssuingDistributionPoint = array('type' => ASN1::TYPE_SEQUENCE,
            'children' => array(
                'distributionPoint'          => array(
                                                    'constant' => 0,
                                                    'optional' => true,
                                                    'explicit' => true
                                                ) + $DistributionPointName,
                'onlyContainsUserCerts'      => array(
                                                    'type'     => ASN1::TYPE_BOOLEAN,
                                                    'constant' => 1,
                                                    'optional' => true,
                                                    'default'  => false,
                                                    'implicit' => true
                                                ),
                'onlyContainsCACerts'        => array(
                                                    'type'     => ASN1::TYPE_BOOLEAN,
                                                    'constant' => 2,
                                                    'optional' => true,
                                                    'default'  => false,
                                                    'implicit' => true
                                                ),
                'onlySomeReasons'           => array(
                                                    'constant' => 3,
                                                    'optional' => true,
                                                    'implicit' => true
                                                ) + $ReasonFlags,
                'indirectCRL'               => array(
                                                    'type'     => ASN1::TYPE_BOOLEAN,
                                                    'constant' => 4,
                                                    'optional' => true,
                                                    'default'  => false,
                                                    'implicit' => true
                                                ),
                'onlyContainsAttributeCerts' => array(
                                                    'type'     => ASN1::TYPE_BOOLEAN,
                                                    'constant' => 5,
                                                    'optional' => true,
                                                    'default'  => false,
                                                    'implicit' => true
                                                )
                          )
        );

        $this->InvalidityDate = array('type' => ASN1::TYPE_GENERALIZED_TIME);

        $this->CertificateIssuer = $GeneralNames;

        $this->HoldInstructionCode = array('type' => ASN1::TYPE_OBJECT_IDENTIFIER);

        $PublicKeyAndChallenge = array(
            'type'     => ASN1::TYPE_SEQUENCE,
            'children' => array(
                'spki'      => $SubjectPublicKeyInfo,
                'challenge' => array('type' => ASN1::TYPE_IA5_STRING)
            )
        );

        $this->SignedPublicKeyAndChallenge = array(
            'type'     => ASN1::TYPE_SEQUENCE,
            'children' => array(
                'publicKeyAndChallenge' => $PublicKeyAndChallenge,
                'signatureAlgorithm'    => $AlgorithmIdentifier,
                'signature'             => array('type' => ASN1::TYPE_BIT_STRING)
            )
        );

        $this->PostalAddress = array(
            'type'     => ASN1::TYPE_SEQUENCE,
            'optional' => true,
            'min'      => 1,
            'max'      => -1,
            'children' => $this->DirectoryString
        );

        // OIDs from RFC5280 and those RFCs mentioned in RFC5280#section-4.1.1.2
        $this->oids = array(
            '1.3.6.1.5.5.7' => 'id-pkix',
            '1.3.6.1.5.5.7.1' => 'id-pe',
            '1.3.6.1.5.5.7.2' => 'id-qt',
            '1.3.6.1.5.5.7.3' => 'id-kp',
            '1.3.6.1.5.5.7.48' => 'id-ad',
            '1.3.6.1.5.5.7.2.1' => 'id-qt-cps',
            '1.3.6.1.5.5.7.2.2' => 'id-qt-unotice',
            '1.3.6.1.5.5.7.48.1' =>'id-ad-ocsp',
            '1.3.6.1.5.5.7.48.2' => 'id-ad-caIssuers',
            '1.3.6.1.5.5.7.48.3' => 'id-ad-timeStamping',
            '1.3.6.1.5.5.7.48.5' => 'id-ad-caRepository',
            '2.5.4' => 'id-at',
            '2.5.4.41' => 'id-at-name',
            '2.5.4.4' => 'id-at-surname',
            '2.5.4.42' => 'id-at-givenName',
            '2.5.4.43' => 'id-at-initials',
            '2.5.4.44' => 'id-at-generationQualifier',
            '2.5.4.3' => 'id-at-commonName',
            '2.5.4.7' => 'id-at-localityName',
            '2.5.4.8' => 'id-at-stateOrProvinceName',
            '2.5.4.10' => 'id-at-organizationName',
            '2.5.4.11' => 'id-at-organizationalUnitName',
            '2.5.4.12' => 'id-at-title',
            '2.5.4.13' => 'id-at-description',
            '2.5.4.46' => 'id-at-dnQualifier',
            '2.5.4.6' => 'id-at-countryName',
            '2.5.4.5' => 'id-at-serialNumber',
            '2.5.4.65' => 'id-at-pseudonym',
            '2.5.4.17' => 'id-at-postalCode',
            '2.5.4.9' => 'id-at-streetAddress',
            '2.5.4.45' => 'id-at-uniqueIdentifier',
            '2.5.4.72' => 'id-at-role',
            '2.5.4.16' => 'id-at-postalAddress',
            '1.3.6.1.4.1.311.60.2.1.3' => 'jurisdictionOfIncorporationCountryName',
            '1.3.6.1.4.1.311.60.2.1.2' => 'jurisdictionOfIncorporationStateOrProvinceName',
            '1.3.6.1.4.1.311.60.2.1.1' => 'jurisdictionLocalityName',
            '2.5.4.15' => 'id-at-businessCategory',

            '0.9.2342.19200300.100.1.25' => 'id-domainComponent',
            '1.2.840.113549.1.9' => 'pkcs-9',
            '1.2.840.113549.1.9.1' => 'pkcs-9-at-emailAddress',
            '2.5.29' => 'id-ce',
            '2.5.29.35' => 'id-ce-authorityKeyIdentifier',
            '2.5.29.14' => 'id-ce-subjectKeyIdentifier',
            '2.5.29.15' => 'id-ce-keyUsage',
            '2.5.29.16' => 'id-ce-privateKeyUsagePeriod',
            '2.5.29.32' => 'id-ce-certificatePolicies',
            '2.5.29.32.0' => 'anyPolicy',

            '2.5.29.33' => 'id-ce-policyMappings',
            '2.5.29.17' => 'id-ce-subjectAltName',
            '2.5.29.18' => 'id-ce-issuerAltName',
            '2.5.29.9' => 'id-ce-subjectDirectoryAttributes',
            '2.5.29.19' => 'id-ce-basicConstraints',
            '2.5.29.30' => 'id-ce-nameConstraints',
            '2.5.29.36' => 'id-ce-policyConstraints',
            '2.5.29.31' => 'id-ce-cRLDistributionPoints',
            '2.5.29.37' => 'id-ce-extKeyUsage',
            '2.5.29.37.0' => 'anyExtendedKeyUsage',
            '1.3.6.1.5.5.7.3.1' => 'id-kp-serverAuth',
            '1.3.6.1.5.5.7.3.2' => 'id-kp-clientAuth',
            '1.3.6.1.5.5.7.3.3' => 'id-kp-codeSigning',
            '1.3.6.1.5.5.7.3.4' => 'id-kp-emailProtection',
            '1.3.6.1.5.5.7.3.8' => 'id-kp-timeStamping',
            '1.3.6.1.5.5.7.3.9' => 'id-kp-OCSPSigning',
            '2.5.29.54' => 'id-ce-inhibitAnyPolicy',
            '2.5.29.46' => 'id-ce-freshestCRL',
            '1.3.6.1.5.5.7.1.1' => 'id-pe-authorityInfoAccess',
            '1.3.6.1.5.5.7.1.11' => 'id-pe-subjectInfoAccess',
            '2.5.29.20' => 'id-ce-cRLNumber',
            '2.5.29.28' => 'id-ce-issuingDistributionPoint',
            '2.5.29.27' => 'id-ce-deltaCRLIndicator',
            '2.5.29.21' => 'id-ce-cRLReasons',
            '2.5.29.29' => 'id-ce-certificateIssuer',
            '2.5.29.23' => 'id-ce-holdInstructionCode',
            '1.2.840.10040.2' => 'holdInstruction',
            '1.2.840.10040.2.1' => 'id-holdinstruction-none',
            '1.2.840.10040.2.2' => 'id-holdinstruction-callissuer',
            '1.2.840.10040.2.3' => 'id-holdinstruction-reject',
            '2.5.29.24' => 'id-ce-invalidityDate',

            '1.2.840.113549.2.2' => 'md2',
            '1.2.840.113549.2.5' => 'md5',
            '1.3.14.3.2.26' => 'id-sha1',
            '1.2.840.10040.4.1' => 'id-dsa',
            '1.2.840.10040.4.3' => 'id-dsa-with-sha1',
            '1.2.840.113549.1.1' => 'pkcs-1',
            '1.2.840.113549.1.1.1' => 'rsaEncryption',
            '1.2.840.113549.1.1.2' => 'md2WithRSAEncryption',
            '1.2.840.113549.1.1.4' => 'md5WithRSAEncryption',
            '1.2.840.113549.1.1.5' => 'sha1WithRSAEncryption',
            '1.2.840.10046.2.1' => 'dhpublicnumber',
            '2.16.840.1.101.2.1.1.22' => 'id-keyExchangeAlgorithm',
            '1.2.840.10045' => 'ansi-X9-62',
            '1.2.840.10045.4' => 'id-ecSigType',
            '1.2.840.10045.4.1' => 'ecdsa-with-SHA1',
            '1.2.840.10045.1' => 'id-fieldType',
            '1.2.840.10045.1.1' => 'prime-field',
            '1.2.840.10045.1.2' => 'characteristic-two-field',
            '1.2.840.10045.1.2.3' => 'id-characteristic-two-basis',
            '1.2.840.10045.1.2.3.1' => 'gnBasis',
            '1.2.840.10045.1.2.3.2' => 'tpBasis',
            '1.2.840.10045.1.2.3.3' => 'ppBasis',
            '1.2.840.10045.2' => 'id-publicKeyType',
            '1.2.840.10045.2.1' => 'id-ecPublicKey',
            '1.2.840.10045.3' => 'ellipticCurve',
            '1.2.840.10045.3.0' => 'c-TwoCurve',
            '1.2.840.10045.3.0.1' => 'c2pnb163v1',
            '1.2.840.10045.3.0.2' => 'c2pnb163v2',
            '1.2.840.10045.3.0.3' => 'c2pnb163v3',
            '1.2.840.10045.3.0.4' => 'c2pnb176w1',
            '1.2.840.10045.3.0.5' => 'c2pnb191v1',
            '1.2.840.10045.3.0.6' => 'c2pnb191v2',
            '1.2.840.10045.3.0.7' => 'c2pnb191v3',
            '1.2.840.10045.3.0.8' => 'c2pnb191v4',
            '1.2.840.10045.3.0.9' => 'c2pnb191v5',
            '1.2.840.10045.3.0.10' => 'c2pnb208w1',
            '1.2.840.10045.3.0.11' => 'c2pnb239v1',
            '1.2.840.10045.3.0.12' => 'c2pnb239v2',
            '1.2.840.10045.3.0.13' => 'c2pnb239v3',
            '1.2.840.10045.3.0.14' => 'c2pnb239v4',
            '1.2.840.10045.3.0.15' => 'c2pnb239v5',
            '1.2.840.10045.3.0.16' => 'c2pnb272w1',
            '1.2.840.10045.3.0.17' => 'c2pnb304w1',
            '1.2.840.10045.3.0.18' => 'c2pnb359v1',
            '1.2.840.10045.3.0.19' => 'c2pnb368w1',
            '1.2.840.10045.3.0.20' => 'c2pnb431r1',
            '1.2.840.10045.3.1' => 'primeCurve',
            '1.2.840.10045.3.1.1' => 'prime192v1',
            '1.2.840.10045.3.1.2' => 'prime192v2',
            '1.2.840.10045.3.1.3' => 'prime192v3',
            '1.2.840.10045.3.1.4' => 'prime239v1',
            '1.2.840.10045.3.1.5' => 'prime239v2',
            '1.2.840.10045.3.1.6' => 'prime239v3',
            '1.2.840.10045.3.1.7' => 'prime256v1',
            '1.2.840.113549.1.1.7' => 'id-RSAES-OAEP',
            '1.2.840.113549.1.1.9' => 'id-pSpecified',
            '1.2.840.113549.1.1.10' => 'id-RSASSA-PSS',
            '1.2.840.113549.1.1.8' => 'id-mgf1',
            '1.2.840.113549.1.1.14' => 'sha224WithRSAEncryption',
            '1.2.840.113549.1.1.11' => 'sha256WithRSAEncryption',
            '1.2.840.113549.1.1.12' => 'sha384WithRSAEncryption',
            '1.2.840.113549.1.1.13' => 'sha512WithRSAEncryption',
            '2.16.840.1.101.3.4.2.4' => 'id-sha224',
            '2.16.840.1.101.3.4.2.1' => 'id-sha256',
            '2.16.840.1.101.3.4.2.2' => 'id-sha384',
            '2.16.840.1.101.3.4.2.3' => 'id-sha512',
            '1.2.643.2.2.4' => 'id-GostR3411-94-with-GostR3410-94',
            '1.2.643.2.2.3' => 'id-GostR3411-94-with-GostR3410-2001',
            '1.2.643.2.2.20' => 'id-GostR3410-2001',
            '1.2.643.2.2.19' => 'id-GostR3410-94',
            // Netscape Object Identifiers from "Netscape Certificate Extensions"
            '2.16.840.1.113730' => 'netscape',
            '2.16.840.1.113730.1' => 'netscape-cert-extension',
            '2.16.840.1.113730.1.1' => 'netscape-cert-type',
            '2.16.840.1.113730.1.13' => 'netscape-comment',
            '2.16.840.1.113730.1.8' => 'netscape-ca-policy-url',
            // the following are X.509 extensions not supported by phpseclib
            '1.3.6.1.5.5.7.1.12' => 'id-pe-logotype',
            '1.2.840.113533.7.65.0' => 'entrustVersInfo',
            '2.16.840.1.113733.1.6.9' => 'verisignPrivate',
            // for Certificate Signing Requests
            // see http://tools.ietf.org/html/rfc2985
            '1.2.840.113549.1.9.2' => 'pkcs-9-at-unstructuredName', // PKCS #9 unstructured name
            '1.2.840.113549.1.9.7' => 'pkcs-9-at-challengePassword', // Challenge password for certificate revocations
            '1.2.840.113549.1.9.14' => 'pkcs-9-at-extensionRequest' // Certificate extension request
        );
    }

    /**
     * Load X.509 certificate
     *
     * Returns an associative array describing the X.509 cert or a false if the cert failed to load
     *
     * @param string $cert
     * @param int $mode
     * @access public
     * @return mixed
     */
    function loadX509($cert, $mode = self::FORMAT_AUTO_DETECT)
    {
        if (is_array($cert) && isset($cert['tbsCertificate'])) {
            unset($this->currentCert);
            unset($this->currentKeyIdentifier);
            $this->dn = $cert['tbsCertificate']['subject'];
            if (!isset($this->dn)) {
                return false;
            }
            $this->currentCert = $cert;

            $currentKeyIdentifier = $this->getExtension('id-ce-subjectKeyIdentifier');
            $this->currentKeyIdentifier = is_string($currentKeyIdentifier) ? $currentKeyIdentifier : null;

            unset($this->signatureSubject);

            return $cert;
        }

        $asn1 = new ASN1();

        if ($mode != self::FORMAT_DER) {
            $newcert = $this->_extractBER($cert);
            if ($mode == self::FORMAT_PEM && $cert == $newcert) {
                return false;
            }
            $cert = $newcert;
        }

        if ($cert === false) {
            $this->currentCert = false;
            return false;
        }

        $asn1->loadOIDs($this->oids);
        $decoded = $asn1->decodeBER($cert);

        if (!empty($decoded)) {
            $x509 = $asn1->asn1map($decoded[0], $this->Certificate);
        }
        if (!isset($x509) || $x509 === false) {
            $this->currentCert = false;
            return false;
        }

        $this->signatureSubject = substr($cert, $decoded[0]['content'][0]['start'], $decoded[0]['content'][0]['length']);

        if ($this->_isSubArrayValid($x509, 'tbsCertificate/extensions')) {
            $this->_mapInExtensions($x509, 'tbsCertificate/extensions', $asn1);
        }
        $this->_mapInDNs($x509, 'tbsCertificate/issuer/rdnSequence', $asn1);
        $this->_mapInDNs($x509, 'tbsCertificate/subject/rdnSequence', $asn1);

        $key = &$x509['tbsCertificate']['subjectPublicKeyInfo']['subjectPublicKey'];
        $key = $this->_reformatKey($x509['tbsCertificate']['subjectPublicKeyInfo']['algorithm']['algorithm'], $key);

        $this->currentCert = $x509;
        $this->dn = $x509['tbsCertificate']['subject'];

        $currentKeyIdentifier = $this->getExtension('id-ce-subjectKeyIdentifier');
        $this->currentKeyIdentifier = is_string($currentKeyIdentifier) ? $currentKeyIdentifier : null;

        return $x509;
    }

    /**
     * Save X.509 certificate
     *
     * @param array $cert
     * @param int $format optional
     * @access public
     * @return string
     */
    function saveX509($cert, $format = self::FORMAT_PEM)
    {
        if (!is_array($cert) || !isset($cert['tbsCertificate'])) {
            return false;
        }

        switch (true) {
            // "case !$a: case !$b: break; default: whatever();" is the same thing as "if ($a && $b) whatever()"
            case !($algorithm = $this->_subArray($cert, 'tbsCertificate/subjectPublicKeyInfo/algorithm/algorithm')):
            case is_object($cert['tbsCertificate']['subjectPublicKeyInfo']['subjectPublicKey']):
                break;
            default:
                switch ($algorithm) {
                    case 'rsaEncryption':
                        $cert['tbsCertificate']['subjectPublicKeyInfo']['subjectPublicKey']
                            = base64_encode("\0" . base64_decode(preg_replace('#-.+-|[\r\n]#', '', $cert['tbsCertificate']['subjectPublicKeyInfo']['subjectPublicKey'])));
                        /* "[For RSA keys] the parameters field MUST have ASN.1 type NULL for this algorithm identifier."
                           -- https://tools.ietf.org/html/rfc3279#section-2.3.1

                           given that and the fact that RSA keys appear ot be the only key type for which the parameters field can be blank,
                           it seems like perhaps the ASN.1 description ought not say the parameters field is OPTIONAL, but whatever.
                         */
                        $cert['tbsCertificate']['subjectPublicKeyInfo']['algorithm']['parameters'] = null;
                        // https://tools.ietf.org/html/rfc3279#section-2.2.1
                        $cert['signatureAlgorithm']['parameters'] = null;
                        $cert['tbsCertificate']['signature']['parameters'] = null;
                }
        }

        $asn1 = new ASN1();
        $asn1->loadOIDs($this->oids);

        $filters = array();
        $type_utf8_string = array('type' => ASN1::TYPE_UTF8_STRING);
        $filters['tbsCertificate']['signature']['parameters'] = $type_utf8_string;
        $filters['tbsCertificate']['signature']['issuer']['rdnSequence']['value'] = $type_utf8_string;
        $filters['tbsCertificate']['issuer']['rdnSequence']['value'] = $type_utf8_string;
        $filters['tbsCertificate']['subject']['rdnSequence']['value'] = $type_utf8_string;
        $filters['tbsCertificate']['subjectPublicKeyInfo']['algorithm']['parameters'] = $type_utf8_string;
        $filters['signatureAlgorithm']['parameters'] = $type_utf8_string;
        $filters['authorityCertIssuer']['directoryName']['rdnSequence']['value'] = $type_utf8_string;
        //$filters['policyQualifiers']['qualifier'] = $type_utf8_string;
        $filters['distributionPoint']['fullName']['directoryName']['rdnSequence']['value'] = $type_utf8_string;
        $filters['directoryName']['rdnSequence']['value'] = $type_utf8_string;

        /* in the case of policyQualifiers/qualifier, the type has to be \phpseclib\File\ASN1::TYPE_IA5_STRING.
           \phpseclib\File\ASN1::TYPE_PRINTABLE_STRING will cause OpenSSL's X.509 parser to spit out random
           characters.
         */
        $filters['policyQualifiers']['qualifier']
            = array('type' => ASN1::TYPE_IA5_STRING);

        $asn1->loadFilters($filters);

        $this->_mapOutExtensions($cert, 'tbsCertificate/extensions', $asn1);
        $this->_mapOutDNs($cert, 'tbsCertificate/issuer/rdnSequence', $asn1);
        $this->_mapOutDNs($cert, 'tbsCertificate/subject/rdnSequence', $asn1);

        $cert = $asn1->encodeDER($cert, $this->Certificate);

        switch ($format) {
            case self::FORMAT_DER:
                return $cert;
            // case self::FORMAT_PEM:
            default:
                return "-----BEGIN CERTIFICATE-----\r\n" . chunk_split(base64_encode($cert), 64) . '-----END CERTIFICATE-----';
        }
    }

    /**
     * Map extension values from octet string to extension-specific internal
     *   format.
     *
     * @param array $root (by reference)
     * @param string $path
     * @param object $asn1
     * @access private
     */
    function _mapInExtensions(&$root, $path, $asn1)
    {
        $extensions = &$this->_subArrayUnchecked($root, $path);

        if ($extensions) {
            for ($i = 0; $i < count($extensions); $i++) {
                $id = $extensions[$i]['extnId'];
                $value = &$extensions[$i]['extnValue'];
                $value = base64_decode($value);
                /* [extnValue] contains the DER encoding of an ASN.1 value
                   corresponding to the extension type identified by extnID */
                $map = $this->_getMapping($id);
                if (!is_bool($map)) {
                    $decoder = $id == 'id-ce-nameConstraints' ?
                        array($this, '_decodeNameConstraintIP') :
                        array($this, '_decodeIP');
                    $decoded = $asn1->decodeBER($value);
                    $mapped = $asn1->asn1map($decoded[0], $map, array('iPAddress' => $decoder));
                    $value = $mapped === false ? $decoded[0] : $mapped;

                    if ($id == 'id-ce-certificatePolicies') {
                        for ($j = 0; $j < count($value); $j++) {
                            if (!isset($value[$j]['policyQualifiers'])) {
                                continue;
                            }
                            for ($k = 0; $k < count($value[$j]['policyQualifiers']); $k++) {
                                $subid = $value[$j]['policyQualifiers'][$k]['policyQualifierId'];
                                $map = $this->_getMapping($subid);
                                $subvalue = &$value[$j]['policyQualifiers'][$k]['qualifier'];
                                if ($map !== false) {
                                    $decoded = $asn1->decodeBER($subvalue);
                                    $mapped = $asn1->asn1map($decoded[0], $map);
                                    $subvalue = $mapped === false ? $decoded[0] : $mapped;
                                }
                            }
                        }
                    }
                } else {
                    $value = base64_encode($value);
                }
            }
        }
    }

    /**
     * Map extension values from extension-specific internal format to
     *   octet string.
     *
     * @param array $root (by reference)
     * @param string $path
     * @param object $asn1
     * @access private
     */
    function _mapOutExtensions(&$root, $path, $asn1)
    {
        $extensions = &$this->_subArray($root, $path);

        if (is_array($extensions)) {
            $size = count($extensions);
            for ($i = 0; $i < $size; $i++) {
                if ($extensions[$i] instanceof Element) {
                    continue;
                }

                $id = $extensions[$i]['extnId'];
                $value = &$extensions[$i]['extnValue'];

                switch ($id) {
                    case 'id-ce-certificatePolicies':
                        for ($j = 0; $j < count($value); $j++) {
                            if (!isset($value[$j]['policyQualifiers'])) {
                                continue;
                            }
                            for ($k = 0; $k < count($value[$j]['policyQualifiers']); $k++) {
                                $subid = $value[$j]['policyQualifiers'][$k]['policyQualifierId'];
                                $map = $this->_getMapping($subid);
                                $subvalue = &$value[$j]['policyQualifiers'][$k]['qualifier'];
                                if ($map !== false) {
                                    // by default \phpseclib\File\ASN1 will try to render qualifier as a \phpseclib\File\ASN1::TYPE_IA5_STRING since it's
                                    // actual type is \phpseclib\File\ASN1::TYPE_ANY
                                    $subvalue = new Element($asn1->encodeDER($subvalue, $map));
                                }
                            }
                        }
                        break;
                    case 'id-ce-authorityKeyIdentifier': // use 00 as the serial number instead of an empty string
                        if (isset($value['authorityCertSerialNumber'])) {
                            if ($value['authorityCertSerialNumber']->toBytes() == '') {
                                $temp = chr((ASN1::CLASS_CONTEXT_SPECIFIC << 6) | 2) . "\1\0";
                                $value['authorityCertSerialNumber'] = new Element($temp);
                            }
                        }
                }

                /* [extnValue] contains the DER encoding of an ASN.1 value
                   corresponding to the extension type identified by extnID */
                $map = $this->_getMapping($id);
                if (is_bool($map)) {
                    if (!$map) {
                        user_error($id . ' is not a currently supported extension');
                        unset($extensions[$i]);
                    }
                } else {
                    $temp = $asn1->encodeDER($value, $map, array('iPAddress' => array($this, '_encodeIP')));
                    $value = base64_encode($temp);
                }
            }
        }
    }

    /**
     * Map attribute values from ANY type to attribute-specific internal
     *   format.
     *
     * @param array $root (by reference)
     * @param string $path
     * @param object $asn1
     * @access private
     */
    function _mapInAttributes(&$root, $path, $asn1)
    {
        $attributes = &$this->_subArray($root, $path);

        if (is_array($attributes)) {
            for ($i = 0; $i < count($attributes); $i++) {
                $id = $attributes[$i]['type'];
                /* $value contains the DER encoding of an ASN.1 value
                   corresponding to the attribute type identified by type */
                $map = $this->_getMapping($id);
                if (is_array($attributes[$i]['value'])) {
                    $values = &$attributes[$i]['value'];
                    for ($j = 0; $j < count($values); $j++) {
                        $value = $asn1->encodeDER($values[$j], $this->AttributeValue);
                        $decoded = $asn1->decodeBER($value);
                        if (!is_bool($map)) {
                            $mapped = $asn1->asn1map($decoded[0], $map);
                            if ($mapped !== false) {
                                $values[$j] = $mapped;
                            }
                            if ($id == 'pkcs-9-at-extensionRequest' && $this->_isSubArrayValid($values, $j)) {
                                $this->_mapInExtensions($values, $j, $asn1);
                            }
                        } elseif ($map) {
                            $values[$j] = base64_encode($value);
                        }
                    }
                }
            }
        }
    }

    /**
     * Map attribute values from attribute-specific internal format to
     *   ANY type.
     *
     * @param array $root (by reference)
     * @param string $path
     * @param object $asn1
     * @access private
     */
    function _mapOutAttributes(&$root, $path, $asn1)
    {
        $attributes = &$this->_subArray($root, $path);

        if (is_array($attributes)) {
            $size = count($attributes);
            for ($i = 0; $i < $size; $i++) {
                /* [value] contains the DER encoding of an ASN.1 value
                   corresponding to the attribute type identified by type */
                $id = $attributes[$i]['type'];
                $map = $this->_getMapping($id);
                if ($map === false) {
                    user_error($id . ' is not a currently supported attribute', E_USER_NOTICE);
                    unset($attributes[$i]);
                } elseif (is_array($attributes[$i]['value'])) {
                    $values = &$attributes[$i]['value'];
                    for ($j = 0; $j < count($values); $j++) {
                        switch ($id) {
                            case 'pkcs-9-at-extensionRequest':
                                $this->_mapOutExtensions($values, $j, $asn1);
                                break;
                        }

                        if (!is_bool($map)) {
                            $temp = $asn1->encodeDER($values[$j], $map);
                            $decoded = $asn1->decodeBER($temp);
                            $values[$j] = $asn1->asn1map($decoded[0], $this->AttributeValue);
                        }
                    }
                }
            }
        }
    }

    /**
     * Map DN values from ANY type to DN-specific internal
     *   format.
     *
     * @param array $root (by reference)
     * @param string $path
     * @param object $asn1
     * @access private
     */
    function _mapInDNs(&$root, $path, $asn1)
    {
        $dns = &$this->_subArray($root, $path);

        if (is_array($dns)) {
            for ($i = 0; $i < count($dns); $i++) {
                for ($j = 0; $j < count($dns[$i]); $j++) {
                    $type = $dns[$i][$j]['type'];
                    $value = &$dns[$i][$j]['value'];
                    if (is_object($value) && $value instanceof Element) {
                        $map = $this->_getMapping($type);
                        if (!is_bool($map)) {
                            $decoded = $asn1->decodeBER($value);
                            $value = $asn1->asn1map($decoded[0], $map);
                        }
                    }
                }
            }
        }
    }

    /**
     * Map DN values from DN-specific internal format to
     *   ANY type.
     *
     * @param array $root (by reference)
     * @param string $path
     * @param object $asn1
     * @access private
     */
    function _mapOutDNs(&$root, $path, $asn1)
    {
        $dns = &$this->_subArray($root, $path);

        if (is_array($dns)) {
            $size = count($dns);
            for ($i = 0; $i < $size; $i++) {
                for ($j = 0; $j < count($dns[$i]); $j++) {
                    $type = $dns[$i][$j]['type'];
                    $value = &$dns[$i][$j]['value'];
                    if (is_object($value) && $value instanceof Element) {
                        continue;
                    }

                    $map = $this->_getMapping($type);
                    if (!is_bool($map)) {
                        $value = new Element($asn1->encodeDER($value, $map));
                    }
                }
            }
        }
    }

    /**
     * Associate an extension ID to an extension mapping
     *
     * @param string $extnId
     * @access private
     * @return mixed
     */
    function _getMapping($extnId)
    {
        if (!is_string($extnId)) { // eg. if it's a \phpseclib\File\ASN1\Element object
            return true;
        }

        switch ($extnId) {
            case 'id-ce-keyUsage':
                return $this->KeyUsage;
            case 'id-ce-basicConstraints':
                return $this->BasicConstraints;
            case 'id-ce-subjectKeyIdentifier':
                return $this->KeyIdentifier;
            case 'id-ce-cRLDistributionPoints':
                return $this->CRLDistributionPoints;
            case 'id-ce-authorityKeyIdentifier':
                return $this->AuthorityKeyIdentifier;
            case 'id-ce-certificatePolicies':
                return $this->CertificatePolicies;
            case 'id-ce-extKeyUsage':
                return $this->ExtKeyUsageSyntax;
            case 'id-pe-authorityInfoAccess':
                return $this->AuthorityInfoAccessSyntax;
            case 'id-pe-subjectInfoAccess':
                return $this->SubjectInfoAccessSyntax;
            case 'id-ce-subjectAltName':
                return $this->SubjectAltName;
            case 'id-ce-subjectDirectoryAttributes':
                return $this->SubjectDirectoryAttributes;
            case 'id-ce-privateKeyUsagePeriod':
                return $this->PrivateKeyUsagePeriod;
            case 'id-ce-issuerAltName':
                return $this->IssuerAltName;
            case 'id-ce-policyMappings':
                return $this->PolicyMappings;
            case 'id-ce-nameConstraints':
                return $this->NameConstraints;

            case 'netscape-cert-type':
                return $this->netscape_cert_type;
            case 'netscape-comment':
                return $this->netscape_comment;
            case 'netscape-ca-policy-url':
                return $this->netscape_ca_policy_url;

            // since id-qt-cps isn't a constructed type it will have already been decoded as a string by the time it gets
            // back around to asn1map() and we don't want it decoded again.
            //case 'id-qt-cps':
            //    return $this->CPSuri;
            case 'id-qt-unotice':
                return $this->UserNotice;

            // the following OIDs are unsupported but we don't want them to give notices when calling saveX509().
            case 'id-pe-logotype': // http://www.ietf.org/rfc/rfc3709.txt
            case 'entrustVersInfo':
            // http://support.microsoft.com/kb/287547
            case '1.3.6.1.4.1.311.20.2': // szOID_ENROLL_CERTTYPE_EXTENSION
            case '1.3.6.1.4.1.311.21.1': // szOID_CERTSRV_CA_VERSION
            // "SET Secure Electronic Transaction Specification"
            // http://www.maithean.com/docs/set_bk3.pdf
            case '2.23.42.7.0': // id-set-hashedRootKey
            // "Certificate Transparency"
            // https://tools.ietf.org/html/rfc6962
            case '1.3.6.1.4.1.11129.2.4.2':
            // "Qualified Certificate statements"
            // https://tools.ietf.org/html/rfc3739#section-3.2.6
            case '1.3.6.1.5.5.7.1.3':
                return true;

            // CSR attributes
            case 'pkcs-9-at-unstructuredName':
                return $this->PKCS9String;
            case 'pkcs-9-at-challengePassword':
                return $this->DirectoryString;
            case 'pkcs-9-at-extensionRequest':
                return $this->Extensions;

            // CRL extensions.
            case 'id-ce-cRLNumber':
                return $this->CRLNumber;
            case 'id-ce-deltaCRLIndicator':
                return $this->CRLNumber;
            case 'id-ce-issuingDistributionPoint':
                return $this->IssuingDistributionPoint;
            case 'id-ce-freshestCRL':
                return $this->CRLDistributionPoints;
            case 'id-ce-cRLReasons':
                return $this->CRLReason;
            case 'id-ce-invalidityDate':
                return $this->InvalidityDate;
            case 'id-ce-certificateIssuer':
                return $this->CertificateIssuer;
            case 'id-ce-holdInstructionCode':
                return $this->HoldInstructionCode;
            case 'id-at-postalAddress':
                return $this->PostalAddress;
        }

        return false;
    }

    /**
     * Load an X.509 certificate as a certificate authority
     *
     * @param string $cert
     * @access public
     * @return bool
     */
    function loadCA($cert)
    {
        $olddn = $this->dn;
        $oldcert = $this->currentCert;
        $oldsigsubj = $this->signatureSubject;
        $oldkeyid = $this->currentKeyIdentifier;

        $cert = $this->loadX509($cert);
        if (!$cert) {
            $this->dn = $olddn;
            $this->currentCert = $oldcert;
            $this->signatureSubject = $oldsigsubj;
            $this->currentKeyIdentifier = $oldkeyid;

            return false;
        }

        /* From RFC5280 "PKIX Certificate and CRL Profile":

           If the keyUsage extension is present, then the subject public key
           MUST NOT be used to verify signatures on certificates or CRLs unless
           the corresponding keyCertSign or cRLSign bit is set. */
        //$keyUsage = $this->getExtension('id-ce-keyUsage');
        //if ($keyUsage && !in_array('keyCertSign', $keyUsage)) {
        //    return false;
        //}

        /* From RFC5280 "PKIX Certificate and CRL Profile":

           The cA boolean indicates whether the certified public key may be used
           to verify certificate signatures.  If the cA boolean is not asserted,
           then the keyCertSign bit in the key usage extension MUST NOT be
           asserted.  If the basic constraints extension is not present in a
           version 3 certificate, or the extension is present but the cA boolean
           is not asserted, then the certified public key MUST NOT be used to
           verify certificate signatures. */
        //$basicConstraints = $this->getExtension('id-ce-basicConstraints');
        //if (!$basicConstraints || !$basicConstraints['cA']) {
        //    return false;
        //}

        $this->CAs[] = $cert;

        $this->dn = $olddn;
        $this->currentCert = $oldcert;
        $this->signatureSubject = $oldsigsubj;

        return true;
    }

    /**
     * Validate an X.509 certificate against a URL
     *
     * From RFC2818 "HTTP over TLS":
     *
     * Matching is performed using the matching rules specified by
     * [RFC2459].  If more than one identity of a given type is present in
     * the certificate (e.g., more than one dNSName name, a match in any one
     * of the set is considered acceptable.) Names may contain the wildcard
     * character * which is considered to match any single domain name
     * component or component fragment. E.g., *.a.com matches foo.a.com but
     * not bar.foo.a.com. f*.com matches foo.com but not bar.com.
     *
     * @param string $url
     * @access public
     * @return bool
     */
    function validateURL($url)
    {
        if (!is_array($this->currentCert) || !isset($this->currentCert['tbsCertificate'])) {
            return false;
        }

        $components = parse_url($url);
        if (!isset($components['host'])) {
            return false;
        }

        if ($names = $this->getExtension('id-ce-subjectAltName')) {
            foreach ($names as $name) {
                foreach ($name as $key => $value) {
                    $value = preg_quote($value);
                    $value = str_replace('\*', '[^.]*', $value);
                    switch ($key) {
                        case 'dNSName':
                            /* From RFC2818 "HTTP over TLS":

                               If a subjectAltName extension of type dNSName is present, that MUST
                               be used as the identity. Otherwise, the (most specific) Common Name
                               field in the Subject field of the certificate MUST be used. Although
                               the use of the Common Name is existing practice, it is deprecated and
                               Certification Authorities are encouraged to use the dNSName instead. */
                            if (preg_match('#^' . $value . '$#', $components['host'])) {
                                return true;
                            }
                            break;
                        case 'iPAddress':
                            /* From RFC2818 "HTTP over TLS":

                               In some cases, the URI is specified as an IP address rather than a
                               hostname. In this case, the iPAddress subjectAltName must be present
                               in the certificate and must exactly match the IP in the URI. */
                            if (preg_match('#(?:\d{1-3}\.){4}#', $components['host'] . '.') && preg_match('#^' . $value . '$#', $components['host'])) {
                                return true;
                            }
                    }
                }
            }
            return false;
        }

        if ($value = $this->getDNProp('id-at-commonName')) {
            $value = str_replace(array('.', '*'), array('\.', '[^.]*'), $value[0]);
            return preg_match('#^' . $value . '$#', $components['host']);
        }

        return false;
    }

    /**
     * Validate a date
     *
     * If $date isn't defined it is assumed to be the current date.
     *
     * @param \DateTime|string $date optional
     * @access public
     */
    function validateDate($date = null)
    {
        if (!is_array($this->currentCert) || !isset($this->currentCert['tbsCertificate'])) {
            return false;
        }

        if (!isset($date)) {
            $date = new DateTime(null, new DateTimeZone(@date_default_timezone_get()));
        }

        $notBefore = $this->currentCert['tbsCertificate']['validity']['notBefore'];
        $notBefore = isset($notBefore['generalTime']) ? $notBefore['generalTime'] : $notBefore['utcTime'];

        $notAfter = $this->currentCert['tbsCertificate']['validity']['notAfter'];
        $notAfter = isset($notAfter['generalTime']) ? $notAfter['generalTime'] : $notAfter['utcTime'];

        if (is_string($date)) {
            $date = new DateTime($date, new DateTimeZone(@date_default_timezone_get()));
        }

        $notBefore = new DateTime($notBefore, new DateTimeZone(@date_default_timezone_get()));
        $notAfter = new DateTime($notAfter, new DateTimeZone(@date_default_timezone_get()));

        switch (true) {
            case $date < $notBefore:
            case $date > $notAfter:
                return false;
        }

        return true;
    }

    /**
     * Fetches a URL
     *
     * @param string $url
     * @access private
     * @return bool|string
     */
    static function _fetchURL($url)
    {
        if (self::$disable_url_fetch) {
            return false;
        }

        $parts = parse_url($url);
        $data = '';
        switch ($parts['scheme']) {
            case 'http':
                $fsock = @fsockopen($parts['host'], isset($parts['port']) ? $parts['port'] : 80);
                if (!$fsock) {
                    return false;
                }
                $path = $parts['path'];
                if (isset($parts['query'])) {
                    $path.= '?' . $parts['query'];
                }
                fputs($fsock, "GET $path HTTP/1.0\r\n");
                fputs($fsock, "Host: $parts[host]\r\n\r\n");
                $line = fgets($fsock, 1024);
                if (strlen($line) < 3) {
                    return false;
                }
                preg_match('#HTTP/1.\d (\d{3})#', $line, $temp);
                if ($temp[1] != '200') {
                    return false;
                }

                // skip the rest of the headers in the http response
                while (!feof($fsock) && fgets($fsock, 1024) != "\r\n") {
                }

                while (!feof($fsock)) {
                    $temp = fread($fsock, 1024);
                    if ($temp === false) {
                        return false;
                    }
                    $data.= $temp;
                }

                break;
            //case 'ftp':
            //case 'ldap':
            //default:
        }

        return $data;
    }

    /**
     * Validates an intermediate cert as identified via authority info access extension
     *
     * See https://tools.ietf.org/html/rfc4325 for more info
     *
     * @param bool $caonly
     * @param int $count
     * @access private
     * @return bool
     */
    function _testForIntermediate($caonly, $count)
    {
        $opts = $this->getExtension('id-pe-authorityInfoAccess');
        if (!is_array($opts)) {
            return false;
        }
        foreach ($opts as $opt) {
            if ($opt['accessMethod'] == 'id-ad-caIssuers') {
                // accessLocation is a GeneralName. GeneralName fields support stuff like email addresses, IP addresses, LDAP,
                // etc, but we're only supporting URI's. URI's and LDAP are the only thing https://tools.ietf.org/html/rfc4325
                // discusses
                if (isset($opt['accessLocation']['uniformResourceIdentifier'])) {
                    $url = $opt['accessLocation']['uniformResourceIdentifier'];
                    break;
                }
            }
        }

        if (!isset($url)) {
            return false;
        }

        $cert = static::_fetchURL($url);
        if (!is_string($cert)) {
            return false;
        }

        $parent = new static();
        $parent->CAs = $this->CAs;
        /*
         "Conforming applications that support HTTP or FTP for accessing
          certificates MUST be able to accept .cer files and SHOULD be able
          to accept .p7c files." -- https://tools.ietf.org/html/rfc4325

         A .p7c file is 'a "certs-only" CMS message as specified in RFC 2797"

         These are currently unsupported
        */
        if (!is_array($parent->loadX509($cert))) {
            return false;
        }

        if (!$parent->_validateSignatureCountable($caonly, ++$count)) {
            return false;
        }

        $this->CAs[] = $parent->currentCert;
        //$this->loadCA($cert);

        return true;
    }

    /**
     * Validate a signature
     *
     * Works on X.509 certs, CSR's and CRL's.
     * Returns true if the signature is verified, false if it is not correct or null on error
     *
     * By default returns false for self-signed certs. Call validateSignature(false) to make this support
     * self-signed.
     *
     * The behavior of this function is inspired by {@link http://php.net/openssl-verify openssl_verify}.
     *
     * @param bool $caonly optional
     * @access public
     * @return mixed
     */
    function validateSignature($caonly = true)
    {
        return $this->_validateSignatureCountable($caonly, 0);
    }

    /**
     * Validate a signature
     *
     * Performs said validation whilst keeping track of how many times validation method is called
     *
     * @param bool $caonly
     * @param int $count
     * @access private
     * @return mixed
     */
    function _validateSignatureCountable($caonly, $count)
    {
        if (!is_array($this->currentCert) || !isset($this->signatureSubject)) {
            return null;
        }

        if ($count == self::$recur_limit) {
            return false;
        }

        /* TODO:
           "emailAddress attribute values are not case-sensitive (e.g., "subscriber@example.com" is the same as "SUBSCRIBER@EXAMPLE.COM")."
            -- http://tools.ietf.org/html/rfc5280#section-4.1.2.6

           implement pathLenConstraint in the id-ce-basicConstraints extension */

        switch (true) {
            case isset($this->currentCert['tbsCertificate']):
                // self-signed cert
                switch (true) {
                    case !defined('FILE_X509_IGNORE_TYPE') && $this->currentCert['tbsCertificate']['issuer'] === $this->currentCert['tbsCertificate']['subject']:
                    case defined('FILE_X509_IGNORE_TYPE') && $this->getIssuerDN(self::DN_STRING) === $this->getDN(self::DN_STRING):
                        $authorityKey = $this->getExtension('id-ce-authorityKeyIdentifier');
                        $subjectKeyID = $this->getExtension('id-ce-subjectKeyIdentifier');
                        switch (true) {
                            case !is_array($authorityKey):
                            case !$subjectKeyID:
                            case isset($authorityKey['keyIdentifier']) && $authorityKey['keyIdentifier'] === $subjectKeyID:
                                $signingCert = $this->currentCert; // working cert
                        }
                }

                if (!empty($this->CAs)) {
                    for ($i = 0; $i < count($this->CAs); $i++) {
                        // even if the cert is a self-signed one we still want to see if it's a CA;
                        // if not, we'll conditionally return an error
                        $ca = $this->CAs[$i];
                        switch (true) {
                            case !defined('FILE_X509_IGNORE_TYPE') && $this->currentCert['tbsCertificate']['issuer'] === $ca['tbsCertificate']['subject']:
                            case defined('FILE_X509_IGNORE_TYPE') && $this->getDN(self::DN_STRING, $this->currentCert['tbsCertificate']['issuer']) === $this->getDN(self::DN_STRING, $ca['tbsCertificate']['subject']):
                                $authorityKey = $this->getExtension('id-ce-authorityKeyIdentifier');
                                $subjectKeyID = $this->getExtension('id-ce-subjectKeyIdentifier', $ca);
                                switch (true) {
                                    case !is_array($authorityKey):
                                    case !$subjectKeyID:
                                    case isset($authorityKey['keyIdentifier']) && $authorityKey['keyIdentifier'] === $subjectKeyID:
                                        if (is_array($authorityKey) && isset($authorityKey['authorityCertSerialNumber']) && !$authorityKey['authorityCertSerialNumber']->equals($ca['tbsCertificate']['serialNumber'])) {
                                            break 2; // serial mismatch - check other ca
                                        }
                                        $signingCert = $ca; // working cert
                                        break 3;
                                }
                        }
                    }
                    if (count($this->CAs) == $i && $caonly) {
                        return $this->_testForIntermediate($caonly, $count) && $this->validateSignature($caonly);
                    }
                } elseif (!isset($signingCert) || $caonly) {
                    return $this->_testForIntermediate($caonly, $count) && $this->validateSignature($caonly);
                }
                return $this->_validateSignature(
                    $signingCert['tbsCertificate']['subjectPublicKeyInfo']['algorithm']['algorithm'],
                    $signingCert['tbsCertificate']['subjectPublicKeyInfo']['subjectPublicKey'],
                    $this->currentCert['signatureAlgorithm']['algorithm'],
                    substr(base64_decode($this->currentCert['signature']), 1),
                    $this->signatureSubject
                );
            case isset($this->currentCert['certificationRequestInfo']):
                return $this->_validateSignature(
                    $this->currentCert['certificationRequestInfo']['subjectPKInfo']['algorithm']['algorithm'],
                    $this->currentCert['certificationRequestInfo']['subjectPKInfo']['subjectPublicKey'],
                    $this->currentCert['signatureAlgorithm']['algorithm'],
                    substr(base64_decode($this->currentCert['signature']), 1),
                    $this->signatureSubject
                );
            case isset($this->currentCert['publicKeyAndChallenge']):
                return $this->_validateSignature(
                    $this->currentCert['publicKeyAndChallenge']['spki']['algorithm']['algorithm'],
                    $this->currentCert['publicKeyAndChallenge']['spki']['subjectPublicKey'],
                    $this->currentCert['signatureAlgorithm']['algorithm'],
                    substr(base64_decode($this->currentCert['signature']), 1),
                    $this->signatureSubject
                );
            case isset($this->currentCert['tbsCertList']):
                if (!empty($this->CAs)) {
                    for ($i = 0; $i < count($this->CAs); $i++) {
                        $ca = $this->CAs[$i];
                        switch (true) {
                            case !defined('FILE_X509_IGNORE_TYPE') && $this->currentCert['tbsCertList']['issuer'] === $ca['tbsCertificate']['subject']:
                            case defined('FILE_X509_IGNORE_TYPE') && $this->getDN(self::DN_STRING, $this->currentCert['tbsCertList']['issuer']) === $this->getDN(self::DN_STRING, $ca['tbsCertificate']['subject']):
                                $authorityKey = $this->getExtension('id-ce-authorityKeyIdentifier');
                                $subjectKeyID = $this->getExtension('id-ce-subjectKeyIdentifier', $ca);
                                switch (true) {
                                    case !is_array($authorityKey):
                                    case !$subjectKeyID:
                                    case isset($authorityKey['keyIdentifier']) && $authorityKey['keyIdentifier'] === $subjectKeyID:
                                        if (is_array($authorityKey) && isset($authorityKey['authorityCertSerialNumber']) && !$authorityKey['authorityCertSerialNumber']->equals($ca['tbsCertificate']['serialNumber'])) {
                                            break 2; // serial mismatch - check other ca
                                        }
                                        $signingCert = $ca; // working cert
                                        break 3;
                                }
                        }
                    }
                }
                if (!isset($signingCert)) {
                    return false;
                }
                return $this->_validateSignature(
                    $signingCert['tbsCertificate']['subjectPublicKeyInfo']['algorithm']['algorithm'],
                    $signingCert['tbsCertificate']['subjectPublicKeyInfo']['subjectPublicKey'],
                    $this->currentCert['signatureAlgorithm']['algorithm'],
                    substr(base64_decode($this->currentCert['signature']), 1),
                    $this->signatureSubject
                );
            default:
                return false;
        }
    }

    /**
     * Validates a signature
     *
     * Returns true if the signature is verified, false if it is not correct or null on error
     *
     * @param string $publicKeyAlgorithm
     * @param string $publicKey
     * @param string $signatureAlgorithm
     * @param string $signature
     * @param string $signatureSubject
     * @access private
     * @return int
     */
    function _validateSignature($publicKeyAlgorithm, $publicKey, $signatureAlgorithm, $signature, $signatureSubject)
    {
        switch ($publicKeyAlgorithm) {
            case 'rsaEncryption':
                $rsa = new RSA();
                $rsa->loadKey($publicKey);

                switch ($signatureAlgorithm) {
                    case 'md2WithRSAEncryption':
                    case 'md5WithRSAEncryption':
                    case 'sha1WithRSAEncryption':
                    case 'sha224WithRSAEncryption':
                    case 'sha256WithRSAEncryption':
                    case 'sha384WithRSAEncryption':
                    case 'sha512WithRSAEncryption':
                        $rsa->setHash(preg_replace('#WithRSAEncryption$#', '', $signatureAlgorithm));
                        $rsa->setSignatureMode(RSA::SIGNATURE_PKCS1);
                        if (!@$rsa->verify($signatureSubject, $signature)) {
                            return false;
                        }
                        break;
                    default:
                        return null;
                }
                break;
            default:
                return null;
        }

        return true;
    }

    /**
     * Sets the recursion limit
     *
     * When validating a signature it may be necessary to download intermediate certs from URI's.
     * An intermediate cert that linked to itself would result in an infinite loop so to prevent
     * that we set a recursion limit. A negative number means that there is no recursion limit.
     *
     * @param int $count
     * @access public
     */
    static function setRecurLimit($count)
    {
        self::$recur_limit = $count;
    }

    /**
     * Prevents URIs from being automatically retrieved
     *
     * @access public
     */
    static function disableURLFetch()
    {
        self::$disable_url_fetch = true;
    }

    /**
     * Allows URIs to be automatically retrieved
     *
     * @access public
     */
    static function enableURLFetch()
    {
        self::$disable_url_fetch = false;
    }

    /**
     * Reformat public keys
     *
     * Reformats a public key to a format supported by phpseclib (if applicable)
     *
     * @param string $algorithm
     * @param string $key
     * @access private
     * @return string
     */
    function _reformatKey($algorithm, $key)
    {
        switch ($algorithm) {
            case 'rsaEncryption':
                return
                    "-----BEGIN RSA PUBLIC KEY-----\r\n" .
                    // subjectPublicKey is stored as a bit string in X.509 certs.  the first byte of a bit string represents how many bits
                    // in the last byte should be ignored.  the following only supports non-zero stuff but as none of the X.509 certs Firefox
                    // uses as a cert authority actually use a non-zero bit I think it's safe to assume that none do.
                    chunk_split(base64_encode(substr(base64_decode($key), 1)), 64) .
                    '-----END RSA PUBLIC KEY-----';
            default:
                return $key;
        }
    }

    /**
     * Decodes an IP address
     *
     * Takes in a base64 encoded "blob" and returns a human readable IP address
     *
     * @param string $ip
     * @access private
     * @return string
     */
    function _decodeIP($ip)
    {
        return inet_ntop(base64_decode($ip));
    }

    /**
     * Decodes an IP address in a name constraints extension
     *
     * Takes in a base64 encoded "blob" and returns a human readable IP address / mask
     *
     * @param string $ip
     * @access private
     * @return array
     */
    function _decodeNameConstraintIP($ip)
    {
        $ip = base64_decode($ip);
        $size = strlen($ip) >> 1;
        $mask = substr($ip, $size);
        $ip = substr($ip, 0, $size);
        return array(inet_ntop($ip), inet_ntop($mask));
    }

    /**
     * Encodes an IP address
     *
     * Takes a human readable IP address into a base64-encoded "blob"
     *
     * @param string|array $ip
     * @access private
     * @return string
     */
    function _encodeIP($ip)
    {
        return is_string($ip) ?
            base64_encode(inet_pton($ip)) :
            base64_encode(inet_pton($ip[0]) . inet_pton($ip[1]));
    }

    /**
     * "Normalizes" a Distinguished Name property
     *
     * @param string $propName
     * @access private
     * @return mixed
     */
    function _translateDNProp($propName)
    {
        switch (strtolower($propName)) {
            case 'jurisdictionofincorporationcountryname':
            case 'jurisdictioncountryname':
            case 'jurisdictionc':
                return 'jurisdictionOfIncorporationCountryName';
            case 'jurisdictionofincorporationstateorprovincename':
            case 'jurisdictionstateorprovincename':
            case 'jurisdictionst':
                return 'jurisdictionOfIncorporationStateOrProvinceName';
            case 'jurisdictionlocalityname':
            case 'jurisdictionl':
                return 'jurisdictionLocalityName';
            case 'id-at-businesscategory':
            case 'businesscategory':
                return 'id-at-businessCategory';
            case 'id-at-countryname':
            case 'countryname':
            case 'c':
                return 'id-at-countryName';
            case 'id-at-organizationname':
            case 'organizationname':
            case 'o':
                return 'id-at-organizationName';
            case 'id-at-dnqualifier':
            case 'dnqualifier':
                return 'id-at-dnQualifier';
            case 'id-at-commonname':
            case 'commonname':
            case 'cn':
                return 'id-at-commonName';
            case 'id-at-stateorprovincename':
            case 'stateorprovincename':
            case 'state':
            case 'province':
            case 'provincename':
            case 'st':
                return 'id-at-stateOrProvinceName';
            case 'id-at-localityname':
            case 'localityname':
            case 'l':
                return 'id-at-localityName';
            case 'id-emailaddress':
            case 'emailaddress':
                return 'pkcs-9-at-emailAddress';
            case 'id-at-serialnumber':
            case 'serialnumber':
                return 'id-at-serialNumber';
            case 'id-at-postalcode':
            case 'postalcode':
                return 'id-at-postalCode';
            case 'id-at-streetaddress':
            case 'streetaddress':
                return 'id-at-streetAddress';
            case 'id-at-name':
            case 'name':
                return 'id-at-name';
            case 'id-at-givenname':
            case 'givenname':
                return 'id-at-givenName';
            case 'id-at-surname':
            case 'surname':
            case 'sn':
                return 'id-at-surname';
            case 'id-at-initials':
            case 'initials':
                return 'id-at-initials';
            case 'id-at-generationqualifier':
            case 'generationqualifier':
                return 'id-at-generationQualifier';
            case 'id-at-organizationalunitname':
            case 'organizationalunitname':
            case 'ou':
                return 'id-at-organizationalUnitName';
            case 'id-at-pseudonym':
            case 'pseudonym':
                return 'id-at-pseudonym';
            case 'id-at-title':
            case 'title':
                return 'id-at-title';
            case 'id-at-description':
            case 'description':
                return 'id-at-description';
            case 'id-at-role':
            case 'role':
                return 'id-at-role';
            case 'id-at-uniqueidentifier':
            case 'uniqueidentifier':
            case 'x500uniqueidentifier':
                return 'id-at-uniqueIdentifier';
            case 'postaladdress':
            case 'id-at-postaladdress':
                return 'id-at-postalAddress';
            default:
                return false;
        }
    }

    /**
     * Set a Distinguished Name property
     *
     * @param string $propName
     * @param mixed $propValue
     * @param string $type optional
     * @access public
     * @return bool
     */
    function setDNProp($propName, $propValue, $type = 'utf8String')
    {
        if (empty($this->dn)) {
            $this->dn = array('rdnSequence' => array());
        }

        if (($propName = $this->_translateDNProp($propName)) === false) {
            return false;
        }

        foreach ((array) $propValue as $v) {
            if (!is_array($v) && isset($type)) {
                $v = array($type => $v);
            }
            $this->dn['rdnSequence'][] = array(
                array(
                    'type' => $propName,
                    'value'=> $v
                )
            );
        }

        return true;
    }

    /**
     * Remove Distinguished Name properties
     *
     * @param string $propName
     * @access public
     */
    function removeDNProp($propName)
    {
        if (empty($this->dn)) {
            return;
        }

        if (($propName = $this->_translateDNProp($propName)) === false) {
            return;
        }

        $dn = &$this->dn['rdnSequence'];
        $size = count($dn);
        for ($i = 0; $i < $size; $i++) {
            if ($dn[$i][0]['type'] == $propName) {
                unset($dn[$i]);
            }
        }

        $dn = array_values($dn);
        // fix for https://bugs.php.net/75433 affecting PHP 7.2
        if (!isset($dn[0])) {
            $dn = array_splice($dn, 0, 0);
        }
    }

    /**
     * Get Distinguished Name properties
     *
     * @param string $propName
     * @param array $dn optional
     * @param bool $withType optional
     * @return mixed
     * @access public
     */
    function getDNProp($propName, $dn = null, $withType = false)
    {
        if (!isset($dn)) {
            $dn = $this->dn;
        }

        if (empty($dn)) {
            return false;
        }

        if (($propName = $this->_translateDNProp($propName)) === false) {
            return false;
        }

        $asn1 = new ASN1();
        $asn1->loadOIDs($this->oids);
        $filters = array();
        $filters['value'] = array('type' => ASN1::TYPE_UTF8_STRING);
        $asn1->loadFilters($filters);
        $this->_mapOutDNs($dn, 'rdnSequence', $asn1);
        $dn = $dn['rdnSequence'];
        $result = array();
        for ($i = 0; $i < count($dn); $i++) {
            if ($dn[$i][0]['type'] == $propName) {
                $v = $dn[$i][0]['value'];
                if (!$withType) {
                    if (is_array($v)) {
                        foreach ($v as $type => $s) {
                            $type = array_search($type, $asn1->ANYmap, true);
                            if ($type !== false && isset($asn1->stringTypeSize[$type])) {
                                $s = $asn1->convert($s, $type);
                                if ($s !== false) {
                                    $v = $s;
                                    break;
                                }
                            }
                        }
                        if (is_array($v)) {
                            $v = array_pop($v); // Always strip data type.
                        }
                    } elseif (is_object($v) && $v instanceof Element) {
                        $map = $this->_getMapping($propName);
                        if (!is_bool($map)) {
                            $decoded = $asn1->decodeBER($v);
                            $v = $asn1->asn1map($decoded[0], $map);
                        }
                    }
                }
                $result[] = $v;
            }
        }

        return $result;
    }

    /**
     * Set a Distinguished Name
     *
     * @param mixed $dn
     * @param bool $merge optional
     * @param string $type optional
     * @access public
     * @return bool
     */
    function setDN($dn, $merge = false, $type = 'utf8String')
    {
        if (!$merge) {
            $this->dn = null;
        }

        if (is_array($dn)) {
            if (isset($dn['rdnSequence'])) {
                $this->dn = $dn; // No merge here.
                return true;
            }

            // handles stuff generated by openssl_x509_parse()
            foreach ($dn as $prop => $value) {
                if (!$this->setDNProp($prop, $value, $type)) {
                    return false;
                }
            }
            return true;
        }

        // handles everything else
        $results = preg_split('#((?:^|, *|/)(?:C=|O=|OU=|CN=|L=|ST=|SN=|postalCode=|streetAddress=|emailAddress=|serialNumber=|organizationalUnitName=|title=|description=|role=|x500UniqueIdentifier=|postalAddress=))#', $dn, -1, PREG_SPLIT_DELIM_CAPTURE);
        for ($i = 1; $i < count($results); $i+=2) {
            $prop = trim($results[$i], ', =/');
            $value = $results[$i + 1];
            if (!$this->setDNProp($prop, $value, $type)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get the Distinguished Name for a certificates subject
     *
     * @param mixed $format optional
     * @param array $dn optional
     * @access public
     * @return bool
     */
    function getDN($format = self::DN_ARRAY, $dn = null)
    {
        if (!isset($dn)) {
            $dn = isset($this->currentCert['tbsCertList']) ? $this->currentCert['tbsCertList']['issuer'] : $this->dn;
        }

        switch ((int) $format) {
            case self::DN_ARRAY:
                return $dn;
            case self::DN_ASN1:
                $asn1 = new ASN1();
                $asn1->loadOIDs($this->oids);
                $filters = array();
                $filters['rdnSequence']['value'] = array('type' => ASN1::TYPE_UTF8_STRING);
                $asn1->loadFilters($filters);
                $this->_mapOutDNs($dn, 'rdnSequence', $asn1);
                return $asn1->encodeDER($dn, $this->Name);
            case self::DN_CANON:
                //  No SEQUENCE around RDNs and all string values normalized as
                // trimmed lowercase UTF-8 with all spacing as one blank.
                // constructed RDNs will not be canonicalized
                $asn1 = new ASN1();
                $asn1->loadOIDs($this->oids);
                $filters = array();
                $filters['value'] = array('type' => ASN1::TYPE_UTF8_STRING);
                $asn1->loadFilters($filters);
                $result = '';
                $this->_mapOutDNs($dn, 'rdnSequence', $asn1);
                foreach ($dn['rdnSequence'] as $rdn) {
                    foreach ($rdn as $i => $attr) {
                        $attr = &$rdn[$i];
                        if (is_array($attr['value'])) {
                            foreach ($attr['value'] as $type => $v) {
                                $type = array_search($type, $asn1->ANYmap, true);
                                if ($type !== false && isset($asn1->stringTypeSize[$type])) {
                                    $v = $asn1->convert($v, $type);
                                    if ($v !== false) {
                                        $v = preg_replace('/\s+/', ' ', $v);
                                        $attr['value'] = strtolower(trim($v));
                                        break;
                                    }
                                }
                            }
                        }
                    }
                    $result .= $asn1->encodeDER($rdn, $this->RelativeDistinguishedName);
                }
                return $result;
            case self::DN_HASH:
                $dn = $this->getDN(self::DN_CANON, $dn);
                $hash = new Hash('sha1');
                $hash = $hash->hash($dn);
                extract(unpack('Vhash', $hash));
                return strtolower(bin2hex(pack('N', $hash)));
        }

        // Default is to return a string.
        $start = true;
        $output = '';

        $result = array();
        $asn1 = new ASN1();
        $asn1->loadOIDs($this->oids);
        $filters = array();
        $filters['rdnSequence']['value'] = array('type' => ASN1::TYPE_UTF8_STRING);
        $asn1->loadFilters($filters);
        $this->_mapOutDNs($dn, 'rdnSequence', $asn1);

        foreach ($dn['rdnSequence'] as $field) {
            $prop = $field[0]['type'];
            $value = $field[0]['value'];

            $delim = ', ';
            switch ($prop) {
                case 'id-at-countryName':
                    $desc = 'C';
                    break;
                case 'id-at-stateOrProvinceName':
                    $desc = 'ST';
                    break;
                case 'id-at-organizationName':
                    $desc = 'O';
                    break;
                case 'id-at-organizationalUnitName':
                    $desc = 'OU';
                    break;
                case 'id-at-commonName':
                    $desc = 'CN';
                    break;
                case 'id-at-localityName':
                    $desc = 'L';
                    break;
                case 'id-at-surname':
                    $desc = 'SN';
                    break;
                case 'id-at-uniqueIdentifier':
                    $delim = '/';
                    $desc = 'x500UniqueIdentifier';
                    break;
                case 'id-at-postalAddress':
                    $delim = '/';
                    $desc = 'postalAddress';
                    break;
                default:
                    $delim = '/';
                    $desc = preg_replace('#.+-([^-]+)$#', '$1', $prop);
            }

            if (!$start) {
                $output.= $delim;
            }
            if (is_array($value)) {
                foreach ($value as $type => $v) {
                    $type = array_search($type, $asn1->ANYmap, true);
                    if ($type !== false && isset($asn1->stringTypeSize[$type])) {
                        $v = $asn1->convert($v, $type);
                        if ($v !== false) {
                            $value = $v;
                            break;
                        }
                    }
                }
                if (is_array($value)) {
                    $value = array_pop($value); // Always strip data type.
                }
            } elseif (is_object($value) && $value instanceof Element) {
                $callback = function ($x) {
                    return "\x" . bin2hex($x[0]);
                };
                $value = strtoupper(preg_replace_callback('#[^\x20-\x7E]#', $callback, $value->element));
            }
            $output.= $desc . '=' . $value;
            $result[$desc] = isset($result[$desc]) ?
                array_merge((array) $result[$desc], array($value)) :
                $value;
            $start = false;
        }

        return $format == self::DN_OPENSSL ? $result : $output;
    }

    /**
     * Get the Distinguished Name for a certificate/crl issuer
     *
     * @param int $format optional
     * @access public
     * @return mixed
     */
    function getIssuerDN($format = self::DN_ARRAY)
    {
        switch (true) {
            case !isset($this->currentCert) || !is_array($this->currentCert):
                break;
            case isset($this->currentCert['tbsCertificate']):
                return $this->getDN($format, $this->currentCert['tbsCertificate']['issuer']);
            case isset($this->currentCert['tbsCertList']):
                return $this->getDN($format, $this->currentCert['tbsCertList']['issuer']);
        }

        return false;
    }

    /**
     * Get the Distinguished Name for a certificate/csr subject
     * Alias of getDN()
     *
     * @param int $format optional
     * @access public
     * @return mixed
     */
    function getSubjectDN($format = self::DN_ARRAY)
    {
        switch (true) {
            case !empty($this->dn):
                return $this->getDN($format);
            case !isset($this->currentCert) || !is_array($this->currentCert):
                break;
            case isset($this->currentCert['tbsCertificate']):
                return $this->getDN($format, $this->currentCert['tbsCertificate']['subject']);
            case isset($this->currentCert['certificationRequestInfo']):
                return $this->getDN($format, $this->currentCert['certificationRequestInfo']['subject']);
        }

        return false;
    }

    /**
     * Get an individual Distinguished Name property for a certificate/crl issuer
     *
     * @param string $propName
     * @param bool $withType optional
     * @access public
     * @return mixed
     */
    function getIssuerDNProp($propName, $withType = false)
    {
        switch (true) {
            case !isset($this->currentCert) || !is_array($this->currentCert):
                break;
            case isset($this->currentCert['tbsCertificate']):
                return $this->getDNProp($propName, $this->currentCert['tbsCertificate']['issuer'], $withType);
            case isset($this->currentCert['tbsCertList']):
                return $this->getDNProp($propName, $this->currentCert['tbsCertList']['issuer'], $withType);
        }

        return false;
    }

    /**
     * Get an individual Distinguished Name property for a certificate/csr subject
     *
     * @param string $propName
     * @param bool $withType optional
     * @access public
     * @return mixed
     */
    function getSubjectDNProp($propName, $withType = false)
    {
        switch (true) {
            case !empty($this->dn):
                return $this->getDNProp($propName, null, $withType);
            case !isset($this->currentCert) || !is_array($this->currentCert):
                break;
            case isset($this->currentCert['tbsCertificate']):
                return $this->getDNProp($propName, $this->currentCert['tbsCertificate']['subject'], $withType);
            case isset($this->currentCert['certificationRequestInfo']):
                return $this->getDNProp($propName, $this->currentCert['certificationRequestInfo']['subject'], $withType);
        }

        return false;
    }

    /**
     * Get the certificate chain for the current cert
     *
     * @access public
     * @return mixed
     */
    function getChain()
    {
        $chain = array($this->currentCert);

        if (!is_array($this->currentCert) || !isset($this->currentCert['tbsCertificate'])) {
            return false;
        }
        if (empty($this->CAs)) {
            return $chain;
        }
        while (true) {
            $currentCert = $chain[count($chain) - 1];
            for ($i = 0; $i < count($this->CAs); $i++) {
                $ca = $this->CAs[$i];
                if ($currentCert['tbsCertificate']['issuer'] === $ca['tbsCertificate']['subject']) {
                    $authorityKey = $this->getExtension('id-ce-authorityKeyIdentifier', $currentCert);
                    $subjectKeyID = $this->getExtension('id-ce-subjectKeyIdentifier', $ca);
                    switch (true) {
                        case !is_array($authorityKey):
                        case is_array($authorityKey) && isset($authorityKey['keyIdentifier']) && $authorityKey['keyIdentifier'] === $subjectKeyID:
                            if ($currentCert === $ca) {
                                break 3;
                            }
                            $chain[] = $ca;
                            break 2;
                    }
                }
            }
            if ($i == count($this->CAs)) {
                break;
            }
        }
        foreach ($chain as $key => $value) {
            $chain[$key] = new X509();
            $chain[$key]->loadX509($value);
        }
        return $chain;
    }

    /**
     * Set public key
     *
     * Key needs to be a \phpseclib\Crypt\RSA object
     *
     * @param object $key
     * @access public
     * @return bool
     */
    function setPublicKey($key)
    {
        $key->setPublicKey();
        $this->publicKey = $key;
    }

    /**
     * Set private key
     *
     * Key needs to be a \phpseclib\Crypt\RSA object
     *
     * @param object $key
     * @access public
     */
    function setPrivateKey($key)
    {
        $this->privateKey = $key;
    }

    /**
     * Set challenge
     *
     * Used for SPKAC CSR's
     *
     * @param string $challenge
     * @access public
     */
    function setChallenge($challenge)
    {
        $this->challenge = $challenge;
    }

    /**
     * Gets the public key
     *
     * Returns a \phpseclib\Crypt\RSA object or a false.
     *
     * @access public
     * @return mixed
     */
    function getPublicKey()
    {
        if (isset($this->publicKey)) {
            return $this->publicKey;
        }

        if (isset($this->currentCert) && is_array($this->currentCert)) {
            foreach (array('tbsCertificate/subjectPublicKeyInfo', 'certificationRequestInfo/subjectPKInfo') as $path) {
                $keyinfo = $this->_subArray($this->currentCert, $path);
                if (!empty($keyinfo)) {
                    break;
                }
            }
        }
        if (empty($keyinfo)) {
            return false;
        }

        $key = $keyinfo['subjectPublicKey'];

        switch ($keyinfo['algorithm']['algorithm']) {
            case 'rsaEncryption':
                $publicKey = new RSA();
                $publicKey->loadKey($key);
                $publicKey->setPublicKey();
                break;
            default:
                return false;
        }

        return $publicKey;
    }

    /**
     * Load a Certificate Signing Request
     *
     * @param string|array $csr
     * @param int $mode
     * @access public
     * @return mixed
     */
    function loadCSR($csr, $mode = self::FORMAT_AUTO_DETECT)
    {
        if (is_array($csr) && isset($csr['certificationRequestInfo'])) {
            unset($this->currentCert);
            unset($this->currentKeyIdentifier);
            unset($this->signatureSubject);
            $this->dn = $csr['certificationRequestInfo']['subject'];
            if (!isset($this->dn)) {
                return false;
            }

            $this->currentCert = $csr;
            return $csr;
        }

        // see http://tools.ietf.org/html/rfc2986

        $asn1 = new ASN1();

        if ($mode != self::FORMAT_DER) {
            $newcsr = $this->_extractBER($csr);
            if ($mode == self::FORMAT_PEM && $csr == $newcsr) {
                return false;
            }
            $csr = $newcsr;
        }
        $orig = $csr;

        if ($csr === false) {
            $this->currentCert = false;
            return false;
        }

        $asn1->loadOIDs($this->oids);
        $decoded = $asn1->decodeBER($csr);

        if (empty($decoded)) {
            $this->currentCert = false;
            return false;
        }

        $csr = $asn1->asn1map($decoded[0], $this->CertificationRequest);
        if (!isset($csr) || $csr === false) {
            $this->currentCert = false;
            return false;
        }

        $this->_mapInAttributes($csr, 'certificationRequestInfo/attributes', $asn1);
        $this->_mapInDNs($csr, 'certificationRequestInfo/subject/rdnSequence', $asn1);

        $this->dn = $csr['certificationRequestInfo']['subject'];

        $this->signatureSubject = substr($orig, $decoded[0]['content'][0]['start'], $decoded[0]['content'][0]['length']);

        $algorithm = &$csr['certificationRequestInfo']['subjectPKInfo']['algorithm']['algorithm'];
        $key = &$csr['certificationRequestInfo']['subjectPKInfo']['subjectPublicKey'];
        $key = $this->_reformatKey($algorithm, $key);

        switch ($algorithm) {
            case 'rsaEncryption':
                $this->publicKey = new RSA();
                $this->publicKey->loadKey($key);
                $this->publicKey->setPublicKey();
                break;
            default:
                $this->publicKey = null;
        }

        $this->currentKeyIdentifier = null;
        $this->currentCert = $csr;

        return $csr;
    }

    /**
     * Save CSR request
     *
     * @param array $csr
     * @param int $format optional
     * @access public
     * @return string
     */
    function saveCSR($csr, $format = self::FORMAT_PEM)
    {
        if (!is_array($csr) || !isset($csr['certificationRequestInfo'])) {
            return false;
        }

        switch (true) {
            case !($algorithm = $this->_subArray($csr, 'certificationRequestInfo/subjectPKInfo/algorithm/algorithm')):
            case is_object($csr['certificationRequestInfo']['subjectPKInfo']['subjectPublicKey']):
                break;
            default:
                switch ($algorithm) {
                    case 'rsaEncryption':
                        $csr['certificationRequestInfo']['subjectPKInfo']['subjectPublicKey']
                            = base64_encode("\0" . base64_decode(preg_replace('#-.+-|[\r\n]#', '', $csr['certificationRequestInfo']['subjectPKInfo']['subjectPublicKey'])));
                        $csr['certificationRequestInfo']['subjectPKInfo']['algorithm']['parameters'] = null;
                        $csr['signatureAlgorithm']['parameters'] = null;
                        $csr['certificationRequestInfo']['signature']['parameters'] = null;
                }
        }

        $asn1 = new ASN1();

        $asn1->loadOIDs($this->oids);

        $filters = array();
        $filters['certificationRequestInfo']['subject']['rdnSequence']['value']
            = array('type' => ASN1::TYPE_UTF8_STRING);

        $asn1->loadFilters($filters);

        $this->_mapOutDNs($csr, 'certificationRequestInfo/subject/rdnSequence', $asn1);
        $this->_mapOutAttributes($csr, 'certificationRequestInfo/attributes', $asn1);
        $csr = $asn1->encodeDER($csr, $this->CertificationRequest);

        switch ($format) {
            case self::FORMAT_DER:
                return $csr;
            // case self::FORMAT_PEM:
            default:
                return "-----BEGIN CERTIFICATE REQUEST-----\r\n" . chunk_split(base64_encode($csr), 64) . '-----END CERTIFICATE REQUEST-----';
        }
    }

    /**
     * Load a SPKAC CSR
     *
     * SPKAC's are produced by the HTML5 keygen element:
     *
     * https://developer.mozilla.org/en-US/docs/HTML/Element/keygen
     *
     * @param string|array $spkac
     * @access public
     * @return mixed
     */
    function loadSPKAC($spkac)
    {
        if (is_array($spkac) && isset($spkac['publicKeyAndChallenge'])) {
            unset($this->currentCert);
            unset($this->currentKeyIdentifier);
            unset($this->signatureSubject);
            $this->currentCert = $spkac;
            return $spkac;
        }

        // see http://www.w3.org/html/wg/drafts/html/master/forms.html#signedpublickeyandchallenge

        $asn1 = new ASN1();

        // OpenSSL produces SPKAC's that are preceded by the string SPKAC=
        $temp = preg_replace('#(?:SPKAC=)|[ \r\n\\\]#', '', $spkac);
        $temp = preg_match('#^[a-zA-Z\d/+]*={0,2}$#', $temp) ? base64_decode($temp) : false;
        if ($temp != false) {
            $spkac = $temp;
        }
        $orig = $spkac;

        if ($spkac === false) {
            $this->currentCert = false;
            return false;
        }

        $asn1->loadOIDs($this->oids);
        $decoded = $asn1->decodeBER($spkac);

        if (empty($decoded)) {
            $this->currentCert = false;
            return false;
        }

        $spkac = $asn1->asn1map($decoded[0], $this->SignedPublicKeyAndChallenge);

        if (!isset($spkac) || $spkac === false) {
            $this->currentCert = false;
            return false;
        }

        $this->signatureSubject = substr($orig, $decoded[0]['content'][0]['start'], $decoded[0]['content'][0]['length']);

        $algorithm = &$spkac['publicKeyAndChallenge']['spki']['algorithm']['algorithm'];
        $key = &$spkac['publicKeyAndChallenge']['spki']['subjectPublicKey'];
        $key = $this->_reformatKey($algorithm, $key);

        switch ($algorithm) {
            case 'rsaEncryption':
                $this->publicKey = new RSA();
                $this->publicKey->loadKey($key);
                $this->publicKey->setPublicKey();
                break;
            default:
                $this->publicKey = null;
        }

        $this->currentKeyIdentifier = null;
        $this->currentCert = $spkac;

        return $spkac;
    }

    /**
     * Save a SPKAC CSR request
     *
     * @param string|array $spkac
     * @param int $format optional
     * @access public
     * @return string
     */
    function saveSPKAC($spkac, $format = self::FORMAT_PEM)
    {
        if (!is_array($spkac) || !isset($spkac['publicKeyAndChallenge'])) {
            return false;
        }

        $algorithm = $this->_subArray($spkac, 'publicKeyAndChallenge/spki/algorithm/algorithm');
        switch (true) {
            case !$algorithm:
            case is_object($spkac['publicKeyAndChallenge']['spki']['subjectPublicKey']):
                break;
            default:
                switch ($algorithm) {
                    case 'rsaEncryption':
                        $spkac['publicKeyAndChallenge']['spki']['subjectPublicKey']
                            = base64_encode("\0" . base64_decode(preg_replace('#-.+-|[\r\n]#', '', $spkac['publicKeyAndChallenge']['spki']['subjectPublicKey'])));
                }
        }

        $asn1 = new ASN1();

        $asn1->loadOIDs($this->oids);
        $spkac = $asn1->encodeDER($spkac, $this->SignedPublicKeyAndChallenge);

        switch ($format) {
            case self::FORMAT_DER:
                return $spkac;
            // case self::FORMAT_PEM:
            default:
                // OpenSSL's implementation of SPKAC requires the SPKAC be preceded by SPKAC= and since there are pretty much
                // no other SPKAC decoders phpseclib will use that same format
                return 'SPKAC=' . base64_encode($spkac);
        }
    }

    /**
     * Load a Certificate Revocation List
     *
     * @param string $crl
     * @param int $mode
     * @access public
     * @return mixed
     */
    function loadCRL($crl, $mode = self::FORMAT_AUTO_DETECT)
    {
        if (is_array($crl) && isset($crl['tbsCertList'])) {
            $this->currentCert = $crl;
            unset($this->signatureSubject);
            return $crl;
        }

        $asn1 = new ASN1();

        if ($mode != self::FORMAT_DER) {
            $newcrl = $this->_extractBER($crl);
            if ($mode == self::FORMAT_PEM && $crl == $newcrl) {
                return false;
            }
            $crl = $newcrl;
        }
        $orig = $crl;

        if ($crl === false) {
            $this->currentCert = false;
            return false;
        }

        $asn1->loadOIDs($this->oids);
        $decoded = $asn1->decodeBER($crl);

        if (empty($decoded)) {
            $this->currentCert = false;
            return false;
        }

        $crl = $asn1->asn1map($decoded[0], $this->CertificateList);
        if (!isset($crl) || $crl === false) {
            $this->currentCert = false;
            return false;
        }

        $this->signatureSubject = substr($orig, $decoded[0]['content'][0]['start'], $decoded[0]['content'][0]['length']);

        $this->_mapInDNs($crl, 'tbsCertList/issuer/rdnSequence', $asn1);
        if ($this->_isSubArrayValid($crl, 'tbsCertList/crlExtensions')) {
            $this->_mapInExtensions($crl, 'tbsCertList/crlExtensions', $asn1);
        }
        if ($this->_isSubArrayValid($crl, 'tbsCertList/revokedCertificates')) {
            $rclist_ref = &$this->_subArrayUnchecked($crl, 'tbsCertList/revokedCertificates');
            if ($rclist_ref) {
                $rclist = $crl['tbsCertList']['revokedCertificates'];
                foreach ($rclist as $i => $extension) {
                    if ($this->_isSubArrayValid($rclist, "$i/crlEntryExtensions", $asn1)) {
                        $this->_mapInExtensions($rclist_ref, "$i/crlEntryExtensions", $asn1);
                    }
                }
            }
        }

        $this->currentKeyIdentifier = null;
        $this->currentCert = $crl;

        return $crl;
    }

    /**
     * Save Certificate Revocation List.
     *
     * @param array $crl
     * @param int $format optional
     * @access public
     * @return string
     */
    function saveCRL($crl, $format = self::FORMAT_PEM)
    {
        if (!is_array($crl) || !isset($crl['tbsCertList'])) {
            return false;
        }

        $asn1 = new ASN1();

        $asn1->loadOIDs($this->oids);

        $filters = array();
        $filters['tbsCertList']['issuer']['rdnSequence']['value']
            = array('type' => ASN1::TYPE_UTF8_STRING);
        $filters['tbsCertList']['signature']['parameters']
            = array('type' => ASN1::TYPE_UTF8_STRING);
        $filters['signatureAlgorithm']['parameters']
            = array('type' => ASN1::TYPE_UTF8_STRING);

        if (empty($crl['tbsCertList']['signature']['parameters'])) {
            $filters['tbsCertList']['signature']['parameters']
                = array('type' => ASN1::TYPE_NULL);
        }

        if (empty($crl['signatureAlgorithm']['parameters'])) {
            $filters['signatureAlgorithm']['parameters']
                = array('type' => ASN1::TYPE_NULL);
        }

        $asn1->loadFilters($filters);

        $this->_mapOutDNs($crl, 'tbsCertList/issuer/rdnSequence', $asn1);
        $this->_mapOutExtensions($crl, 'tbsCertList/crlExtensions', $asn1);
        $rclist = &$this->_subArray($crl, 'tbsCertList/revokedCertificates');
        if (is_array($rclist)) {
            foreach ($rclist as $i => $extension) {
                $this->_mapOutExtensions($rclist, "$i/crlEntryExtensions", $asn1);
            }
        }

        $crl = $asn1->encodeDER($crl, $this->CertificateList);

        switch ($format) {
            case self::FORMAT_DER:
                return $crl;
            // case self::FORMAT_PEM:
            default:
                return "-----BEGIN X509 CRL-----\r\n" . chunk_split(base64_encode($crl), 64) . '-----END X509 CRL-----';
        }
    }

    /**
     * Helper function to build a time field according to RFC 3280 section
     *  - 4.1.2.5 Validity
     *  - 5.1.2.4 This Update
     *  - 5.1.2.5 Next Update
     *  - 5.1.2.6 Revoked Certificates
     * by choosing utcTime iff year of date given is before 2050 and generalTime else.
     *
     * @param string $date in format date('D, d M Y H:i:s O')
     * @access private
     * @return array
     */
    function _timeField($date)
    {
        if ($date instanceof Element) {
            return $date;
        }
        $dateObj = new DateTime($date, new DateTimeZone('GMT'));
        $year = $dateObj->format('Y'); // the same way ASN1.php parses this
        if ($year < 2050) {
            return array('utcTime' => $date);
        } else {
            return array('generalTime' => $date);
        }
    }

    /**
     * Sign an X.509 certificate
     *
     * $issuer's private key needs to be loaded.
     * $subject can be either an existing X.509 cert (if you want to resign it),
     * a CSR or something with the DN and public key explicitly set.
     *
     * @param \phpseclib\File\X509 $issuer
     * @param \phpseclib\File\X509 $subject
     * @param string $signatureAlgorithm optional
     * @access public
     * @return mixed
     */
    function sign($issuer, $subject, $signatureAlgorithm = 'sha1WithRSAEncryption')
    {
        if (!is_object($issuer->privateKey) || empty($issuer->dn)) {
            return false;
        }

        if (isset($subject->publicKey) && !($subjectPublicKey = $subject->_formatSubjectPublicKey())) {
            return false;
        }

        $currentCert = isset($this->currentCert) ? $this->currentCert : null;
        $signatureSubject = isset($this->signatureSubject) ? $this->signatureSubject: null;

        if (isset($subject->currentCert) && is_array($subject->currentCert) && isset($subject->currentCert['tbsCertificate'])) {
            $this->currentCert = $subject->currentCert;
            $this->currentCert['tbsCertificate']['signature']['algorithm'] = $signatureAlgorithm;
            $this->currentCert['signatureAlgorithm']['algorithm'] = $signatureAlgorithm;

            if (!empty($this->startDate)) {
                $this->currentCert['tbsCertificate']['validity']['notBefore'] = $this->_timeField($this->startDate);
            }
            if (!empty($this->endDate)) {
                $this->currentCert['tbsCertificate']['validity']['notAfter'] = $this->_timeField($this->endDate);
            }
            if (!empty($this->serialNumber)) {
                $this->currentCert['tbsCertificate']['serialNumber'] = $this->serialNumber;
            }
            if (!empty($subject->dn)) {
                $this->currentCert['tbsCertificate']['subject'] = $subject->dn;
            }
            if (!empty($subject->publicKey)) {
                $this->currentCert['tbsCertificate']['subjectPublicKeyInfo'] = $subjectPublicKey;
            }
            $this->removeExtension('id-ce-authorityKeyIdentifier');
            if (isset($subject->domains)) {
                $this->removeExtension('id-ce-subjectAltName');
            }
        } elseif (isset($subject->currentCert) && is_array($subject->currentCert) && isset($subject->currentCert['tbsCertList'])) {
            return false;
        } else {
            if (!isset($subject->publicKey)) {
                return false;
            }

            $startDate = new DateTime('now', new DateTimeZone(@date_default_timezone_get()));
            $startDate = !empty($this->startDate) ? $this->startDate : $startDate->format('D, d M Y H:i:s O');

            $endDate = new DateTime('+1 year', new DateTimeZone(@date_default_timezone_get()));
            $endDate = !empty($this->endDate) ? $this->endDate : $endDate->format('D, d M Y H:i:s O');

            /* "The serial number MUST be a positive integer"
               "Conforming CAs MUST NOT use serialNumber values longer than 20 octets."
                -- https://tools.ietf.org/html/rfc5280#section-4.1.2.2

               for the integer to be positive the leading bit needs to be 0 hence the
               application of a bitmap
            */
            $serialNumber = !empty($this->serialNumber) ?
                $this->serialNumber :
                new BigInteger(Random::string(20) & ("\x7F" . str_repeat("\xFF", 19)), 256);

            $this->currentCert = array(
                'tbsCertificate' =>
                    array(
                        'version' => 'v3',
                        'serialNumber' => $serialNumber, // $this->setSerialNumber()
                        'signature' => array('algorithm' => $signatureAlgorithm),
                        'issuer' => false, // this is going to be overwritten later
                        'validity' => array(
                            'notBefore' => $this->_timeField($startDate), // $this->setStartDate()
                            'notAfter' => $this->_timeField($endDate)   // $this->setEndDate()
                        ),
                        'subject' => $subject->dn,
                        'subjectPublicKeyInfo' => $subjectPublicKey
                    ),
                    'signatureAlgorithm' => array('algorithm' => $signatureAlgorithm),
                    'signature'          => false // this is going to be overwritten later
            );

            // Copy extensions from CSR.
            $csrexts = $subject->getAttribute('pkcs-9-at-extensionRequest', 0);

            if (!empty($csrexts)) {
                $this->currentCert['tbsCertificate']['extensions'] = $csrexts;
            }
        }

        $this->currentCert['tbsCertificate']['issuer'] = $issuer->dn;

        if (isset($issuer->currentKeyIdentifier)) {
            $this->setExtension('id-ce-authorityKeyIdentifier', array(
                    //'authorityCertIssuer' => array(
                    //    array(
                    //        'directoryName' => $issuer->dn
                    //    )
                    //),
                    'keyIdentifier' => $issuer->currentKeyIdentifier
                ));
            //$extensions = &$this->currentCert['tbsCertificate']['extensions'];
            //if (isset($issuer->serialNumber)) {
            //    $extensions[count($extensions) - 1]['authorityCertSerialNumber'] = $issuer->serialNumber;
            //}
            //unset($extensions);
        }

        if (isset($subject->currentKeyIdentifier)) {
            $this->setExtension('id-ce-subjectKeyIdentifier', $subject->currentKeyIdentifier);
        }

        $altName = array();

        if (isset($subject->domains) && count($subject->domains)) {
            $altName = array_map(array('\phpseclib\File\X509', '_dnsName'), $subject->domains);
        }

        if (isset($subject->ipAddresses) && count($subject->ipAddresses)) {
            // should an IP address appear as the CN if no domain name is specified? idk
            //$ips = count($subject->domains) ? $subject->ipAddresses : array_slice($subject->ipAddresses, 1);
            $ipAddresses = array();
            foreach ($subject->ipAddresses as $ipAddress) {
                $encoded = $subject->_ipAddress($ipAddress);
                if ($encoded !== false) {
                    $ipAddresses[] = $encoded;
                }
            }
            if (count($ipAddresses)) {
                $altName = array_merge($altName, $ipAddresses);
            }
        }

        if (!empty($altName)) {
            $this->setExtension('id-ce-subjectAltName', $altName);
        }

        if ($this->caFlag) {
            $keyUsage = $this->getExtension('id-ce-keyUsage');
            if (!$keyUsage) {
                $keyUsage = array();
            }

            $this->setExtension(
                'id-ce-keyUsage',
                array_values(array_unique(array_merge($keyUsage, array('cRLSign', 'keyCertSign'))))
            );

            $basicConstraints = $this->getExtension('id-ce-basicConstraints');
            if (!$basicConstraints) {
                $basicConstraints = array();
            }

            $this->setExtension(
                'id-ce-basicConstraints',
                array_unique(array_merge(array('cA' => true), $basicConstraints)),
                true
            );

            if (!isset($subject->currentKeyIdentifier)) {
                $this->setExtension('id-ce-subjectKeyIdentifier', base64_encode($this->computeKeyIdentifier($this->currentCert)), false, false);
            }
        }

        // resync $this->signatureSubject
        // save $tbsCertificate in case there are any \phpseclib\File\ASN1\Element objects in it
        $tbsCertificate = $this->currentCert['tbsCertificate'];
        $this->loadX509($this->saveX509($this->currentCert));

        $result = $this->_sign($issuer->privateKey, $signatureAlgorithm);
        $result['tbsCertificate'] = $tbsCertificate;

        $this->currentCert = $currentCert;
        $this->signatureSubject = $signatureSubject;

        return $result;
    }

    /**
     * Sign a CSR
     *
     * @access public
     * @return mixed
     */
    function signCSR($signatureAlgorithm = 'sha1WithRSAEncryption')
    {
        if (!is_object($this->privateKey) || empty($this->dn)) {
            return false;
        }

        $origPublicKey = $this->publicKey;
        $class = get_class($this->privateKey);
        $this->publicKey = new $class();
        $this->publicKey->loadKey($this->privateKey->getPublicKey());
        $this->publicKey->setPublicKey();
        if (!($publicKey = $this->_formatSubjectPublicKey())) {
            return false;
        }
        $this->publicKey = $origPublicKey;

        $currentCert = isset($this->currentCert) ? $this->currentCert : null;
        $signatureSubject = isset($this->signatureSubject) ? $this->signatureSubject: null;

        if (isset($this->currentCert) && is_array($this->currentCert) && isset($this->currentCert['certificationRequestInfo'])) {
            $this->currentCert['signatureAlgorithm']['algorithm'] = $signatureAlgorithm;
            if (!empty($this->dn)) {
                $this->currentCert['certificationRequestInfo']['subject'] = $this->dn;
            }
            $this->currentCert['certificationRequestInfo']['subjectPKInfo'] = $publicKey;
        } else {
            $this->currentCert = array(
                'certificationRequestInfo' =>
                    array(
                        'version' => 'v1',
                        'subject' => $this->dn,
                        'subjectPKInfo' => $publicKey
                    ),
                    'signatureAlgorithm' => array('algorithm' => $signatureAlgorithm),
                    'signature'          => false // this is going to be overwritten later
            );
        }

        // resync $this->signatureSubject
        // save $certificationRequestInfo in case there are any \phpseclib\File\ASN1\Element objects in it
        $certificationRequestInfo = $this->currentCert['certificationRequestInfo'];
        $this->loadCSR($this->saveCSR($this->currentCert));

        $result = $this->_sign($this->privateKey, $signatureAlgorithm);
        $result['certificationRequestInfo'] = $certificationRequestInfo;

        $this->currentCert = $currentCert;
        $this->signatureSubject = $signatureSubject;

        return $result;
    }

    /**
     * Sign a SPKAC
     *
     * @access public
     * @return mixed
     */
    function signSPKAC($signatureAlgorithm = 'sha1WithRSAEncryption')
    {
        if (!is_object($this->privateKey)) {
            return false;
        }

        $origPublicKey = $this->publicKey;
        $class = get_class($this->privateKey);
        $this->publicKey = new $class();
        $this->publicKey->loadKey($this->privateKey->getPublicKey());
        $this->publicKey->setPublicKey();
        $publicKey = $this->_formatSubjectPublicKey();
        if (!$publicKey) {
            return false;
        }
        $this->publicKey = $origPublicKey;

        $currentCert = isset($this->currentCert) ? $this->currentCert : null;
        $signatureSubject = isset($this->signatureSubject) ? $this->signatureSubject: null;

        // re-signing a SPKAC seems silly but since everything else supports re-signing why not?
        if (isset($this->currentCert) && is_array($this->currentCert) && isset($this->currentCert['publicKeyAndChallenge'])) {
            $this->currentCert['signatureAlgorithm']['algorithm'] = $signatureAlgorithm;
            $this->currentCert['publicKeyAndChallenge']['spki'] = $publicKey;
            if (!empty($this->challenge)) {
                // the bitwise AND ensures that the output is a valid IA5String
                $this->currentCert['publicKeyAndChallenge']['challenge'] = $this->challenge & str_repeat("\x7F", strlen($this->challenge));
            }
        } else {
            $this->currentCert = array(
                'publicKeyAndChallenge' =>
                    array(
                        'spki' => $publicKey,
                        // quoting <https://developer.mozilla.org/en-US/docs/Web/HTML/Element/keygen>,
                        // "A challenge string that is submitted along with the public key. Defaults to an empty string if not specified."
                        // both Firefox and OpenSSL ("openssl spkac -key private.key") behave this way
                        // we could alternatively do this instead if we ignored the specs:
                        // Random::string(8) & str_repeat("\x7F", 8)
                        'challenge' => !empty($this->challenge) ? $this->challenge : ''
                    ),
                    'signatureAlgorithm' => array('algorithm' => $signatureAlgorithm),
                    'signature'          => false // this is going to be overwritten later
            );
        }

        // resync $this->signatureSubject
        // save $publicKeyAndChallenge in case there are any \phpseclib\File\ASN1\Element objects in it
        $publicKeyAndChallenge = $this->currentCert['publicKeyAndChallenge'];
        $this->loadSPKAC($this->saveSPKAC($this->currentCert));

        $result = $this->_sign($this->privateKey, $signatureAlgorithm);
        $result['publicKeyAndChallenge'] = $publicKeyAndChallenge;

        $this->currentCert = $currentCert;
        $this->signatureSubject = $signatureSubject;

        return $result;
    }

    /**
     * Sign a CRL
     *
     * $issuer's private key needs to be loaded.
     *
     * @param \phpseclib\File\X509 $issuer
     * @param \phpseclib\File\X509 $crl
     * @param string $signatureAlgorithm optional
     * @access public
     * @return mixed
     */
    function signCRL($issuer, $crl, $signatureAlgorithm = 'sha1WithRSAEncryption')
    {
        if (!is_object($issuer->privateKey) || empty($issuer->dn)) {
            return false;
        }

        $currentCert = isset($this->currentCert) ? $this->currentCert : null;
        $signatureSubject = isset($this->signatureSubject) ? $this->signatureSubject : null;

        $thisUpdate = new DateTime('now', new DateTimeZone(@date_default_timezone_get()));
        $thisUpdate = !empty($this->startDate) ? $this->startDate : $thisUpdate->format('D, d M Y H:i:s O');

        if (isset($crl->currentCert) && is_array($crl->currentCert) && isset($crl->currentCert['tbsCertList'])) {
            $this->currentCert = $crl->currentCert;
            $this->currentCert['tbsCertList']['signature']['algorithm'] = $signatureAlgorithm;
            $this->currentCert['signatureAlgorithm']['algorithm'] = $signatureAlgorithm;
        } else {
            $this->currentCert = array(
                'tbsCertList' =>
                    array(
                        'version' => 'v2',
                        'signature' => array('algorithm' => $signatureAlgorithm),
                        'issuer' => false, // this is going to be overwritten later
                        'thisUpdate' => $this->_timeField($thisUpdate) // $this->setStartDate()
                    ),
                    'signatureAlgorithm' => array('algorithm' => $signatureAlgorithm),
                    'signature'          => false // this is going to be overwritten later
            );
        }

        $tbsCertList = &$this->currentCert['tbsCertList'];
        $tbsCertList['issuer'] = $issuer->dn;
        $tbsCertList['thisUpdate'] = $this->_timeField($thisUpdate);

        if (!empty($this->endDate)) {
            $tbsCertList['nextUpdate'] = $this->_timeField($this->endDate); // $this->setEndDate()
        } else {
            unset($tbsCertList['nextUpdate']);
        }

        if (!empty($this->serialNumber)) {
            $crlNumber = $this->serialNumber;
        } else {
            $crlNumber = $this->getExtension('id-ce-cRLNumber');
            // "The CRL number is a non-critical CRL extension that conveys a
            //  monotonically increasing sequence number for a given CRL scope and
            //  CRL issuer.  This extension allows users to easily determine when a
            //  particular CRL supersedes another CRL."
            // -- https://tools.ietf.org/html/rfc5280#section-5.2.3
            $crlNumber = $crlNumber !== false ? $crlNumber->add(new BigInteger(1)) : null;
        }

        $this->removeExtension('id-ce-authorityKeyIdentifier');
        $this->removeExtension('id-ce-issuerAltName');

        // Be sure version >= v2 if some extension found.
        $version = isset($tbsCertList['version']) ? $tbsCertList['version'] : 0;
        if (!$version) {
            if (!empty($tbsCertList['crlExtensions'])) {
                $version = 1; // v2.
            } elseif (!empty($tbsCertList['revokedCertificates'])) {
                foreach ($tbsCertList['revokedCertificates'] as $cert) {
                    if (!empty($cert['crlEntryExtensions'])) {
                        $version = 1; // v2.
                    }
                }
            }

            if ($version) {
                $tbsCertList['version'] = $version;
            }
        }

        // Store additional extensions.
        if (!empty($tbsCertList['version'])) { // At least v2.
            if (!empty($crlNumber)) {
                $this->setExtension('id-ce-cRLNumber', $crlNumber);
            }

            if (isset($issuer->currentKeyIdentifier)) {
                $this->setExtension('id-ce-authorityKeyIdentifier', array(
                        //'authorityCertIssuer' => array(
                        //    array(
                        //        'directoryName' => $issuer->dn
                        //    )
                        //),
                        'keyIdentifier' => $issuer->currentKeyIdentifier
                    ));
                //$extensions = &$tbsCertList['crlExtensions'];
                //if (isset($issuer->serialNumber)) {
                //    $extensions[count($extensions) - 1]['authorityCertSerialNumber'] = $issuer->serialNumber;
                //}
                //unset($extensions);
            }

            $issuerAltName = $this->getExtension('id-ce-subjectAltName', $issuer->currentCert);

            if ($issuerAltName !== false) {
                $this->setExtension('id-ce-issuerAltName', $issuerAltName);
            }
        }

        if (empty($tbsCertList['revokedCertificates'])) {
            unset($tbsCertList['revokedCertificates']);
        }

        unset($tbsCertList);

        // resync $this->signatureSubject
        // save $tbsCertList in case there are any \phpseclib\File\ASN1\Element objects in it
        $tbsCertList = $this->currentCert['tbsCertList'];
        $this->loadCRL($this->saveCRL($this->currentCert));

        $result = $this->_sign($issuer->privateKey, $signatureAlgorithm);
        $result['tbsCertList'] = $tbsCertList;

        $this->currentCert = $currentCert;
        $this->signatureSubject = $signatureSubject;

        return $result;
    }

    /**
     * X.509 certificate signing helper function.
     *
     * @param \phpseclib\File\X509 $key
     * @param string $signatureAlgorithm
     * @access public
     * @return mixed
     */
    function _sign($key, $signatureAlgorithm)
    {
        if ($key instanceof RSA) {
            switch ($signatureAlgorithm) {
                case 'md2WithRSAEncryption':
                case 'md5WithRSAEncryption':
                case 'sha1WithRSAEncryption':
                case 'sha224WithRSAEncryption':
                case 'sha256WithRSAEncryption':
                case 'sha384WithRSAEncryption':
                case 'sha512WithRSAEncryption':
                    $key->setHash(preg_replace('#WithRSAEncryption$#', '', $signatureAlgorithm));
                    $key->setSignatureMode(RSA::SIGNATURE_PKCS1);

                    $this->currentCert['signature'] = base64_encode("\0" . $key->sign($this->signatureSubject));
                    return $this->currentCert;
            }
        }

        return false;
    }

    /**
     * Set certificate start date
     *
     * @param string $date
     * @access public
     */
    function setStartDate($date)
    {
        if (!is_object($date) || !is_a($date, 'DateTime')) {
            $date = new DateTime($date, new DateTimeZone(@date_default_timezone_get()));
        }

        $this->startDate = $date->format('D, d M Y H:i:s O');
    }

    /**
     * Set certificate end date
     *
     * @param string $date
     * @access public
     */
    function setEndDate($date)
    {
        /*
          To indicate that a certificate has no well-defined expiration date,
          the notAfter SHOULD be assigned the GeneralizedTime value of
          99991231235959Z.

          -- http://tools.ietf.org/html/rfc5280#section-4.1.2.5
        */
        if (strtolower($date) == 'lifetime') {
            $temp = '99991231235959Z';
            $asn1 = new ASN1();
            $temp = chr(ASN1::TYPE_GENERALIZED_TIME) . $asn1->_encodeLength(strlen($temp)) . $temp;
            $this->endDate = new Element($temp);
        } else {
            if (!is_object($date) || !is_a($date, 'DateTime')) {
                $date = new DateTime($date, new DateTimeZone(@date_default_timezone_get()));
            }

            $this->endDate = $date->format('D, d M Y H:i:s O');
        }
    }

    /**
     * Set Serial Number
     *
     * @param string $serial
     * @param int $base optional
     * @access public
     */
    function setSerialNumber($serial, $base = -256)
    {
        $this->serialNumber = new BigInteger($serial, $base);
    }

    /**
     * Turns the certificate into a certificate authority
     *
     * @access public
     */
    function makeCA()
    {
        $this->caFlag = true;
    }

    /**
     * Check for validity of subarray
     *
     * This is intended for use in conjunction with _subArrayUnchecked(),
     * implementing the checks included in _subArray() but without copying
     * a potentially large array by passing its reference by-value to is_array().
     *
     * @param array $root
     * @param string $path
     * @return boolean
     * @access private
     */
    function _isSubArrayValid($root, $path)
    {
        if (!is_array($root)) {
            return false;
        }

        foreach (explode('/', $path) as $i) {
            if (!is_array($root)) {
                return false;
            }

            if (!isset($root[$i])) {
                return true;
            }

            $root = $root[$i];
        }

        return true;
    }

    /**
     * Get a reference to a subarray
     *
     * This variant of _subArray() does no is_array() checking,
     * so $root should be checked with _isSubArrayValid() first.
     *
     * This is here for performance reasons:
     * Passing a reference (i.e. $root) by-value (i.e. to is_array())
     * creates a copy. If $root is an especially large array, this is expensive.
     *
     * @param array $root
     * @param string $path  absolute path with / as component separator
     * @param bool $create optional
     * @access private
     * @return array|false
     */
    function &_subArrayUnchecked(&$root, $path, $create = false)
    {
        $false = false;

        foreach (explode('/', $path) as $i) {
            if (!isset($root[$i])) {
                if (!$create) {
                    return $false;
                }

                $root[$i] = array();
            }

            $root = &$root[$i];
        }

        return $root;
    }

    /**
     * Get a reference to a subarray
     *
     * @param array $root
     * @param string $path  absolute path with / as component separator
     * @param bool $create optional
     * @access private
     * @return array|false
     */
    function &_subArray(&$root, $path, $create = false)
    {
        $false = false;

        if (!is_array($root)) {
            return $false;
        }

        foreach (explode('/', $path) as $i) {
            if (!is_array($root)) {
                return $false;
            }

            if (!isset($root[$i])) {
                if (!$create) {
                    return $false;
                }

                $root[$i] = array();
            }

            $root = &$root[$i];
        }

        return $root;
    }

    /**
     * Get a reference to an extension subarray
     *
     * @param array $root
     * @param string $path optional absolute path with / as component separator
     * @param bool $create optional
     * @access private
     * @return array|false
     */
    function &_extensions(&$root, $path = null, $create = false)
    {
        if (!isset($root)) {
            $root = $this->currentCert;
        }

        switch (true) {
            case !empty($path):
            case !is_array($root):
                break;
            case isset($root['tbsCertificate']):
                $path = 'tbsCertificate/extensions';
                break;
            case isset($root['tbsCertList']):
                $path = 'tbsCertList/crlExtensions';
                break;
            case isset($root['certificationRequestInfo']):
                $pth = 'certificationRequestInfo/attributes';
                $attributes = &$this->_subArray($root, $pth, $create);

                if (is_array($attributes)) {
                    foreach ($attributes as $key => $value) {
                        if ($value['type'] == 'pkcs-9-at-extensionRequest') {
                            $path = "$pth/$key/value/0";
                            break 2;
                        }
                    }
                    if ($create) {
                        $key = count($attributes);
                        $attributes[] = array('type' => 'pkcs-9-at-extensionRequest', 'value' => array());
                        $path = "$pth/$key/value/0";
                    }
                }
                break;
        }

        $extensions = &$this->_subArray($root, $path, $create);

        if (!is_array($extensions)) {
            $false = false;
            return $false;
        }

        return $extensions;
    }

    /**
     * Remove an Extension
     *
     * @param string $id
     * @param string $path optional
     * @access private
     * @return bool
     */
    function _removeExtension($id, $path = null)
    {
        $extensions = &$this->_extensions($this->currentCert, $path);

        if (!is_array($extensions)) {
            return false;
        }

        $result = false;
        foreach ($extensions as $key => $value) {
            if ($value['extnId'] == $id) {
                unset($extensions[$key]);
                $result = true;
            }
        }

        $extensions = array_values($extensions);
        // fix for https://bugs.php.net/75433 affecting PHP 7.2
        if (!isset($extensions[0])) {
            $extensions = array_splice($extensions, 0, 0);
        }
        return $result;
    }

    /**
     * Get an Extension
     *
     * Returns the extension if it exists and false if not
     *
     * @param string $id
     * @param array $cert optional
     * @param string $path optional
     * @access private
     * @return mixed
     */
    function _getExtension($id, $cert = null, $path = null)
    {
        $extensions = $this->_extensions($cert, $path);

        if (!is_array($extensions)) {
            return false;
        }

        foreach ($extensions as $key => $value) {
            if ($value['extnId'] == $id) {
                return $value['extnValue'];
            }
        }

        return false;
    }

    /**
     * Returns a list of all extensions in use
     *
     * @param array $cert optional
     * @param string $path optional
     * @access private
     * @return array
     */
    function _getExtensions($cert = null, $path = null)
    {
        $exts = $this->_extensions($cert, $path);
        $extensions = array();

        if (is_array($exts)) {
            foreach ($exts as $extension) {
                $extensions[] = $extension['extnId'];
            }
        }

        return $extensions;
    }

    /**
     * Set an Extension
     *
     * @param string $id
     * @param mixed $value
     * @param bool $critical optional
     * @param bool $replace optional
     * @param string $path optional
     * @access private
     * @return bool
     */
    function _setExtension($id, $value, $critical = false, $replace = true, $path = null)
    {
        $extensions = &$this->_extensions($this->currentCert, $path, true);

        if (!is_array($extensions)) {
            return false;
        }

        $newext = array('extnId'  => $id, 'critical' => $critical, 'extnValue' => $value);

        foreach ($extensions as $key => $value) {
            if ($value['extnId'] == $id) {
                if (!$replace) {
                    return false;
                }

                $extensions[$key] = $newext;
                return true;
            }
        }

        $extensions[] = $newext;
        return true;
    }

    /**
     * Remove a certificate, CSR or CRL Extension
     *
     * @param string $id
     * @access public
     * @return bool
     */
    function removeExtension($id)
    {
        return $this->_removeExtension($id);
    }

    /**
     * Get a certificate, CSR or CRL Extension
     *
     * Returns the extension if it exists and false if not
     *
     * @param string $id
     * @param array $cert optional
     * @access public
     * @return mixed
     */
    function getExtension($id, $cert = null)
    {
        return $this->_getExtension($id, $cert);
    }

    /**
     * Returns a list of all extensions in use in certificate, CSR or CRL
     *
     * @param array $cert optional
     * @access public
     * @return array
     */
    function getExtensions($cert = null)
    {
        return $this->_getExtensions($cert);
    }

    /**
     * Set a certificate, CSR or CRL Extension
     *
     * @param string $id
     * @param mixed $value
     * @param bool $critical optional
     * @param bool $replace optional
     * @access public
     * @return bool
     */
    function setExtension($id, $value, $critical = false, $replace = true)
    {
        return $this->_setExtension($id, $value, $critical, $replace);
    }

    /**
     * Remove a CSR attribute.
     *
     * @param string $id
     * @param int $disposition optional
     * @access public
     * @return bool
     */
    function removeAttribute($id, $disposition = self::ATTR_ALL)
    {
        $attributes = &$this->_subArray($this->currentCert, 'certificationRequestInfo/attributes');

        if (!is_array($attributes)) {
            return false;
        }

        $result = false;
        foreach ($attributes as $key => $attribute) {
            if ($attribute['type'] == $id) {
                $n = count($attribute['value']);
                switch (true) {
                    case $disposition == self::ATTR_APPEND:
                    case $disposition == self::ATTR_REPLACE:
                        return false;
                    case $disposition >= $n:
                        $disposition -= $n;
                        break;
                    case $disposition == self::ATTR_ALL:
                    case $n == 1:
                        unset($attributes[$key]);
                        $result = true;
                        break;
                    default:
                        unset($attributes[$key]['value'][$disposition]);
                        $attributes[$key]['value'] = array_values($attributes[$key]['value']);
                        $result = true;
                        break;
                }
                if ($result && $disposition != self::ATTR_ALL) {
                    break;
                }
            }
        }

        $attributes = array_values($attributes);
        return $result;
    }

    /**
     * Get a CSR attribute
     *
     * Returns the attribute if it exists and false if not
     *
     * @param string $id
     * @param int $disposition optional
     * @param array $csr optional
     * @access public
     * @return mixed
     */
    function getAttribute($id, $disposition = self::ATTR_ALL, $csr = null)
    {
        if (empty($csr)) {
            $csr = $this->currentCert;
        }

        $attributes = $this->_subArray($csr, 'certificationRequestInfo/attributes');

        if (!is_array($attributes)) {
            return false;
        }

        foreach ($attributes as $key => $attribute) {
            if ($attribute['type'] == $id) {
                $n = count($attribute['value']);
                switch (true) {
                    case $disposition == self::ATTR_APPEND:
                    case $disposition == self::ATTR_REPLACE:
                        return false;
                    case $disposition == self::ATTR_ALL:
                        return $attribute['value'];
                    case $disposition >= $n:
                        $disposition -= $n;
                        break;
                    default:
                        return $attribute['value'][$disposition];
                }
            }
        }

        return false;
    }

    /**
     * Returns a list of all CSR attributes in use
     *
     * @param array $csr optional
     * @access public
     * @return array
     */
    function getAttributes($csr = null)
    {
        if (empty($csr)) {
            $csr = $this->currentCert;
        }

        $attributes = $this->_subArray($csr, 'certificationRequestInfo/attributes');
        $attrs = array();

        if (is_array($attributes)) {
            foreach ($attributes as $attribute) {
                $attrs[] = $attribute['type'];
            }
        }

        return $attrs;
    }

    /**
     * Set a CSR attribute
     *
     * @param string $id
     * @param mixed $value
     * @param bool $disposition optional
     * @access public
     * @return bool
     */
    function setAttribute($id, $value, $disposition = self::ATTR_ALL)
    {
        $attributes = &$this->_subArray($this->currentCert, 'certificationRequestInfo/attributes', true);

        if (!is_array($attributes)) {
            return false;
        }

        switch ($disposition) {
            case self::ATTR_REPLACE:
                $disposition = self::ATTR_APPEND;
            case self::ATTR_ALL:
                $this->removeAttribute($id);
                break;
        }

        foreach ($attributes as $key => $attribute) {
            if ($attribute['type'] == $id) {
                $n = count($attribute['value']);
                switch (true) {
                    case $disposition == self::ATTR_APPEND:
                        $last = $key;
                        break;
                    case $disposition >= $n:
                        $disposition -= $n;
                        break;
                    default:
                        $attributes[$key]['value'][$disposition] = $value;
                        return true;
                }
            }
        }

        switch (true) {
            case $disposition >= 0:
                return false;
            case isset($last):
                $attributes[$last]['value'][] = $value;
                break;
            default:
                $attributes[] = array('type' => $id, 'value' => $disposition == self::ATTR_ALL ? $value: array($value));
                break;
        }

        return true;
    }

    /**
     * Sets the subject key identifier
     *
     * This is used by the id-ce-authorityKeyIdentifier and the id-ce-subjectKeyIdentifier extensions.
     *
     * @param string $value
     * @access public
     */
    function setKeyIdentifier($value)
    {
        if (empty($value)) {
            unset($this->currentKeyIdentifier);
        } else {
            $this->currentKeyIdentifier = base64_encode($value);
        }
    }

    /**
     * Compute a public key identifier.
     *
     * Although key identifiers may be set to any unique value, this function
     * computes key identifiers from public key according to the two
     * recommended methods (4.2.1.2 RFC 3280).
     * Highly polymorphic: try to accept all possible forms of key:
     * - Key object
     * - \phpseclib\File\X509 object with public or private key defined
     * - Certificate or CSR array
     * - \phpseclib\File\ASN1\Element object
     * - PEM or DER string
     *
     * @param mixed $key optional
     * @param int $method optional
     * @access public
     * @return string binary key identifier
     */
    function computeKeyIdentifier($key = null, $method = 1)
    {
        if (is_null($key)) {
            $key = $this;
        }

        switch (true) {
            case is_string($key):
                break;
            case is_array($key) && isset($key['tbsCertificate']['subjectPublicKeyInfo']['subjectPublicKey']):
                return $this->computeKeyIdentifier($key['tbsCertificate']['subjectPublicKeyInfo']['subjectPublicKey'], $method);
            case is_array($key) && isset($key['certificationRequestInfo']['subjectPKInfo']['subjectPublicKey']):
                return $this->computeKeyIdentifier($key['certificationRequestInfo']['subjectPKInfo']['subjectPublicKey'], $method);
            case !is_object($key):
                return false;
            case $key instanceof Element:
                // Assume the element is a bitstring-packed key.
                $asn1 = new ASN1();
                $decoded = $asn1->decodeBER($key->element);
                if (empty($decoded)) {
                    return false;
                }
                $raw = $asn1->asn1map($decoded[0], array('type' => ASN1::TYPE_BIT_STRING));
                if (empty($raw)) {
                    return false;
                }
                $raw = base64_decode($raw);
                // If the key is private, compute identifier from its corresponding public key.
                $key = new RSA();
                if (!$key->loadKey($raw)) {
                    return false;   // Not an unencrypted RSA key.
                }
                if ($key->getPrivateKey() !== false) {  // If private.
                    return $this->computeKeyIdentifier($key, $method);
                }
                $key = $raw;    // Is a public key.
                break;
            case $key instanceof X509:
                if (isset($key->publicKey)) {
                    return $this->computeKeyIdentifier($key->publicKey, $method);
                }
                if (isset($key->privateKey)) {
                    return $this->computeKeyIdentifier($key->privateKey, $method);
                }
                if (isset($key->currentCert['tbsCertificate']) || isset($key->currentCert['certificationRequestInfo'])) {
                    return $this->computeKeyIdentifier($key->currentCert, $method);
                }
                return false;
            default: // Should be a key object (i.e.: \phpseclib\Crypt\RSA).
                $key = $key->getPublicKey(RSA::PUBLIC_FORMAT_PKCS1);
                break;
        }

        // If in PEM format, convert to binary.
        $key = $this->_extractBER($key);

        // Now we have the key string: compute its sha-1 sum.
        $hash = new Hash('sha1');
        $hash = $hash->hash($key);

        if ($method == 2) {
            $hash = substr($hash, -8);
            $hash[0] = chr((ord($hash[0]) & 0x0F) | 0x40);
        }

        return $hash;
    }

    /**
     * Format a public key as appropriate
     *
     * @access private
     * @return array
     */
    function _formatSubjectPublicKey()
    {
        if ($this->publicKey instanceof RSA) {
            // the following two return statements do the same thing. i dunno.. i just prefer the later for some reason.
            // the former is a good example of how to do fuzzing on the public key
            //return new Element(base64_decode(preg_replace('#-.+-|[\r\n]#', '', $this->publicKey->getPublicKey())));
            return array(
                'algorithm' => array('algorithm' => 'rsaEncryption'),
                'subjectPublicKey' => $this->publicKey->getPublicKey(RSA::PUBLIC_FORMAT_PKCS1)
            );
        }

        return false;
    }

    /**
     * Set the domain name's which the cert is to be valid for
     *
     * @access public
     * @return array
     */
    function setDomain()
    {
        $this->domains = func_get_args();
        $this->removeDNProp('id-at-commonName');
        $this->setDNProp('id-at-commonName', $this->domains[0]);
    }

    /**
     * Set the IP Addresses's which the cert is to be valid for
     *
     * @access public
     */
    function setIPAddress()
    {
        $this->ipAddresses = func_get_args();
        /*
        if (!isset($this->domains)) {
            $this->removeDNProp('id-at-commonName');
            $this->setDNProp('id-at-commonName', $this->ipAddresses[0]);
        }
        */
    }

    /**
     * Helper function to build domain array
     *
     * @access private
     * @param string $domain
     * @return array
     */
    function _dnsName($domain)
    {
        return array('dNSName' => $domain);
    }

    /**
     * Helper function to build IP Address array
     *
     * (IPv6 is not currently supported)
     *
     * @access private
     * @param string $address
     * @return array
     */
    function _iPAddress($address)
    {
        return array('iPAddress' => $address);
    }

    /**
     * Get the index of a revoked certificate.
     *
     * @param array $rclist
     * @param string $serial
     * @param bool $create optional
     * @access private
     * @return int|false
     */
    function _revokedCertificate(&$rclist, $serial, $create = false)
    {
        $serial = new BigInteger($serial);

        foreach ($rclist as $i => $rc) {
            if (!($serial->compare($rc['userCertificate']))) {
                return $i;
            }
        }

        if (!$create) {
            return false;
        }

        $i = count($rclist);
        $revocationDate = new DateTime('now', new DateTimeZone(@date_default_timezone_get()));
        $rclist[] = array('userCertificate' => $serial,
                          'revocationDate'  => $this->_timeField($revocationDate->format('D, d M Y H:i:s O')));
        return $i;
    }

    /**
     * Revoke a certificate.
     *
     * @param string $serial
     * @param string $date optional
     * @access public
     * @return bool
     */
    function revoke($serial, $date = null)
    {
        if (isset($this->currentCert['tbsCertList'])) {
            if (is_array($rclist = &$this->_subArray($this->currentCert, 'tbsCertList/revokedCertificates', true))) {
                if ($this->_revokedCertificate($rclist, $serial) === false) { // If not yet revoked
                    if (($i = $this->_revokedCertificate($rclist, $serial, true)) !== false) {
                        if (!empty($date)) {
                            $rclist[$i]['revocationDate'] = $this->_timeField($date);
                        }

                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Unrevoke a certificate.
     *
     * @param string $serial
     * @access public
     * @return bool
     */
    function unrevoke($serial)
    {
        if (is_array($rclist = &$this->_subArray($this->currentCert, 'tbsCertList/revokedCertificates'))) {
            if (($i = $this->_revokedCertificate($rclist, $serial)) !== false) {
                unset($rclist[$i]);
                $rclist = array_values($rclist);
                return true;
            }
        }

        return false;
    }

    /**
     * Get a revoked certificate.
     *
     * @param string $serial
     * @access public
     * @return mixed
     */
    function getRevoked($serial)
    {
        if (is_array($rclist = $this->_subArray($this->currentCert, 'tbsCertList/revokedCertificates'))) {
            if (($i = $this->_revokedCertificate($rclist, $serial)) !== false) {
                return $rclist[$i];
            }
        }

        return false;
    }

    /**
     * List revoked certificates
     *
     * @param array $crl optional
     * @access public
     * @return array
     */
    function listRevoked($crl = null)
    {
        if (!isset($crl)) {
            $crl = $this->currentCert;
        }

        if (!isset($crl['tbsCertList'])) {
            return false;
        }

        $result = array();

        if (is_array($rclist = $this->_subArray($crl, 'tbsCertList/revokedCertificates'))) {
            foreach ($rclist as $rc) {
                $result[] = $rc['userCertificate']->toString();
            }
        }

        return $result;
    }

    /**
     * Remove a Revoked Certificate Extension
     *
     * @param string $serial
     * @param string $id
     * @access public
     * @return bool
     */
    function removeRevokedCertificateExtension($serial, $id)
    {
        if (is_array($rclist = &$this->_subArray($this->currentCert, 'tbsCertList/revokedCertificates'))) {
            if (($i = $this->_revokedCertificate($rclist, $serial)) !== false) {
                return $this->_removeExtension($id, "tbsCertList/revokedCertificates/$i/crlEntryExtensions");
            }
        }

        return false;
    }

    /**
     * Get a Revoked Certificate Extension
     *
     * Returns the extension if it exists and false if not
     *
     * @param string $serial
     * @param string $id
     * @param array $crl optional
     * @access public
     * @return mixed
     */
    function getRevokedCertificateExtension($serial, $id, $crl = null)
    {
        if (!isset($crl)) {
            $crl = $this->currentCert;
        }

        if (is_array($rclist = $this->_subArray($crl, 'tbsCertList/revokedCertificates'))) {
            if (($i = $this->_revokedCertificate($rclist, $serial)) !== false) {
                return $this->_getExtension($id, $crl,  "tbsCertList/revokedCertificates/$i/crlEntryExtensions");
            }
        }

        return false;
    }

    /**
     * Returns a list of all extensions in use for a given revoked certificate
     *
     * @param string $serial
     * @param array $crl optional
     * @access public
     * @return array
     */
    function getRevokedCertificateExtensions($serial, $crl = null)
    {
        if (!isset($crl)) {
            $crl = $this->currentCert;
        }

        if (is_array($rclist = $this->_subArray($crl, 'tbsCertList/revokedCertificates'))) {
            if (($i = $this->_revokedCertificate($rclist, $serial)) !== false) {
                return $this->_getExtensions($crl, "tbsCertList/revokedCertificates/$i/crlEntryExtensions");
            }
        }

        return false;
    }

    /**
     * Set a Revoked Certificate Extension
     *
     * @param string $serial
     * @param string $id
     * @param mixed $value
     * @param bool $critical optional
     * @param bool $replace optional
     * @access public
     * @return bool
     */
    function setRevokedCertificateExtension($serial, $id, $value, $critical = false, $replace = true)
    {
        if (isset($this->currentCert['tbsCertList'])) {
            if (is_array($rclist = &$this->_subArray($this->currentCert, 'tbsCertList/revokedCertificates', true))) {
                if (($i = $this->_revokedCertificate($rclist, $serial, true)) !== false) {
                    return $this->_setExtension($id, $value, $critical, $replace, "tbsCertList/revokedCertificates/$i/crlEntryExtensions");
                }
            }
        }

        return false;
    }

    /**
     * Extract raw BER from Base64 encoding
     *
     * @access private
     * @param string $str
     * @return string
     */
    function _extractBER($str)
    {
        /* X.509 certs are assumed to be base64 encoded but sometimes they'll have additional things in them
         * above and beyond the ceritificate.
         * ie. some may have the following preceding the -----BEGIN CERTIFICATE----- line:
         *
         * Bag Attributes
         *     localKeyID: 01 00 00 00
         * subject=/O=organization/OU=org unit/CN=common name
         * issuer=/O=organization/CN=common name
         */
        if (strlen($str) > ini_get('pcre.backtrack_limit')) {
            $temp = $str;
        } else {
            $temp = preg_replace('#.*?^-+[^-]+-+[\r\n ]*$#ms', '', $str, 1);
            $temp = preg_replace('#-+END.*[\r\n ]*.*#ms', '', $temp, 1);
        }
        // remove new lines
        $temp = str_replace(array("\r", "\n", ' '), '', $temp);
        // remove the -----BEGIN CERTIFICATE----- and -----END CERTIFICATE----- stuff
        $temp = preg_replace('#^-+[^-]+-+|-+[^-]+-+$#', '', $temp);
        $temp = preg_match('#^[a-zA-Z\d/+]*={0,2}$#', $temp) ? base64_decode($temp) : false;
        return $temp != false ? $temp : $str;
    }

    /**
     * Returns the OID corresponding to a name
     *
     * What's returned in the associative array returned by loadX509() (or load*()) is either a name or an OID if
     * no OID to name mapping is available. The problem with this is that what may be an unmapped OID in one version
     * of phpseclib may not be unmapped in the next version, so apps that are looking at this OID may not be able
     * to work from version to version.
     *
     * This method will return the OID if a name is passed to it and if no mapping is avialable it'll assume that
     * what's being passed to it already is an OID and return that instead. A few examples.
     *
     * getOID('2.16.840.1.101.3.4.2.1') == '2.16.840.1.101.3.4.2.1'
     * getOID('id-sha256') == '2.16.840.1.101.3.4.2.1'
     * getOID('zzz') == 'zzz'
     *
     * @access public
     * @return string
     */
    function getOID($name)
    {
        static $reverseMap;
        if (!isset($reverseMap)) {
            $reverseMap = array_flip($this->oids);
        }
        return isset($reverseMap[$name]) ? $reverseMap[$name] : $name;
    }
}
