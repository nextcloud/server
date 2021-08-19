<?php
/*
 * This file is part of the PHPASN1 library.
 *
 * Copyright © Friedrich Große <friedrich.grosse@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FG\ASN1;

class OID
{
    const RSA_ENCRYPTION                    = '1.2.840.113549.1.1.1';
    const MD5_WITH_RSA_ENCRYPTION           = '1.2.840.113549.1.1.4';
    const SHA1_WITH_RSA_SIGNATURE           = '1.2.840.113549.1.1.5';
    const SHA256_WITH_RSA_SIGNATURE         = '1.2.840.113549.1.1.11';
    const PKCS9_EMAIL                       = '1.2.840.113549.1.9.1';
    const PKCS9_UNSTRUCTURED_NAME           = '1.2.840.113549.1.9.2';
    const PKCS9_CONTENT_TYPE                = '1.2.840.113549.1.9.3';
    const PKCS9_MESSAGE_DIGEST              = '1.2.840.113549.1.9.4';
    const PKCS9_SIGNING_TIME                = '1.2.840.113549.1.9.5';
    const PKCS9_EXTENSION_REQUEST           = '1.2.840.113549.1.9.14';

    // certificate extension identifier
    const CERT_EXT_SUBJECT_DIRECTORY_ATTR   = '2.5.29.9';
    const CERT_EXT_SUBJECT_KEY_IDENTIFIER   = '2.5.29.14';
    const CERT_EXT_KEY_USAGE                = '2.5.29.15';
    const CERT_EXT_PRIVATE_KEY_USAGE_PERIOD = '2.5.29.16';
    const CERT_EXT_SUBJECT_ALT_NAME         = '2.5.29.17';
    const CERT_EXT_ISSUER_ALT_NAME          = '2.5.29.18';
    const CERT_EXT_BASIC_CONSTRAINTS        = '2.5.29.19';
    const CERT_EXT_CRL_NUMBER               = '2.5.29.20';
    const CERT_EXT_REASON_CODE              = '2.5.29.21';
    const CERT_EXT_INVALIDITY_DATE          = '2.5.29.24';
    const CERT_EXT_DELTA_CRL_INDICATOR      = '2.5.29.27';
    const CERT_EXT_ISSUING_DIST_POINT       = '2.5.29.28';
    const CERT_EXT_CERT_ISSUER              = '2.5.29.29';
    const CERT_EXT_NAME_CONSTRAINTS         = '2.5.29.30';
    const CERT_EXT_CRL_DISTRIBUTION_POINTS  = '2.5.29.31';
    const CERT_EXT_CERT_POLICIES            = '2.5.29.32';
    const CERT_EXT_AUTHORITY_KEY_IDENTIFIER = '2.5.29.35';
    const CERT_EXT_EXTENDED_KEY_USAGE       = '2.5.29.37';

    // standard certificate files
    const COMMON_NAME                       = '2.5.4.3';
    const SURNAME                           = '2.5.4.4';
    const SERIAL_NUMBER                     = '2.5.4.5';
    const COUNTRY_NAME                      = '2.5.4.6';
    const LOCALITY_NAME                     = '2.5.4.7';
    const STATE_OR_PROVINCE_NAME            = '2.5.4.8';
    const STREET_ADDRESS                    = '2.5.4.9';
    const ORGANIZATION_NAME                 = '2.5.4.10';
    const OU_NAME                           = '2.5.4.11';
    const TITLE                             = '2.5.4.12';
    const DESCRIPTION                       = '2.5.4.13';
    const POSTAL_ADDRESS                    = '2.5.4.16';
    const POSTAL_CODE                       = '2.5.4.17';
    const AUTHORITY_REVOCATION_LIST         = '2.5.4.38';

    const AUTHORITY_INFORMATION_ACCESS      = '1.3.6.1.5.5.7.1.1';

    /**
     * Returns the name of the given object identifier.
     *
     * Some OIDs are saved as class constants in this class.
     * If the wanted oidString is not among them, this method will
     * query http://oid-info.com for the right name.
     * This behavior can be suppressed by setting the second method parameter to false.
     *
     * @param string $oidString
     * @param bool $loadFromWeb
     *
     * @see self::loadFromWeb($oidString)
     *
     * @return string
     */
    public static function getName($oidString, $loadFromWeb = true)
    {
        switch ($oidString) {
            case self::RSA_ENCRYPTION:
                return 'RSA Encryption';
            case self::MD5_WITH_RSA_ENCRYPTION:
                return 'MD5 with RSA Encryption';
            case self::SHA1_WITH_RSA_SIGNATURE:
                return 'SHA-1 with RSA Signature';

            case self::PKCS9_EMAIL:
                return 'PKCS #9 Email Address';
            case self::PKCS9_UNSTRUCTURED_NAME:
                return 'PKCS #9 Unstructured Name';
            case self::PKCS9_CONTENT_TYPE:
                return 'PKCS #9 Content Type';
            case self::PKCS9_MESSAGE_DIGEST:
                return 'PKCS #9 Message Digest';
            case self::PKCS9_SIGNING_TIME:
                return 'PKCS #9 Signing Time';

            case self::COMMON_NAME:
                return 'Common Name';
            case self::SURNAME:
                return 'Surname';
            case self::SERIAL_NUMBER:
                return 'Serial Number';
            case self::COUNTRY_NAME:
                return 'Country Name';
            case self::LOCALITY_NAME:
                return 'Locality Name';
            case self::STATE_OR_PROVINCE_NAME:
                return 'State or Province Name';
            case self::STREET_ADDRESS:
                return 'Street Address';
            case self::ORGANIZATION_NAME:
                return 'Organization Name';
            case self::OU_NAME:
                return 'Organization Unit Name';
            case self::TITLE:
                return 'Title';
            case self::DESCRIPTION:
                return 'Description';
            case self::POSTAL_ADDRESS:
                return 'Postal Address';
            case self::POSTAL_CODE:
                return 'Postal Code';
            case self::AUTHORITY_REVOCATION_LIST:
                return 'Authority Revocation List';

            case self::CERT_EXT_SUBJECT_DIRECTORY_ATTR:
                return 'Subject directory attributes';
            case self::CERT_EXT_SUBJECT_KEY_IDENTIFIER:
                return 'Subject key identifier';
            case self::CERT_EXT_KEY_USAGE:
                return 'Key usage certificate extension';
            case self::CERT_EXT_PRIVATE_KEY_USAGE_PERIOD:
                return 'Private key usage';
            case self::CERT_EXT_SUBJECT_ALT_NAME:
                return 'Subject alternative name (SAN)';
            case self::CERT_EXT_ISSUER_ALT_NAME:
                return 'Issuer alternative name';
            case self::CERT_EXT_BASIC_CONSTRAINTS:
                return 'Basic constraints';
            case self::CERT_EXT_CRL_NUMBER:
                return 'CRL number';
            case self::CERT_EXT_REASON_CODE:
                return 'Reason code';
            case self::CERT_EXT_INVALIDITY_DATE:
                return 'Invalidity code';
            case self::CERT_EXT_DELTA_CRL_INDICATOR:
                return 'Delta CRL indicator';
            case self::CERT_EXT_ISSUING_DIST_POINT:
                return 'Issuing distribution point';
            case self::CERT_EXT_CERT_ISSUER:
                return 'Certificate issuer';
            case self::CERT_EXT_NAME_CONSTRAINTS:
                return 'Name constraints';
            case self::CERT_EXT_CRL_DISTRIBUTION_POINTS:
                return 'CRL distribution points';
            case self::CERT_EXT_CERT_POLICIES:
                return 'Certificate policies ';
            case self::CERT_EXT_AUTHORITY_KEY_IDENTIFIER:
                return 'Authority key identifier';
            case self::CERT_EXT_EXTENDED_KEY_USAGE:
                return 'Extended key usage';
            case self::AUTHORITY_INFORMATION_ACCESS:
                return 'Certificate Authority Information Access (AIA)';

            default:
                if ($loadFromWeb) {
                    return self::loadFromWeb($oidString);
                } else {
                    return $oidString;
                }
        }
    }

    public static function loadFromWeb($oidString)
    {
        $ch = curl_init("http://oid-info.com/get/{$oidString}");

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);

        $contents = curl_exec($ch);
        curl_close($ch);

        // This pattern needs to be updated as soon as the website layout of oid-info.com changes
        preg_match_all('#<tt>(.+)\(\d+\)</tt>#si', $contents, $oidName);

        if (empty($oidName[1])) {
            return "{$oidString} (unknown)";
        }

        $oidName = ucfirst(strtolower(preg_replace('/([A-Z][a-z])/', ' $1', $oidName[1][0])));
        $oidName = str_replace('-', ' ', $oidName);

        return "{$oidName} ({$oidString})";
    }
}
