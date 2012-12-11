<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Pure-PHP X.509 Parser
 *
 * PHP versions 4 and 5
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
 * LICENSE: Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @category   File
 * @package    File_X509
 * @author     Jim Wigginton <terrafrost@php.net>
 * @copyright  MMXII Jim Wigginton
 * @license    http://www.opensource.org/licenses/mit-license.html  MIT License
 * @version    $Id$
 * @link       htp://phpseclib.sourceforge.net
 */

/**
 * Include File_ASN1
 */
if (!class_exists('File_ASN1')) {
    require_once('File/ASN1.php');
}

/**
 * Flag to only accept signatures signed by certificate authorities
 *
 * @access public
 * @see File_X509::validateSignature()
 */
define('FILE_X509_VALIDATE_SIGNATURE_BY_CA', 1);

/**#@+
 * @access public
 * @see File_X509::getDN()
 */
/**
 * Return internal array representation
 */
define('FILE_X509_DN_ARRAY', 0); // Internal array representation.
/**
 * Return string
 */
define('FILE_X509_DN_STRING', 1);
/**
 * Return ASN.1 name string
 */
define('FILE_X509_DN_ASN1', 2);
/**
 * Return OpenSSL compatible array
 */
define('FILE_X509_DN_OPENSSL', 3);
/**
 * Return canonical ASN.1 RDNs string
 */
define('FILE_X509_DN_CANON', 4);
/**
 * Return name ash for file indexing
 */
define('FILE_X509_DN_HASH', 5);
/**#@-*/

/**
 * Pure-PHP X.509 Parser
 *
 * @author  Jim Wigginton <terrafrost@php.net>
 * @version 0.3.0
 * @access  public
 * @package File_X509
 */
class File_X509 {
    /**
     * ASN.1 syntax for X.509 certificates
     *
     * @var Array
     * @access private
     */
    var $Certificate;

    /**#@+
     * ASN.1 syntax for various extensions
     *
     * @access private
     */
    var $KeyUsage;
    var $ExtKeyUsageSyntax;
    var $BasicConstraints;
    var $KeyIdentifier;
    var $CRLDistributionPoints;
    var $AuthorityKeyIdentifier;
    var $CertificatePolicies;
    var $AuthorityInfoAccessSyntax;
    var $SubjectAltName;
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
    /**#@-*/

    /**
     * ASN.1 syntax for Certificate Signing Requests (RFC2986)
     *
     * @var Array
     * @access private
     */
    var $CertificationRequest;

    /**
     * ASN.1 syntax for Certificate Revocation Lists (RFC5280)
     *
     * @var Array
     * @access private
     */
    var $CertificateList;

    /**
     * Distinguished Name
     *
     * @var Array
     * @access private
     */
    var $dn;

    /**
     * Public key
     *
     * @var String
     * @access private
     */
    var $publicKey;

    /**
     * Private key
     *
     * @var String
     * @access private
     */
    var $privateKey;

    /**
     * Object identifiers for X.509 certificates
     *
     * @var Array
     * @access private
     * @link http://en.wikipedia.org/wiki/Object_identifier
     */
    var $oids;

    /**
     * The certificate authorities
     *
     * @var Array
     * @access private
     */
    var $CAs;

    /**
     * The currently loaded certificate
     *
     * @var Array
     * @access private
     */
    var $currentCert;

    /**
     * The signature subject
     *
     * There's no guarantee File_X509 is going to reencode an X.509 cert in the same way it was originally
     * encoded so we take save the portion of the original cert that the signature would have made for. 
     *
     * @var String
     * @access private
     */
    var $signatureSubject;

    /**
     * Certificate Start Date
     *
     * @var String
     * @access private
     */
    var $startDate;

    /**
     * Certificate End Date
     *
     * @var String
     * @access private
     */
    var $endDate;

    /**
     * Serial Number
     *
     * @var String
     * @access private
     */
    var $serialNumber;

    /**
     * Key Identifier
     *
     * See {@link http://tools.ietf.org/html/rfc5280#section-4.2.1.1 RFC5280#section-4.2.1.1} and
     * {@link http://tools.ietf.org/html/rfc5280#section-4.2.1.2 RFC5280#section-4.2.1.2}.
     *
     * @var String
     * @access private
     */
    var $currentKeyIdentifier;

    /**
     * CA Flag
     *
     * @var Boolean
     * @access private
     */
    var $caFlag = false;

    /**
     * Default Constructor.
     *
     * @return File_X509
     * @access public
     */
    function File_X509()
    {
        // Explicitly Tagged Module, 1988 Syntax
        // http://tools.ietf.org/html/rfc5280#appendix-A.1

        $DirectoryString = array(
            'type'     => FILE_ASN1_TYPE_CHOICE,
            'children' => array(
                'teletexString'   => array('type' => FILE_ASN1_TYPE_TELETEX_STRING),
                'printableString' => array('type' => FILE_ASN1_TYPE_PRINTABLE_STRING),
                'universalString' => array('type' => FILE_ASN1_TYPE_UNIVERSAL_STRING),
                'utf8String'      => array('type' => FILE_ASN1_TYPE_UTF8_STRING),
                'bmpString'       => array('type' => FILE_ASN1_TYPE_BMP_STRING)
            )
        );

        $AttributeValue = array('type' => FILE_ASN1_TYPE_ANY);

        $AttributeType = array('type' => FILE_ASN1_TYPE_OBJECT_IDENTIFIER);

        $AttributeTypeAndValue = array(
            'type'     => FILE_ASN1_TYPE_SEQUENCE,
            'children' => array(
                'type' => $AttributeType,
                'value'=> $AttributeValue
            )
        );

        /*
        In practice, RDNs containing multiple name-value pairs (called "multivalued RDNs") are rare,
        but they can be useful at times when either there is no unique attribute in the entry or you
        want to ensure that the entry's DN contains some useful identifying information.

        - https://www.opends.org/wiki/page/DefinitionRelativeDistinguishedName
        */
        $this->RelativeDistinguishedName = array(
            'type'     => FILE_ASN1_TYPE_SET,
            'min'      => 1,
            'max'      => -1,
            'children' => $AttributeTypeAndValue
        );

        // http://tools.ietf.org/html/rfc5280#section-4.1.2.4
        $RDNSequence = array(
            'type'     => FILE_ASN1_TYPE_SEQUENCE,
            // RDNSequence does not define a min or a max, which means it doesn't have one
            'min'      => 0,
            'max'      => -1,
            'children' => $this->RelativeDistinguishedName
        );

        $this->Name = array(
            'type'     => FILE_ASN1_TYPE_CHOICE,
            'children' => array(
                'rdnSequence' => $RDNSequence
            )
        );

        // http://tools.ietf.org/html/rfc5280#section-4.1.1.2
        $AlgorithmIdentifier = array(
            'type'     => FILE_ASN1_TYPE_SEQUENCE,
            'children' => array(
                'algorithm'  => array('type' => FILE_ASN1_TYPE_OBJECT_IDENTIFIER),
                'parameters' => array(
                                    'type'     => FILE_ASN1_TYPE_ANY,
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
            'type'     => FILE_ASN1_TYPE_SEQUENCE,
            'children' => array(
                'extnId'   => array('type' => FILE_ASN1_TYPE_OBJECT_IDENTIFIER),
                'critical' => array(
                                  'type'     => FILE_ASN1_TYPE_BOOLEAN,
                                  'optional' => true,
                                  'default'  => false
                              ),
                'extnValue' => array('type' => FILE_ASN1_TYPE_OCTET_STRING)
            )
        );

        $Extensions = array(
            'type'     => FILE_ASN1_TYPE_SEQUENCE,
            'min'      => 1,
            // technically, it's MAX, but we'll assume anything < 0 is MAX
            'max'      => -1,
            // if 'children' isn't an array then 'min' and 'max' must be defined
            'children' => $Extension
        );

        $SubjectPublicKeyInfo = array(
            'type'     => FILE_ASN1_TYPE_SEQUENCE,
            'children' => array(
                'algorithm'        => $AlgorithmIdentifier,
                'subjectPublicKey' => array('type' => FILE_ASN1_TYPE_BIT_STRING)
            )
        );

        $UniqueIdentifier = array('type' => FILE_ASN1_TYPE_BIT_STRING);

        $Time = array(
            'type'     => FILE_ASN1_TYPE_CHOICE,
            'children' => array(
                'utcTime'     => array('type' => FILE_ASN1_TYPE_UTC_TIME),
                'generalTime' => array('type' => FILE_ASN1_TYPE_GENERALIZED_TIME)
            )
        );

        // http://tools.ietf.org/html/rfc5280#section-4.1.2.5
        $Validity = array(
            'type'     => FILE_ASN1_TYPE_SEQUENCE,
            'children' => array(
                'notBefore' => $Time,
                'notAfter'  => $Time
            )
        );

        $CertificateSerialNumber = array('type' => FILE_ASN1_TYPE_INTEGER);

        $Version = array(
            'type'    => FILE_ASN1_TYPE_INTEGER,
            'mapping' => array('v1', 'v2', 'v3')
        );

        // assert($TBSCertificate['children']['signature'] == $Certificate['children']['signatureAlgorithm'])
        $TBSCertificate = array(
            'type'     => FILE_ASN1_TYPE_SEQUENCE,
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
                                           ) + $Extensions
            )
        );

        $this->Certificate = array(
            'type'     => FILE_ASN1_TYPE_SEQUENCE,
            'children' => array(
                 'tbsCertificate'     => $TBSCertificate,
                 'signatureAlgorithm' => $AlgorithmIdentifier,
                 'signature'          => array('type' => FILE_ASN1_TYPE_BIT_STRING)
            )
        );

        $this->KeyUsage = array(
            'type'    => FILE_ASN1_TYPE_BIT_STRING,
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
            'type'     => FILE_ASN1_TYPE_SEQUENCE,
            'children' => array(
                'cA'                => array(
                                                 'type'     => FILE_ASN1_TYPE_BOOLEAN,
                                                 'optional' => true,
                                                 'default'  => false
                                       ),
                'pathLenConstraint' => array(
                                                 'type' => FILE_ASN1_TYPE_INTEGER,
                                                 'optional' => true
                                       )
            )
        );

        $this->KeyIdentifier = array('type' => FILE_ASN1_TYPE_OCTET_STRING);

        $OrganizationalUnitNames = array(
            'type'     => FILE_ASN1_TYPE_SEQUENCE,
            'min'      => 1,
            'max'      => 4, // ub-organizational-units
            'children' => array('type' => FILE_ASN1_TYPE_PRINTABLE_STRING)
        );

        $PersonalName = array(
            'type'     => FILE_ASN1_TYPE_SET,
            'children' => array(
                'surname'              => array(
                                           'type' => FILE_ASN1_TYPE_PRINTABLE_STRING,
                                           'constant' => 0,
                                           'optional' => true,
                                           'implicit' => true
                                         ),
                'given-name'           => array(
                                           'type' => FILE_ASN1_TYPE_PRINTABLE_STRING,
                                           'constant' => 1,
                                           'optional' => true,
                                           'implicit' => true
                                         ),
                'initials'             => array(
                                           'type' => FILE_ASN1_TYPE_PRINTABLE_STRING,
                                           'constant' => 2,
                                           'optional' => true,
                                           'implicit' => true
                                         ),
                'generation-qualifier' => array(
                                           'type' => FILE_ASN1_TYPE_PRINTABLE_STRING,
                                           'constant' => 3,
                                           'optional' => true,
                                           'implicit' => true
                                         )
            )
        );

        $NumericUserIdentifier = array('type' => FILE_ASN1_TYPE_NUMERIC_STRING);

        $OrganizationName = array('type' => FILE_ASN1_TYPE_PRINTABLE_STRING);

        $PrivateDomainName = array(
            'type'     => FILE_ASN1_TYPE_CHOICE,
            'children' => array(
                'numeric'   => array('type' => FILE_ASN1_TYPE_NUMERIC_STRING),
                'printable' => array('type' => FILE_ASN1_TYPE_PRINTABLE_STRING)
            )
        );

        $TerminalIdentifier = array('type' => FILE_ASN1_TYPE_PRINTABLE_STRING);

        $NetworkAddress = array('type' => FILE_ASN1_TYPE_NUMERIC_STRING);

        $AdministrationDomainName = array(
            'type'     => FILE_ASN1_TYPE_CHOICE,
            // if class isn't present it's assumed to be FILE_ASN1_CLASS_UNIVERSAL or
            // (if constant is present) FILE_ASN1_CLASS_CONTEXT_SPECIFIC
            'class'    => FILE_ASN1_CLASS_APPLICATION,
            'cast'     => 2,
            'children' => array(
                'numeric'   => array('type' => FILE_ASN1_TYPE_NUMERIC_STRING),
                'printable' => array('type' => FILE_ASN1_TYPE_PRINTABLE_STRING)
            )
        );

        $CountryName = array(
            'type'     => FILE_ASN1_TYPE_CHOICE,
            // if class isn't present it's assumed to be FILE_ASN1_CLASS_UNIVERSAL or
            // (if constant is present) FILE_ASN1_CLASS_CONTEXT_SPECIFIC
            'class'    => FILE_ASN1_CLASS_APPLICATION,
            'cast'     => 1,
            'children' => array(
                'x121-dcc-code'        => array('type' => FILE_ASN1_TYPE_NUMERIC_STRING),
                'iso-3166-alpha2-code' => array('type' => FILE_ASN1_TYPE_PRINTABLE_STRING)
            )
        );

        $AnotherName = array(
            'type'     => FILE_ASN1_TYPE_SEQUENCE,
            'children' => array(
                 'type-id' => array('type' => FILE_ASN1_TYPE_OBJECT_IDENTIFIER),
                 'value'   => array(
                                  'type' => FILE_ASN1_TYPE_ANY,
                                  'constant' => 0,
                                  'optional' => true,
                                  'explicit' => true
                              )
            )
        );

        $ExtensionAttribute = array(
            'type'     => FILE_ASN1_TYPE_SEQUENCE,
            'children' => array(
                 'extension-attribute-type'  => array(
                                                    'type' => FILE_ASN1_TYPE_PRINTABLE_STRING,
                                                    'constant' => 0,
                                                    'optional' => true,
                                                    'implicit' => true
                                                ),
                 'extension-attribute-value' => array(
                                                    'type' => FILE_ASN1_TYPE_ANY,
                                                    'constant' => 1,
                                                    'optional' => true,
                                                    'explicit' => true
                                                )
            )
        );

        $ExtensionAttributes = array(
            'type'     => FILE_ASN1_TYPE_SET,
            'min'      => 1,
            'max'      => 256, // ub-extension-attributes
            'children' => $ExtensionAttribute
        );

        $BuiltInDomainDefinedAttribute = array(
            'type'     => FILE_ASN1_TYPE_SEQUENCE,
            'children' => array(
                 'type'  => array('type' => FILE_ASN1_TYPE_PRINTABLE_STRING),
                 'value' => array('type' => FILE_ASN1_TYPE_PRINTABLE_STRING)
            )
        );

        $BuiltInDomainDefinedAttributes = array(
            'type'     => FILE_ASN1_TYPE_SEQUENCE,
            'min'      => 1,
            'max'      => 4, // ub-domain-defined-attributes
            'children' => $BuiltInDomainDefinedAttribute
        );

        $BuiltInStandardAttributes =  array(
            'type'     => FILE_ASN1_TYPE_SEQUENCE,
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
            'type'     => FILE_ASN1_TYPE_SEQUENCE,
            'children' => array(
                 'built-in-standard-attributes'       => $BuiltInStandardAttributes,
                 'built-in-domain-defined-attributes' => array('optional' => true) + $BuiltInDomainDefinedAttributes,
                 'extension-attributes'               => array('optional' => true) + $ExtensionAttributes
            )
        );

        $EDIPartyName = array(
            'type'     => FILE_ASN1_TYPE_SEQUENCE,
            'children' => array(
                 'nameAssigner' => array(
                                    'constant' => 0,
                                    'optional' => true,
                                    'implicit' => true
                                ) + $DirectoryString,
                 // partyName is technically required but File_ASN1 doesn't currently support non-optional constants and
                 // setting it to optional gets the job done in any event.
                 'partyName'    => array(
                                    'constant' => 1,
                                    'optional' => true,
                                    'implicit' => true
                                ) + $DirectoryString
            )
        );

        $GeneralName = array(
            'type'     => FILE_ASN1_TYPE_CHOICE,
            'children' => array(
                'otherName'                 => array(
                                                 'constant' => 0,
                                                 'optional' => true,
                                                 'implicit' => true
                                               ) + $AnotherName,
                'rfc822Name'                => array(
                                                 'type' => FILE_ASN1_TYPE_IA5_STRING,
                                                 'constant' => 1,
                                                 'optional' => true,
                                                 'implicit' => true
                                               ),
                'dNSName'                   => array(
                                                 'type' => FILE_ASN1_TYPE_IA5_STRING,
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
                                                 'type' => FILE_ASN1_TYPE_IA5_STRING,
                                                 'constant' => 6,
                                                 'optional' => true,
                                                 'implicit' => true
                                               ),
                'iPAddress'                 => array(
                                                 'type' => FILE_ASN1_TYPE_OCTET_STRING,
                                                 'constant' => 7,
                                                 'optional' => true,
                                                 'implicit' => true
                                               ),
                'registeredID'              => array(
                                                 'type' => FILE_ASN1_TYPE_OBJECT_IDENTIFIER,
                                                 'constant' => 8,
                                                 'optional' => true,
                                                 'implicit' => true
                                               )
            )
        );

        $GeneralNames = array(
            'type'     => FILE_ASN1_TYPE_SEQUENCE,
            'min'      => 1,
            'max'      => -1,
            'children' => $GeneralName
        );

        $this->IssuerAltName = $GeneralNames;

        $ReasonFlags = array(
            'type'    => FILE_ASN1_TYPE_BIT_STRING,
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
            'type'     => FILE_ASN1_TYPE_CHOICE,
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
            'type'     => FILE_ASN1_TYPE_SEQUENCE,
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
            'type'     => FILE_ASN1_TYPE_SEQUENCE,
            'min'      => 1,
            'max'      => -1,
            'children' => $DistributionPoint
        );

        $this->AuthorityKeyIdentifier = array(
            'type'     => FILE_ASN1_TYPE_SEQUENCE,
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

        $PolicyQualifierId = array('type' => FILE_ASN1_TYPE_OBJECT_IDENTIFIER);

        $PolicyQualifierInfo = array(
            'type'     => FILE_ASN1_TYPE_SEQUENCE,
            'children' => array(
                'policyQualifierId' => $PolicyQualifierId,
                'qualifier'         => array('type' => FILE_ASN1_TYPE_ANY)
            )
        );

        $CertPolicyId = array('type' => FILE_ASN1_TYPE_OBJECT_IDENTIFIER);

        $PolicyInformation = array(
            'type'     => FILE_ASN1_TYPE_SEQUENCE,
            'children' => array(
                'policyIdentifier' => $CertPolicyId,
                'policyQualifiers' => array(
                                          'type'     => FILE_ASN1_TYPE_SEQUENCE,
                                          'min'      => 0,
                                          'max'      => -1,
                                          'optional' => true,
                                          'children' => $PolicyQualifierInfo
                                      )
            )
        );

        $this->CertificatePolicies = array(
            'type'     => FILE_ASN1_TYPE_SEQUENCE,
            'min'      => 1,
            'max'      => -1,
            'children' => $PolicyInformation
        );

        $this->PolicyMappings = array(
            'type'     => FILE_ASN1_TYPE_SEQUENCE,
            'min'      => 1,
            'max'      => -1,
            'children' => array(
                              'type'     => FILE_ASN1_TYPE_SEQUENCE,
                              'children' => array(
                                  'issuerDomainPolicy' => $CertPolicyId,
                                  'subjectDomainPolicy' => $CertPolicyId
                              )
                       )
        );

        $KeyPurposeId = array('type' => FILE_ASN1_TYPE_OBJECT_IDENTIFIER);

        $this->ExtKeyUsageSyntax = array(
            'type'     => FILE_ASN1_TYPE_SEQUENCE,
            'min'      => 1,
            'max'      => -1,
            'children' => $KeyPurposeId
        );

        $AccessDescription = array(
            'type'     => FILE_ASN1_TYPE_SEQUENCE,
            'children' => array(
                'accessMethod'   => array('type' => FILE_ASN1_TYPE_OBJECT_IDENTIFIER),
                'accessLocation' => $GeneralName
            )
        );

        $this->AuthorityInfoAccessSyntax = array(
            'type'     => FILE_ASN1_TYPE_SEQUENCE,
            'min'      => 1,
            'max'      => -1,
            'children' => $AccessDescription
        );

        $this->SubjectAltName = $GeneralNames;

        $this->PrivateKeyUsagePeriod = array(
            'type'     => FILE_ASN1_TYPE_SEQUENCE,
            'children' => array(
                'notBefore' => array(
                                                 'constant' => 0,
                                                 'optional' => true,
                                                 'implicit' => true,
                                                 'type' => FILE_ASN1_TYPE_GENERALIZED_TIME),
                'notAfter'  => array(
                                                 'constant' => 1,
                                                 'optional' => true,
                                                 'implicit' => true,
                                                 'type' => FILE_ASN1_TYPE_GENERALIZED_TIME)
            )
        );

        $BaseDistance = array('type' => FILE_ASN1_TYPE_INTEGER);

        $GeneralSubtree = array(
            'type'     => FILE_ASN1_TYPE_SEQUENCE,
            'children' => array(
                'base'    => $GeneralName,
                'minimum' => array(
                                 'constant' => 0,
                                 'optional' => true,
                                 'implicit' => true,
                                 'default' => new Math_BigInteger(0)
                             ) + $BaseDistance,
                'maximum' => array(
                                 'constant' => 1,
                                 'optional' => true,
                                 'implicit' => true,
                             ) + $BaseDistance
            )
        );

        $GeneralSubtrees = array(
            'type'     => FILE_ASN1_TYPE_SEQUENCE,
            'min'      => 1,
            'max'      => -1,
            'children' => $GeneralSubtree
        );

        $this->NameConstraints = array(
            'type'     => FILE_ASN1_TYPE_SEQUENCE,
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

        $this->CPSuri = array('type' => FILE_ASN1_TYPE_IA5_STRING);

        $DisplayText = array(
            'type'     => FILE_ASN1_TYPE_CHOICE,
            'children' => array(
                'ia5String'     => array('type' => FILE_ASN1_TYPE_IA5_STRING),
                'visibleString' => array('type' => FILE_ASN1_TYPE_VISIBLE_STRING),
                'bmpString'     => array('type' => FILE_ASN1_TYPE_BMP_STRING),
                'utf8String'    => array('type' => FILE_ASN1_TYPE_UTF8_STRING)
            )
        );

        $NoticeReference = array(
            'type'     => FILE_ASN1_TYPE_SEQUENCE,
            'children' => array(
                'organization'  => $DisplayText,
                'noticeNumbers' => array(
                                       'type'     => FILE_ASN1_TYPE_SEQUENCE,
                                       'min'      => 1,
                                       'max'      => 200,
                                       'children' => array('type' => FILE_ASN1_TYPE_INTEGER)
                                   )
            )
        );

        $this->UserNotice = array(
            'type'     => FILE_ASN1_TYPE_SEQUENCE,
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
            'type'    => FILE_ASN1_TYPE_BIT_STRING,
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

        $this->netscape_comment = array('type' => FILE_ASN1_TYPE_IA5_STRING);
        $this->netscape_ca_policy_url = array('type' => FILE_ASN1_TYPE_IA5_STRING);

        // attribute is used in RFC2986 but we're using the RFC5280 definition

        $Attribute = array(
            'type'     => FILE_ASN1_TYPE_SEQUENCE,
            'children' => array(
                'type' => $AttributeType,
                'value'=> array(
                              'type'     => FILE_ASN1_TYPE_SET,
                              'min'      => 1,
                              'max'      => -1,
                              'children' => $AttributeValue
                          )
            )
        );

        // adapted from <http://tools.ietf.org/html/rfc2986>

        $Attributes = array(
            'type'     => FILE_ASN1_TYPE_SET,
            'min'      => 1,
            'max'      => -1,
            'children' => $Attribute
        );

        $CertificationRequestInfo = array(
            'type'     => FILE_ASN1_TYPE_SEQUENCE,
            'children' => array(
                'version'       => array(
                                       'type' => FILE_ASN1_TYPE_INTEGER,
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
            'type'     => FILE_ASN1_TYPE_SEQUENCE,
            'children' => array(
                'certificationRequestInfo' => $CertificationRequestInfo,
                'signatureAlgorithm'       => $AlgorithmIdentifier,
                'signature'                => array('type' => FILE_ASN1_TYPE_BIT_STRING)
            )
        );

        $RevokedCertificate = array(
            'type'     => FILE_ASN1_TYPE_SEQUENCE,
            'children' => array(
                              'userCertificate'    => $CertificateSerialNumber,
                              'revocationDate'     => $Time,
                              'crlEntryExtensions' => array(
                                                          'optional' => true
                                                      ) + $Extensions
                          )
        );

        $TBSCertList = array(
            'type'     => FILE_ASN1_TYPE_SEQUENCE,
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
                                             'type'     => FILE_ASN1_TYPE_SEQUENCE,
                                             'optional' => true,
                                             'min'      => 0,
                                             'max'      => -1,
                                             'children' => $RevokedCertificate
                                         ),
                'crlExtensions'       => array(
                                             'constant' => 0,
                                             'optional' => true,
                                             'explicit' => true
                                         ) + $Extensions
            )
        );

        $this->CertificateList = array(
            'type'     => FILE_ASN1_TYPE_SEQUENCE,
            'children' => array(
                'tbsCertList'        => $TBSCertList,
                'signatureAlgorithm' => $AlgorithmIdentifier,
                'signature'          => array('type' => FILE_ASN1_TYPE_BIT_STRING)
            )
        );

        $this->CRLNumber = array('type' => FILE_ASN1_TYPE_INTEGER);

        $this->CRLReason = array('type' => FILE_ASN1_TYPE_ENUMERATED,
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

        $this->IssuingDistributionPoint = array('type' => FILE_ASN1_TYPE_SEQUENCE,
            'children' => array(
                'distributionPoint'          => array(
                                                    'constant' => 0,
                                                    'optional' => true,
                                                    'explicit' => true
                                                ) + $DistributionPointName,
                'onlyContainsUserCerts'      => array(
                                                    'type'     => FILE_ASN1_TYPE_BOOLEAN,
                                                    'constant' => 1,
                                                    'optional' => true,
                                                    'default'  => false,
                                                    'implicit' => true
                                                ),
                'onlyContainsCACerts'        => array(
                                                    'type'     => FILE_ASN1_TYPE_BOOLEAN,
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
                                                    'type'     => FILE_ASN1_TYPE_BOOLEAN,
                                                    'constant' => 4,
                                                    'optional' => true,
                                                    'default'  => false,
                                                    'implicit' => true
                                                ),
                'onlyContainsAttributeCerts' => array(
                                                    'type'     => FILE_ASN1_TYPE_BOOLEAN,
                                                    'constant' => 5,
                                                    'optional' => true,
                                                    'default'  => false,
                                                    'implicit' => true
                                                )
                          )
        );

        $this->InvalidityDate = array('type' => FILE_ASN1_TYPE_GENERALIZED_TIME);

        $this->CertificateIssuer = $GeneralNames;

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

            '0.9.2342.19200300.100.1.25' => 'id-domainComponent',
            '1.2.840.113549.1.9' => 'pkcs-9',
            '1.2.840.113549.1.9.1' => 'id-emailAddress',
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
            '2.2.840.10040.2' => 'holdInstruction',
            '2.2.840.10040.2.1' => 'id-holdinstruction-none',
            '2.2.840.10040.2.2' => 'id-holdinstruction-callissuer',
            '2.2.840.10040.2.3' => 'id-holdinstruction-reject',
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
            '1.2.840.113549.1.9.2' => 'unstructuredName', // PKCS #9 unstructured name
            '1.2.840.113549.1.9.7' => 'challengePassword' // Challenge password for certificate revocations
        );
    }

    /**
     * Load X.509 certificate
     *
     * Returns an associative array describing the X.509 cert or a false if the cert failed to load
     *
     * @param String $cert
     * @access public
     * @return Mixed
     */
    function loadX509($cert)
    {
        if (is_array($cert) && isset($cert['tbsCertificate'])) {
            $this->currentCert = $cert;
            unset($this->signatureSubject);
            return false;
        }

        $asn1 = new File_ASN1();

        /*
            X.509 certs are assumed to be base64 encoded but sometimes they'll have additional things in them above and beyond the ceritificate. ie.
            some may have the following preceeding the -----BEGIN CERTIFICATE----- line:

            subject=/O=organization/OU=org unit/CN=common name
            issuer=/O=organization/CN=common name
        */
        $temp = preg_replace('#^(?:[^-].+[\r\n]+)+|-.+-|[\r\n]| #', '', $cert);
        $temp = preg_match('#^[a-zA-Z\d/+]*={0,2}$#', $temp) ? base64_decode($temp) : false;
        if ($temp != false) {
            $cert = $temp;
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

        $this->_mapInExtensions($x509, 'tbsCertificate/extensions', $asn1);

        $key = &$x509['tbsCertificate']['subjectPublicKeyInfo']['subjectPublicKey'];
        $key = $this->_reformatKey($x509['tbsCertificate']['subjectPublicKeyInfo']['algorithm']['algorithm'], $key);

        $this->currentCert = $x509;
        $this->dn = $x509['tbsCertificate']['subject'];

        $currentKeyIdentifier = $this->getExtension('id-ce-subjectKeyIdentifier');
        $this->currentKeyIdentifier = is_string($currentKeyIdentifier) ? $currentKeyIdentifier : NULL;

        return $x509;
    }

    /**
     * Save X.509 certificate
     *
     * @param Array $cert
     * @access public
     * @return String
     */
    function saveX509($cert)
    {
        if (!is_array($cert) || !isset($cert['tbsCertificate'])) {
            return false;
        }

        if (is_array($cert['tbsCertificate']['subjectPublicKeyInfo'])) {
            switch ($cert['tbsCertificate']['subjectPublicKeyInfo']['algorithm']['algorithm']) {
                case 'rsaEncryption':
                    $cert['tbsCertificate']['subjectPublicKeyInfo']['subjectPublicKey'] = 
                        base64_encode("\0" . base64_decode(preg_replace('#-.+-|[\r\n]#', '', $cert['tbsCertificate']['subjectPublicKeyInfo']['subjectPublicKey'])));
            }
        }

        $asn1 = new File_ASN1();

        $asn1->loadOIDs($this->oids);

        $filters = array();
        $filters['tbsCertificate']['signature']['parameters'] = 
        $filters['tbsCertificate']['signature']['issuer']['rdnSequence']['value'] = 
        $filters['tbsCertificate']['issuer']['rdnSequence']['value'] = 
        $filters['tbsCertificate']['subject']['rdnSequence']['value'] = 
        $filters['tbsCertificate']['subjectPublicKeyInfo']['algorithm']['parameters'] = 
        $filters['signatureAlgorithm']['parameters'] = 
        $filters['authorityCertIssuer']['directoryName']['rdnSequence']['value'] = 
        //$filters['policyQualifiers']['qualifier'] = 
        $filters['distributionPoint']['fullName']['directoryName']['rdnSequence']['value'] = 
        $filters['directoryName']['rdnSequence']['value'] = 
            array('type' => FILE_ASN1_TYPE_UTF8_STRING);
        /* in the case of policyQualifiers/qualifier, the type has to be FILE_ASN1_TYPE_IA5_STRING.
           FILE_ASN1_TYPE_PRINTABLE_STRING will cause OpenSSL's X.509 parser to spit out random
           characters.
         */
        $filters['policyQualifiers']['qualifier'] = 
            array('type' => FILE_ASN1_TYPE_IA5_STRING);

        $asn1->loadFilters($filters);

        $this->_mapOutExtensions($cert, 'tbsCertificate/extensions', $asn1);

        $cert = $asn1->encodeDER($cert, $this->Certificate);

        return "-----BEGIN CERTIFICATE-----\r\n" . chunk_split(base64_encode($cert)) . '-----END CERTIFICATE-----';
    }

    /**
     * Map extension values from octet string to extension-specific internal
     *   format.
     *
     * @param Array ref $root
     * @param String $path
     * @param Object $asn1
     * @access private
     */
    function _mapInExtensions(&$root, $path, $asn1)
    {
        $extensions = &$this->_subArray($root, $path);

        if (is_array($extensions)) {
            for ($i = 0; $i < count($extensions); $i++) {
                $id = $extensions[$i]['extnId'];
                $value = &$extensions[$i]['extnValue'];
                $value = base64_decode($value);
                $decoded = $asn1->decodeBER($value);
                /* [extnValue] contains the DER encoding of an ASN.1 value
                   corresponding to the extension type identified by extnID */
                $map = $this->_getMapping($id);
                if (!is_bool($map)) {
                    $mapped = $asn1->asn1map($decoded[0], $map);
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
                } elseif ($map) {
                    $value = base64_encode($value);
                }
            }
        }
    }

    /**
     * Map extension values from extension-specific internal format to
     *   octet string.
     *
     * @param Array ref $root
     * @param String $path
     * @param Object $asn1
     * @access private
     */
    function _mapOutExtensions(&$root, $path, $asn1)
    {
        $extensions = &$this->_subArray($root, $path);

        if (is_array($extensions)) {
            $size = count($extensions);
            for ($i = 0; $i < $size; $i++) {
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
                                    // by default File_ASN1 will try to render qualifier as a FILE_ASN1_TYPE_IA5_STRING since it's
                                    // actual type is FILE_ASN1_TYPE_ANY
                                    $subvalue = new File_ASN1_Element($asn1->encodeDER($subvalue, $map));
                                }
                            }
                        }
                        break;
                    case 'id-ce-authorityKeyIdentifier': // use 00 as the serial number instead of an empty string
                        if (isset($value['authorityCertSerialNumber'])) {
                            if ($value['authorityCertSerialNumber']->toBytes() == '') {
                                $temp = chr((FILE_ASN1_CLASS_CONTEXT_SPECIFIC << 6) | 2) . "\1\0";
                                $value['authorityCertSerialNumber'] = new File_ASN1_Element($temp);
                            }
                        }
                }

                /* [extnValue] contains the DER encoding of an ASN.1 value
                   corresponding to the extension type identified by extnID */
                $map = $this->_getMapping($id);
                if (is_bool($map)) {
                    if (!$map) {
                        user_error($id . ' is not a currently supported extension', E_USER_NOTICE);
                        unset($extensions[$i]);
                    }
                } else {
                    $temp = $asn1->encodeDER($value, $map);
                    $value = base64_encode($temp);
                }
            }
        }
    }

    /**
     * Associate an extension ID to an extension mapping
     *
     * @param String $extnId
     * @access private
     * @return Mixed
     */
    function _getMapping($extnId)
    {
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
            case 'id-ce-subjectAltName':
                return $this->SubjectAltName;
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
                return true;

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
        }

        return false;
    }

    /**
     * Load an X.509 certificate as a certificate authority
     *
     * @param String $cert
     * @access public
     * @return Boolean
     */
    function loadCA($cert)
    {
        $olddn = $this->dn;
        $oldcert = $this->currentCert;
        $oldsigsubj = $this->signatureSubject;

        $cert = $this->loadX509($cert);
        if (!$cert) {
            $this->dn = $olddn;
            $this->currentCert = $oldcert;
            $this->signatureSubject = $oldsigsubj;

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
     * @param String $url
     * @access public
     * @return Boolean
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
            foreach ($names as $key => $value) {
                $value = str_replace(array('.', '*'), array('\.', '[^.]*'), $value);
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
     * @param Integer $date optional
     * @access public
     */
    function validateDate($date = NULL)
    {
        if (!is_array($this->currentCert) || !isset($this->currentCert['tbsCertificate'])) {
            return false;
        }

        if (!isset($date)) {
            $date = time();
        }

        $notBefore = $this->currentCert['tbsCertificate']['validity']['notBefore'];
        $notBefore = isset($notBefore['generalTime']) ? $notBefore['generalTime'] : $notBefore['utcTime'];

        $notAfter = $this->currentCert['tbsCertificate']['validity']['notAfter'];
        $notAfter = isset($notAfter['generalTime']) ? $notAfter['generalTime'] : $notAfter['utcTime'];

        switch (true) {
            case $date < @strtotime($notBefore):
            case $date > @strtotime($notAfter):
                return false;
        }

        return true;
    }

    /**
     * Validate a signature
     *
     * Works on X.509 certs, CSR's and CRL's.
     * Returns true if the signature is verified, false if it is not correct or NULL on error
     *
     * The behavior of this function is inspired by {@link http://php.net/openssl-verify openssl_verify}.
     *
     * @param Integer $options optional
     * @access public
     * @return Mixed
     */
    function validateSignature($options = 0)
    {
        if (!is_array($this->currentCert) || !isset($this->signatureSubject)) {
            return 0;
        }

        /* TODO:
           "emailAddress attribute values are not case-sensitive (e.g., "subscriber@example.com" is the same as "SUBSCRIBER@EXAMPLE.COM")."
            -- http://tools.ietf.org/html/rfc5280#section-4.1.2.6

           implement pathLenConstraint in the id-ce-basicConstraints extension */

        switch (true) {
            case isset($this->currentCert['tbsCertificate']):
                // self-signed cert
                if ($this->currentCert['tbsCertificate']['issuer'] === $this->currentCert['tbsCertificate']['subject']) {
                    $authorityKey = $this->getExtension('id-ce-authorityKeyIdentifier');
                    $subjectKeyID = $this->getExtension('id-ce-subjectKeyIdentifier');
                    switch (true) {
                        case !is_array($authorityKey):
                        case is_array($authorityKey) && isset($authorityKey['keyIdentifier']) && $authorityKey['keyIdentifier'] === $subjectKeyID:
                            $signingCert = $this->currentCert; // working cert
                    }
                }

                if (!empty($this->CAs)) {
                    for ($i = 0; $i < count($this->CAs); $i++) {
                        // even if the cert is a self-signed one we still want to see if it's a CA;
                        // if not, we'll conditionally return an error
                        $ca = $this->CAs[$i];
                        if ($this->currentCert['tbsCertificate']['issuer'] === $ca['tbsCertificate']['subject']) {
                            $authorityKey = $this->getExtension('id-ce-authorityKeyIdentifier');
                            $subjectKeyID = $this->getExtension('id-ce-subjectKeyIdentifier', $ca);
                            switch (true) {
                                case !is_array($authorityKey):
                                case is_array($authorityKey) && isset($authorityKey['keyIdentifier']) && $authorityKey['keyIdentifier'] === $subjectKeyID:
                                    $signingCert = $ca; // working cert
                                    break 2;
                            }
                        }
                    }
                    if (count($this->CAs) == $i && ($options & FILE_X509_VALIDATE_SIGNATURE_BY_CA)) {
                        return false;
                    }
                } elseif (!isset($signingCert) || ($options & FILE_X509_VALIDATE_SIGNATURE_BY_CA)) {
                    return false;
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
            case isset($this->currentCert['tbsCertList']):
                if (!empty($this->CAs)) {
                    for ($i = 0; $i < count($this->CAs); $i++) {
                        $ca = $this->CAs[$i];
                        if ($this->currentCert['tbsCertList']['issuer'] === $ca['tbsCertificate']['subject']) {
                            $authorityKey = $this->getExtension('id-ce-authorityKeyIdentifier');
                            $subjectKeyID = $this->getExtension('id-ce-subjectKeyIdentifier', $ca);
                            switch (true) {
                                case !is_array($authorityKey):
                                case is_array($authorityKey) && isset($authorityKey['keyIdentifier']) && $authorityKey['keyIdentifier'] === $subjectKeyID:
                                    $signingCert = $ca; // working cert
                                    break 2;
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
     * Returns true if the signature is verified, false if it is not correct or NULL on error
     *
     * @param String $publicKeyAlgorithm
     * @param String $publicKey
     * @param String $signatureAlgorithm
     * @param String $signature
     * @param String $signatureSubject
     * @access private
     * @return Integer
     */
    function _validateSignature($publicKeyAlgorithm, $publicKey, $signatureAlgorithm, $signature, $signatureSubject)
    {
        switch ($publicKeyAlgorithm) {
            case 'rsaEncryption':
                if (!class_exists('Crypt_RSA')) {
                    require_once('Crypt/RSA.php');
                }
                $rsa = new Crypt_RSA();
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
                        $rsa->setSignatureMode(CRYPT_RSA_SIGNATURE_PKCS1);

                        if (!@$rsa->verify($signatureSubject, $signature)) {
                            return false;
                        }
                        break;
                    default:
                        return NULL;
                }
                break;
            default:
                return NULL;
        }

        return true;
    }

    /**
     * Reformat public keys
     *
     * Reformats a public key to a format supported by phpseclib (if applicable)
     *
     * @param String $algorithm
     * @param String $key
     * @access private
     * @return String
     */
    function _reformatKey($algorithm, $key)
    {
        switch ($algorithm) {
            case 'rsaEncryption':
                return
                    "-----BEGIN PUBLIC KEY-----\r\n" .
                    // subjectPublicKey is stored as a bit string in X.509 certs.  the first byte of a bit string represents how many bits
                    // in the last byte should be ignored.  the following only supports non-zero stuff but as none of the X.509 certs Firefox
                    // uses as a cert authority actually use a non-zero bit I think it's safe to assume that none do.
                    chunk_split(base64_encode(substr(base64_decode($key), 1))) .
                    '-----END PUBLIC KEY-----';
            default:
                return $key;
        }
    }

    /**
     * "Normalizes" a Distinguished Name property
     *
     * @param String $propName
     * @access private
     * @return Mixed
     */
    function _translateDNProp($propName)
    {
        switch (strtolower($propName)) {
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
            case 'id-at-stateorprovinceName':
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
                return 'id-emailAddress';
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
            default:
                return false;
        }
    }

    /**
     * Set a Distinguished Name property
     *
     * @param String $propName
     * @param Mixed $propValue
     * @param String $type optional
     * @access public
     * @return Boolean
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
     * @param String $propName
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
    }

    /**
     * Get Distinguished Name properties
     *
     * @param String $propName
     * @param Array $dn optional
     * @param Boolean $withType optional
     * @return Mixed
     * @access public
     */
    function getDNProp($propName, $dn = NULL, $withType = false)
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

        $dn = $dn['rdnSequence'];
        $result = array();
        $asn1 = new File_ASN1();
        for ($i = 0; $i < count($dn); $i++) {
            if ($dn[$i][0]['type'] == $propName) {
                $v = $dn[$i][0]['value'];
                if (!$withType && is_array($v)) {
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
                }
                $result[] = $v;
            }
        }

        return $result;
    }

    /**
     * Set a Distinguished Name
     *
     * @param Mixed $dn
     * @param Boolean $merge optional
     * @param String $type optional
     * @access public
     * @return Boolean
     */
    function setDN($dn, $merge = false, $type = 'utf8String')
    {
        if (!$merge) {
            $this->dn = NULL;
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
        $results = preg_split('#((?:^|, *|/)(?:C=|O=|OU=|CN=|L=|ST=|SN=|postalCode=|streetAddress=|emailAddress=|serialNumber=|organizationalUnitName=|title=|description=|role=|x500UniqueIdentifier=))#', $dn, -1, PREG_SPLIT_DELIM_CAPTURE);
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
     * @param Mixed $format optional
     * @param Array $dn optional
     * @access public
     * @return Boolean
     */
    function getDN($format = FILE_X509_DN_ARRAY, $dn = NULL)
    {
        if (!isset($dn)) {
            $dn = isset($this->currentCert['tbsCertList']) ? $this->currentCert['tbsCertList']['issuer'] : $this->dn;
        }

        switch ((int) $format) {
            case FILE_X509_DN_ARRAY:
                return $dn;
            case FILE_X509_DN_ASN1:
                $asn1 = new File_ASN1();
                $asn1->loadOIDs($this->oids);
                $filters = array();
                $filters['rdnSequence']['value'] = array('type' => FILE_ASN1_TYPE_UTF8_STRING);
                $asn1->loadFilters($filters);
                return $asn1->encodeDER($dn, $this->Name);
            case FILE_X509_DN_OPENSSL:
                $dn = $this->getDN(FILE_X509_DN_STRING, $dn);
                if ($dn === false) {
                    return false;
                }
                $attrs = preg_split('#((?:^|, *|/)[a-z][a-z0-9]*=)#i', $dn, -1, PREG_SPLIT_DELIM_CAPTURE);
                $dn = array();
                for ($i = 1; $i < count($attrs); $i += 2) {
                    $prop = trim($attrs[$i], ', =/');
                    $value = $attrs[$i + 1];
                    if (!isset($dn[$prop])) {
                        $dn[$prop] = $value;
                    } else {
                        $dn[$prop] = array_merge((array) $dn[$prop], array($value));
                    }
                }
                return $dn;
            case FILE_X509_DN_CANON:
                //  No SEQUENCE around RDNs and all string values normalized as
                // trimmed lowercase UTF-8 with all spacing  as one blank.
                $asn1 = new File_ASN1();
                $asn1->loadOIDs($this->oids);
                $filters = array();
                $filters['value'] = array('type' => FILE_ASN1_TYPE_UTF8_STRING);
                $asn1->loadFilters($filters);
                $result = '';
                foreach ($dn['rdnSequence'] as $rdn) {
                    foreach ($rdn as &$attr) {
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
            case FILE_X509_DN_HASH:
                $dn = $this->getDN(FILE_X509_DN_CANON, $dn);
                if (!class_exists('Crypt_Hash')) {
                    require_once('Crypt/Hash.php');
                }
                $hash = new Crypt_Hash('sha1');
                $hash = $hash->hash($dn);
                extract(unpack('Vhash', $hash));
                return strtolower(bin2hex(pack('N', $hash)));
        }

        // Defaut is to return a string.
        $start = true;
        $output = '';
        $asn1 = new File_ASN1();
        foreach ($dn['rdnSequence'] as $field) {
            $prop = $field[0]['type'];
            $value = $field[0]['value'];

            $delim = ', ';
            switch ($prop) {
                case 'id-at-countryName':
                    $desc = 'C=';
                    break;
                case 'id-at-stateOrProvinceName':
                    $desc = 'ST=';
                    break;
                case 'id-at-organizationName':
                    $desc = 'O=';
                    break;
                case 'id-at-organizationalUnitName':
                    $desc = 'OU=';
                    break;
                case 'id-at-commonName':
                    $desc = 'CN=';
                    break;
                case 'id-at-localityName':
                    $desc = 'L=';
                    break;
                case 'id-at-surname':
                    $desc = 'SN=';
                    break;
                case 'id-at-uniqueIdentifier':
                    $delim = '/';
                    $desc = 'x500UniqueIdentifier=';
                    break;
                default:
                    $delim = '/';
                    $desc = preg_replace('#.+-([^-]+)$#', '$1',  $prop) . '=';
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
            }
            $output.= $desc . $value;
            $start = false;
        }

        return $output;
    }

    /**
     * Get the Distinguished Name for a certificate/crl issuer
     *
     * @param Integer $format optional
     * @access public
     * @return Mixed
     */
    function getIssuerDN($format = FILE_X509_DN_ARRAY)
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
     * @param Integer $format optional
     * @access public
     * @return Mixed
     */
    function getSubjectDN($format = FILE_X509_DN_ARRAY)
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
     * @param String $propName
     * @param Boolean $withType optional
     * @access public
     * @return Mixed
     */
    function getIssuerDNProp($propName, $withType = false)
    {
        switch (true) {
            case !isset($this->currentCert) || !is_array($this->currentCert):
                break;
            case isset($this->currentCert['tbsCertificate']):
                return $this->getDNProp($propname, $this->currentCert['tbsCertificate']['issuer'], $withType);
            case isset($this->currentCert['tbsCertList']):
                return $this->getDNProp($propname, $this->currentCert['tbsCertList']['issuer'], $withType);
        }

        return false;
    }

    /**
     * Get an individual Distinguished Name property for a certificate/csr subject
     *
     * @param String $propName
     * @param Boolean $withType optional
     * @access public
     * @return Mixed
     */
    function getSubjectDNProp($propName, $withType = false)
    {
        switch (true) {
            case !empty($this->dn):
                return $this->getDNProp($propName, NULL, $withType);
            case !isset($this->currentCert) || !is_array($this->currentCert):
                break;
            case isset($this->currentCert['tbsCertificate']):
                return $this->getDNProp($propName, $this->currentCert['tbsCertificate']['subject'], $withType);
            case isset($this->currentCert['certificationRequestInfo']):
                return $this->getDNProp($propname, $this->currentCert['certificationRequestInfo']['subject'], $withType);
        }

        return false;
    }

    /**
     * Set public key
     *
     * Key needs to be a Crypt_RSA object
     *
     * @param Object $key
     * @access public
     * @return Boolean
     */
    function setPublicKey($key)
    {
        $this->publicKey = $key;
    }

    /**
     * Set private key
     *
     * Key needs to be a Crypt_RSA object
     *
     * @param Object $key
     * @access public
     */
    function setPrivateKey($key)
    {
        $this->privateKey = $key;
    }

    /**
     * Gets the public key
     *
     * Returns a Crypt_RSA object or a false.
     *
     * @access public
     * @return Mixed
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
                if (!class_exists('Crypt_RSA')) {
                    require_once('Crypt/RSA.php');
                }
                $publicKey = new Crypt_RSA();
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
     * @param String $csr
     * @access public
     * @return Mixed
     */
    function loadCSR($csr)
    {
        // see http://tools.ietf.org/html/rfc2986

        $asn1 = new File_ASN1();

        $temp = preg_replace('#^(?:[^-].+[\r\n]+)+|-.+-|[\r\n]| #', '', $csr);
        $temp = preg_match('#^[a-zA-Z\d/+]*={0,2}$#', $temp) ? base64_decode($temp) : false;
        if ($temp != false) {
            $csr = $temp;
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

        $this->dn = $csr['certificationRequestInfo']['subject'];

        $this->signatureSubject = substr($orig, $decoded[0]['content'][0]['start'], $decoded[0]['content'][0]['length']);

        $algorithm = &$csr['certificationRequestInfo']['subjectPKInfo']['algorithm']['algorithm'];
        $key = &$csr['certificationRequestInfo']['subjectPKInfo']['subjectPublicKey'];
        $key = $this->_reformatKey($algorithm, $key);

        switch ($algorithm) {
            case 'rsaEncryption':
                if (!class_exists('Crypt_RSA')) {
                    require_once('Crypt/RSA.php');
                }
                $this->publicKey = new Crypt_RSA();
                $this->publicKey->loadKey($key);
                $this->publicKey->setPublicKey();
                break;
            default:
                $this->publicKey = NULL;
        }

        $this->currentKeyIdentifier = NULL;
        $this->currentCert = $csr;

        return $csr;
    }

    /**
     * Save CSR request
     *
     * @param Array $csr
     * @access public
     * @return String
     */
    function saveCSR($csr)
    {
        if (!is_array($csr) || !isset($csr['certificationRequestInfo'])) {
            return false;
        }

        switch ($csr['certificationRequestInfo']['subjectPKInfo']['algorithm']['algorithm']) {
            case 'rsaEncryption':
                $csr['certificationRequestInfo']['subjectPKInfo']['subjectPublicKey'] = 
                    base64_encode("\0" . base64_decode(preg_replace('#-.+-|[\r\n]#', '', $csr['certificationRequestInfo']['subjectPKInfo']['subjectPublicKey'])));
        }

        $asn1 = new File_ASN1();

        $asn1->loadOIDs($this->oids);

        $filters = array();
        $filters['certificationRequestInfo']['subject']['rdnSequence']['value'] = 
            array('type' => FILE_ASN1_TYPE_UTF8_STRING);

        $asn1->loadFilters($filters);

        $csr = $asn1->encodeDER($csr, $this->CertificationRequest);

        return "-----BEGIN CERTIFICATE REQUEST-----\r\n" . chunk_split(base64_encode($csr)) . '-----END CERTIFICATE REQUEST-----';
    }

    /**
     * Load a Certificate Revocation List
     *
     * @param String $crl
     * @access public
     * @return Mixed
     */
    function loadCRL($crl)
    {
        $asn1 = new File_ASN1();

        $temp = preg_replace('#^(?:[^-].+[\r\n]+)+|-.+-|[\r\n]| #', '', $csr);
        $temp = preg_match('#^[a-zA-Z\d/+]*={0,2}$#', $temp) ? base64_decode($temp) : false;
        if ($temp != false) {
            $crl = $temp;
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

        $this->_mapInExtensions($crl, 'tbsCertList/crlExtensions', $asn1);
        $rclist = &$this->_subArray($crl,'tbsCertList/revokedCertificates');
        if (is_array($rclist)) {
            foreach ($rclist as $i => $extension) {
                $this->_mapInExtensions($rclist, "$i/crlEntryExtensions", $asn1);
            }
        }

        $this->currentKeyIdentifier = NULL;
        $this->currentCert = $crl;

        return $crl;
    }

    /**
     * Save Certificate Revocation List.
     *
     * @param Array $crl
     * @access public
     * @return String
     */
    function saveCRL($crl)
    {
        if (!is_array($crl) || !isset($crl['tbsCertList'])) {
            return false;
        }

        $asn1 = new File_ASN1();

        $asn1->loadOIDs($this->oids);

        $filters = array();
        $filters['tbsCertList']['issuer']['rdnSequence']['value'] = 
        $filters['tbsCertList']['signature']['parameters'] = 
        $filters['signatureAlgorithm']['parameters'] = 
            array('type' => FILE_ASN1_TYPE_UTF8_STRING);

        if (empty($crl['tbsCertList']['signature']['parameters'])) {
            $filters['tbsCertList']['signature']['parameters'] = 
                array('type' => FILE_ASN1_TYPE_NULL);
        }

        if (empty($crl['signatureAlgorithm']['parameters'])) {
            $filters['signatureAlgorithm']['parameters'] = 
                array('type' => FILE_ASN1_TYPE_NULL);
        }

        $asn1->loadFilters($filters);

        $this->_mapOutExtensions($crl, 'tbsCertList/crlExtensions', $asn1);
        $rclist = &$this->_subArray($crl,'tbsCertList/revokedCertificates');
        if (is_array($rclist)) {
            foreach ($rclist as $i => $extension) {
                $this->_mapOutExtensions($rclist, "$i/crlEntryExtensions", $asn1);
            }
        }

        $crl = $asn1->encodeDER($crl, $this->CertificateList);

        return "-----BEGIN X509 CRL-----\r\n" . chunk_split(base64_encode($crl)) . '-----END X509 CRL-----';
    }

    /**
     * Sign an X.509 certificate
     *
     * $issuer's private key needs to be loaded.
     * $subject can be either an existing X.509 cert (if you want to resign it),
     * a CSR or something with the DN and public key explicitly set.
     *
     * @param File_X509 $issuer
     * @param File_X509 $subject
     * @param String $signatureAlgorithm optional
     * @access public
     * @return Mixed
     */
    function sign($issuer, $subject, $signatureAlgorithm = 'sha1WithRSAEncryption')
    {
        if (!is_object($issuer->privateKey) || empty($issuer->dn)) {
            return false;
        }

        if (isset($subject->publicKey) && !($subjectPublicKey = $subject->_formatSubjectPublicKey())) {
            return false;
        }

        $currentCert = isset($this->currentCert) ? $this->currentCert : NULL;
        $signatureSubject = isset($this->signatureSubject) ? $this->signatureSubject: NULL;

        if (isset($subject->currentCert) && is_array($subject->currentCert) && isset($subject->currentCert['tbsCertificate'])) {
            $this->currentCert = $subject->currentCert;
            $this->currentCert['tbsCertificate']['signature']['algorithm'] =
            $this->currentCert['signatureAlgorithm']['algorithm'] =
                $signatureAlgorithm;
            if (!empty($this->startDate)) {
                $this->currentCert['tbsCertificate']['validity']['notBefore']['generalTime'] = $this->startDate;
                unset($this->currentCert['tbsCertificate']['validity']['notBefore']['utcTime']);
            }
            if (!empty($this->endDate)) {
                $this->currentCert['tbsCertificate']['validity']['notAfter']['generalTime'] = $this->endDate;
                unset($this->currentCert['tbsCertificate']['validity']['notAfter']['utcTime']);
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
        } else if (isset($subject->currentCert) && is_array($subject->currentCert) && isset($subject->currentCert['tbsCertList'])) {
            return false;
        } else {
            if (!isset($subject->publicKey)) {
                return false;
            }

            $startDate = !empty($this->startDate) ? $this->startDate : @date('M j H:i:s Y T');
            $endDate = !empty($this->endDate) ? $this->endDate : @date('M j H:i:s Y T', strtotime('+1 year'));
            $serialNumber = !empty($this->serialNumber) ? $this->serialNumber : new Math_BigInteger();

            $this->currentCert = array(
                'tbsCertificate' =>
                    array(
                        'version' => 'v3',
                        'serialNumber' => $serialNumber, // $this->setserialNumber()
                        'signature' => array('algorithm' => $signatureAlgorithm),
                        'issuer' => false, // this is going to be overwritten later
                        'validity' => array(
                            'notBefore' => array('generalTime' => $startDate), // $this->setStartDate()
                            'notAfter' => array('generalTime' => $endDate)   // $this->setEndDate()
                        ),
                        'subject' => $subject->dn,
                        'subjectPublicKeyInfo' => $subjectPublicKey
                    ),
                'signatureAlgorithm' => array('algorithm' => $signatureAlgorithm),
                'signature'          => false // this is going to be overwritten later
            );
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
                )
            );
            //$extensions = &$this->currentCert['tbsCertificate']['extensions'];
            //if (isset($issuer->serialNumber)) {
            //    $extensions[count($extensions) - 1]['authorityCertSerialNumber'] = $issuer->serialNumber;
            //}
            //unset($extensions);
        }

        if (isset($subject->currentKeyIdentifier)) {
            $this->setExtension('id-ce-subjectKeyIdentifier', $subject->currentKeyIdentifier);
        }

        if (isset($subject->domains) && count($subject->domains) > 1) {
            $this->setExtension('id-ce-subjectAltName',
                array_map(array('File_X509', '_dnsName'), $subject->domains));
        }

        if ($this->caFlag) {
            $keyUsage = $this->getExtension('id-ce-keyUsage');
            if (!$keyUsage) {
                $keyUsage = array();
            }

            $this->setExtension('id-ce-keyUsage',
                array_values(array_unique(array_merge($keyUsage, array('cRLSign', 'keyCertSign'))))
            );

            $basicConstraints = $this->getExtension('id-ce-basicConstraints');
            if (!$basicConstraints) {
                $basicConstraints = array();
            }

            $this->setExtension('id-ce-basicConstraints',
                array_unique(array_merge(array('cA' => true), $basicConstraints)), true);

            if (!isset($subject->currentKeyIdentifier)) {
                $this->setExtension('id-ce-subjectKeyIdentifier', base64_encode($this->computeKeyIdentifier($this->currentCert)), false, false);
            }
        }

        // resync $this->signatureSubject
        // save $tbsCertificate in case there are any File_ASN1_Element objects in it
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
     * @return Mixed
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

        $currentCert = isset($this->currentCert) ? $this->currentCert : NULL;
        $signatureSubject = isset($this->signatureSubject) ? $this->signatureSubject: NULL;

        if (isset($this->currentCert) && is_array($this->currentCert) && isset($this->currentCert['certificationRequestInfo'])) {
            $this->currentCert['signatureAlgorithm']['algorithm'] =
                $signatureAlgorithm;
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
        // save $certificationRequestInfo in case there are any File_ASN1_Element objects in it
        $certificationRequestInfo = $this->currentCert['certificationRequestInfo'];
        $this->loadCSR($this->saveCSR($this->currentCert));

        $result = $this->_sign($this->privateKey, $signatureAlgorithm);
        $result['certificationRequestInfo'] = $certificationRequestInfo;

        $this->currentCert = $currentCert;
        $this->signatureSubject = $signatureSubject;

        return $result;
    }

    /**
     * Sign a CRL
     *
     * $issuer's private key needs to be loaded.
     *
     * @param File_X509 $issuer
     * @param File_X509 $crl
     * @param String $signatureAlgorithm optional
     * @access public
     * @return Mixed
     */
    function signCRL($issuer, $crl, $signatureAlgorithm = 'sha1WithRSAEncryption')
    {
        if (!is_object($issuer->privateKey) || empty($issuer->dn)) {
            return false;
        }

        $currentCert = isset($this->currentCert) ? $this->currentCert : NULL;
        $signatureSubject = isset($this->signatureSubject) ? $this->signatureSubject : NULL;
        $thisUpdate = !empty($this->startDate) ? $this->startDate : @date('M j H:i:s Y T');

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
                        'thisUpdate' => array('generalTime' => $thisUpdate) // $this->setStartDate()
                    ),
                'signatureAlgorithm' => array('algorithm' => $signatureAlgorithm),
                'signature'          => false // this is going to be overwritten later
            );
        }

        $tbsCertList = &$this->currentCert['tbsCertList'];
        $tbsCertList['issuer'] = $issuer->dn;
        $tbsCertList['thisUpdate'] = array('generalTime' => $thisUpdate);

        if (!empty($this->endDate)) {
            $tbsCertList['nextUpdate'] = array('generalTime' => $this->endDate); // $this->setEndDate()
        }
        else {
            unset($tbsCertList['nextUpdate']);
        }

        if (!empty($this->serialNumber)) {
            $crlNumber = $this->serialNumber;
        }
        else {
            $crlNumber = $this->getExtension('id-ce-cRLNumber');
            $crlNumber = $crlNumber !== false ? $crlNumber->add(new Math_BigInteger(1)) : NULL;
            }

        $this->removeExtension('id-ce-authorityKeyIdentifier');
        $this->removeExtension('id-ce-issuerAltName');

        // Be sure version >= v2 if some extension found.
        $version = isset($tbsCertList['version']) ? $tbsCertList['version'] : 0;
        if (!$version) {
            if (!empty($tbsCertList['crlExtensions'])) {
                $version = 1; // v2.
            }
            elseif (!empty($tbsCertList['revokedCertificates'])) {
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
                    )
                );
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
        // save $tbsCertList in case there are any File_ASN1_Element objects in it
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
     * @param Object $key
     * @param File_X509 $subject
     * @param String $signatureAlgorithm
     * @access public
     * @return Mixed
     */
    function _sign($key, $signatureAlgorithm)
    {
        switch (strtolower(get_class($key))) {
            case 'crypt_rsa':
                switch ($signatureAlgorithm) {
                    case 'md2WithRSAEncryption':
                    case 'md5WithRSAEncryption':
                    case 'sha1WithRSAEncryption':
                    case 'sha224WithRSAEncryption':
                    case 'sha256WithRSAEncryption':
                    case 'sha384WithRSAEncryption':
                    case 'sha512WithRSAEncryption':
                        $key->setHash(preg_replace('#WithRSAEncryption$#', '', $signatureAlgorithm));
                        $key->setSignatureMode(CRYPT_RSA_SIGNATURE_PKCS1);

                        $this->currentCert['signature'] = base64_encode("\0" . $key->sign($this->signatureSubject));
                        return $this->currentCert;
                }
            default:
                return false;
        }
    }

    /**
     * Set certificate start date
     *
     * @param String $date
     * @access public
     */
    function setStartDate($date)
    {
        $this->startDate = @date('M j H:i:s Y T', @strtotime($date));
    }

    /**
     * Set certificate end date
     *
     * @param String $date
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
            $asn1 = new File_ASN1();
            $temp = chr(FILE_ASN1_TYPE_GENERALIZED_TIME) . $asn1->_encodeLength(strlen($temp)) . $temp;
            $this->endDate = new File_ASN1_Element($temp);
        } else {
            $this->endDate = @date('M j H:i:s Y T', @strtotime($date));
        }
    }

    /**
     * Set Serial Number
     *
     * @param String $serial
     * @param $base optional
     * @access public
     */
    function setSerialNumber($serial, $base = -256)
    {
        $this->serialNumber = new Math_BigInteger($serial, $base);
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
     * Get a reference to a subarray
     *
     * @param array $root
     * @param String $path  absolute path with / as component separator
     * @param Boolean $create optional
     * @access private
     * @return array item ref or false
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
     * @param String $path optional absolute path with / as component separator
     * @param Boolean $create optional
     * @access private
     * @return array ref or false
     */
    function &_extensions(&$root, $path = NULL, $create = false)
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
     * @param String $id
     * @param String $path optional
     * @access private
     * @return Boolean
     */
    function _removeExtension($id, $path = NULL)
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
        return $result;
    }

    /**
     * Get an Extension
     *
     * Returns the extension if it exists and false if not
     *
     * @param String $id
     * @param Array $cert optional
     * @param String $path optional
     * @access private
     * @return Mixed
     */
    function _getExtension($id, $cert = NULL, $path = NULL)
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
     * @param String $path optional
     * @access private
     * @return Array
     */
    function _getExtensions($cert = NULL, $path = NULL)
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
     * @param String $id
     * @param Mixed $value
     * @param Boolean $critical optional
     * @param Boolean $replace optional
     * @param String $path optional
     * @access private
     * @return Boolean
     */
    function _setExtension($id, $value, $critical = false, $replace = true, $path = NULL)
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
     * Remove a certificate or CRL Extension
     *
     * @param String $id
     * @access public
     * @return Boolean
     */
    function removeExtension($id)
    {
        return $this->_removeExtension($id);
    }

    /**
     * Get a certificate or CRL Extension
     *
     * Returns the extension if it exists and false if not
     *
     * @param String $id
     * @param Array $cert optional
     * @access public
     * @return Mixed
     */
    function getExtension($id, $cert = NULL)
    {
        return $this->_getExtension($id, $cert);
    }

    /**
     * Returns a list of all extensions in use in certificate or CRL
     *
     * @param array $cert optional
     * @access public
     * @return Array
     */
    function getExtensions($cert = NULL)
    {
        return $this->_getExtensions($cert);
    }

    /**
     * Set a certificate or CRL Extension
     *
     * @param String $id
     * @param Mixed $value
     * @param Boolean $critical optional
     * @param Boolean $replace optional
     * @access public
     * @return Boolean
     */
    function setExtension($id, $value, $critical = false, $replace = true)
    {
        return $this->_setExtension($id, $value, $critical, $replace);
    }

    /**
     * Sets the subject key identifier
     *
     * This is used by the id-ce-authorityKeyIdentifier and the id-ce-subjectKeyIdentifier extensions.
     *
     * @param String $value
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
     * - File_X509 object with public or private key defined
     * - Certificate or CSR array
     * - File_ASN1_Element object
     * - PEM or DER string
     *
     * @param Mixed $key optional
     * @param Integer $method optional
     * @access public
     * @return String binary key identifier
     */
    function computeKeyIdentifier($key = NULL, $method = 1)
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
            case strtolower(get_class($key)) == 'file_asn1_element':
                $asn1 = new File_ASN1();
                $decoded = $asn1->decodeBER($cert);
                if (empty($decoded)) {
                    return false;
                }
                $key = $asn1->asn1map($decoded[0], array('type' => FILE_ASN1_TYPE_BIT_STRING));
                break;
            case strtolower(get_class($key)) == 'file_x509':
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
            default: // Should be a key object (i.e.: Crypt_RSA).
                $key = $key->getPublicKey(CRYPT_RSA_PUBLIC_FORMAT_PKCS1_RAW);
                break;
        }

        // If in PEM format, convert to binary.
        if (preg_match('#^-----BEGIN #', $key)) {
            $key = base64_decode(preg_replace('#-.+-|[\r\n]#', '', $key));
        }

        // Now we have the key string: compute its sha-1 sum.
        if (!class_exists('Crypt_Hash')) {
            require_once('Crypt/Hash.php');
        }
        $hash = new Crypt_Hash('sha1');
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
     * @return Array
     */
    function _formatSubjectPublicKey()
    {
        if (!isset($this->publicKey) || !is_object($this->publicKey)) {
            return false;
        }

        switch (strtolower(get_class($this->publicKey))) {
            case 'crypt_rsa':
                // the following two return statements do the same thing. i dunno.. i just prefer the later for some reason.
                // the former is a good example of how to do fuzzing on the public key
                //return new File_ASN1_Element(base64_decode(preg_replace('#-.+-|[\r\n]#', '', $this->publicKey->getPublicKey())));
                return array(
                    'algorithm' => array('algorithm' => 'rsaEncryption'),
                    'subjectPublicKey' => $this->publicKey->getPublicKey(CRYPT_RSA_PUBLIC_FORMAT_PKCS1_RAW)
                );
            default:
                return false;
        }
    }

    /**
     * Set the domain name's which the cert is to be valid for
     *
     * @access public
     * @return Array
     */
    function setDomain()
    {
        $this->domains = func_get_args();
        $this->removeDNProp('id-at-commonName');
        $this->setDNProp('id-at-commonName', $this->domains[0]);
    }

    /**
     * Helper function to build domain array
     *
     * @access private
     * @param String $domain
     * @return Array
     */
    function _dnsName($domain)
    {
        return array('dNSName' => $domain);
    }

    /**
     * Get the index of a revoked certificate.
     *
     * @param array $rclist
     * @param String $serial
     * @param Boolean $create optional
     * @access private
     * @return Integer or false
     */
    function _revokedCertificate(&$rclist, $serial, $create = false)
    {
        $serial = new Math_BigInteger($serial);

        foreach ($rclist as $i => $rc) {
            if (!($serial->compare($rc['userCertificate']))) {
                return $i;
            }
        }

        if (!$create) {
            return false;
        }

        $i = count($rclist);
        $rclist[] = array('userCertificate' => $serial,
                          'revocationDate'  => array('generalTime' => @date('M j H:i:s Y T')));
        return $i;
    }

    /**
     * Revoke a certificate.
     *
     * @param String $serial
     * @param String $date optional
     * @access public
     * @return Boolean
     */
    function revoke($serial, $date = NULL)
    {
        if (isset($this->currentCert['tbsCertList'])) {
            if (is_array($rclist = &$this->_subArray($this->currentCert, 'tbsCertList/revokedCertificates', true))) {
                if ($this->_revokedCertificate($rclist, $serial) === false) { // If not yet revoked
                    if (($i = $this->_revokedCertificate($rclist, $serial, true)) !== false) {

                        if (!empty($date)) {
                            $rclist[$i]['revocationDate'] = array('generalTime' => $date);
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
     * @param String $serial
     * @access public
     * @return Boolean
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
     * @param String $serial
     * @access public
     * @return Mixed
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
    function listRevoked($crl = NULL)
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
     * @param String $serial
     * @param String $id
     * @access public
     * @return Boolean
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
     * @param String $serial
     * @param String $id
     * @param Array $crl optional
     * @access public
     * @return Mixed
     */
    function getRevokedCertificateExtension($serial, $id, $crl = NULL)
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
     * @param String $serial
     * @param array $crl optional
     * @access public
     * @return Array
     */
    function getRevokedCertificateExtensions($serial, $crl = NULL)
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
     * @param String $serial
     * @param String $id
     * @param Mixed $value
     * @param Boolean $critical optional
     * @param Boolean $replace optional
     * @access public
     * @return Boolean
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
}
