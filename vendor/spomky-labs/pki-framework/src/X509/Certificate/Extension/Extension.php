<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\X509\Certificate\Extension;

use SpomkyLabs\Pki\ASN1\Element;
use SpomkyLabs\Pki\ASN1\Type\Constructed\Sequence;
use SpomkyLabs\Pki\ASN1\Type\Primitive\Boolean;
use SpomkyLabs\Pki\ASN1\Type\Primitive\ObjectIdentifier;
use SpomkyLabs\Pki\ASN1\Type\Primitive\OctetString;
use Stringable;
use function array_key_exists;

/**
 * Base class for certificate extensions.
 *
 * @see https://tools.ietf.org/html/rfc5280#section-4.2
 * @see https://tools.ietf.org/html/rfc5280#section-4.1
 */
abstract class Extension implements Stringable
{
    // OID's from standard certificate extensions
    public const OID_OBSOLETE_AUTHORITY_KEY_IDENTIFIER = '2.5.29.1';

    public const OID_OBSOLETE_KEY_ATTRIBUTES = '2.5.29.2';

    public const OID_OBSOLETE_CERTIFICATE_POLICIES = '2.5.29.3';

    public const OID_OBSOLETE_KEY_USAGE_RESTRICTION = '2.5.29.4';

    public const OID_OBSOLETE_POLICY_MAPPING = '2.5.29.5';

    public const OID_OBSOLETE_SUBTREES_CONSTRAINT = '2.5.29.6';

    public const OID_OBSOLETE_SUBJECT_ALT_NAME = '2.5.29.7';

    public const OID_OBSOLETE_ISSUER_ALT_NAME = '2.5.29.8';

    public const OID_SUBJECT_DIRECTORY_ATTRIBUTES = '2.5.29.9';

    public const OID_OBSOLETE_BASIC_CONSTRAINTS = '2.5.29.10';

    public const OID_SUBJECT_KEY_IDENTIFIER = '2.5.29.14';

    public const OID_KEY_USAGE = '2.5.29.15';

    public const OID_PRIVATE_KEY_USAGE_PERIOD = '2.5.29.16';

    public const OID_SUBJECT_ALT_NAME = '2.5.29.17';

    public const OID_ISSUER_ALT_NAME = '2.5.29.18';

    public const OID_BASIC_CONSTRAINTS = '2.5.29.19';

    public const OID_CRL_NUMBER = '2.5.29.20';

    public const OID_REASON_CODE = '2.5.29.21';

    public const OID_OBSOLETE_EXPIRATION_DATE = '2.5.29.22';

    public const OID_INSTRUCTION_CODE = '2.5.29.23';

    public const OID_INVALIDITY_DATE = '2.5.29.24';

    public const OID_OBSOLETE_CRL_DISTRIBUTION_POINTS = '2.5.29.25';

    public const OID_OBSOLETE_ISSUING_DISTRIBUTION_POINT = '2.5.29.26';

    public const OID_DELTA_CRL_INDICATOR = '2.5.29.27';

    public const OID_ISSUING_DISTRIBUTION_POINT = '2.5.29.28';

    public const OID_CERTIFICATE_ISSUER = '2.5.29.29';

    public const OID_NAME_CONSTRAINTS = '2.5.29.30';

    public const OID_CRL_DISTRIBUTION_POINTS = '2.5.29.31';

    public const OID_CERTIFICATE_POLICIES = '2.5.29.32';

    public const OID_POLICY_MAPPINGS = '2.5.29.33';

    public const OID_OBSOLETE_POLICY_CONSTRAINTS = '2.5.29.34';

    public const OID_AUTHORITY_KEY_IDENTIFIER = '2.5.29.35';

    public const OID_POLICY_CONSTRAINTS = '2.5.29.36';

    public const OID_EXT_KEY_USAGE = '2.5.29.37';

    public const OID_AUTHORITY_ATTRIBUTE_IDENTIFIER = '2.5.29.38';

    public const OID_ROLE_SPEC_CERT_IDENTIFIER = '2.5.29.39';

    public const OID_CRL_STREAM_IDENTIFIER = '2.5.29.40';

    public const OID_BASIC_ATT_CONSTRAINTS = '2.5.29.41';

    public const OID_DELEGATED_NAME_CONSTRAINTS = '2.5.29.42';

    public const OID_TIME_SPECIFICATION = '2.5.29.43';

    public const OID_CRL_SCOPE = '2.5.29.44';

    public const OID_STATUS_REFERRALS = '2.5.29.45';

    public const OID_FRESHEST_CRL = '2.5.29.46';

    public const OID_ORDERED_LIST = '2.5.29.47';

    public const OID_ATTRIBUTE_DESCRIPTOR = '2.5.29.48';

    public const OID_USER_NOTICE = '2.5.29.49';

    public const OID_SOA_IDENTIFIER = '2.5.29.50';

    public const OID_BASE_UPDATE_TIME = '2.5.29.51';

    public const OID_ACCEPTABLE_CERT_POLICIES = '2.5.29.52';

    public const OID_DELTA_INFO = '2.5.29.53';

    public const OID_INHIBIT_ANY_POLICY = '2.5.29.54';

    public const OID_TARGET_INFORMATION = '2.5.29.55';

    public const OID_NO_REV_AVAIL = '2.5.29.56';

    public const OID_ACCEPTABLE_PRIVILEGE_POLICIES = '2.5.29.57';

    public const OID_TO_BE_REVOKED = '2.5.29.58';

    public const OID_REVOKED_GROUPS = '2.5.29.59';

    public const OID_EXPIRED_CERTS_ON_CRL = '2.5.29.60';

    public const OID_INDIRECT_ISSUER = '2.5.29.61';

    public const OID_NO_ASSERTION = '2.5.29.62';

    public const OID_AA_ISSUING_DISTRIBUTION_POINT = '2.5.29.63';

    public const OID_ISSUED_ON_BEHALF_OF = '2.5.29.64';

    public const OID_SINGLE_USE = '2.5.29.65';

    public const OID_GROUP_AC = '2.5.29.66';

    public const OID_ALLOWED_ATT_ASS = '2.5.29.67';

    public const OID_ATTRIBUTE_MAPPINGS = '2.5.29.68';

    public const OID_HOLDER_NAME_CONSTRAINTS = '2.5.29.69';

    // OID's from private certificate extensions arc
    public const OID_AUTHORITY_INFORMATION_ACCESS = '1.3.6.1.5.5.7.1.1';

    public const OID_AA_CONTROLS = '1.3.6.1.5.5.7.1.6';

    public const OID_SUBJECT_INFORMATION_ACCESS = '1.3.6.1.5.5.7.1.11';

    public const OID_LOGOTYPE = '1.3.6.1.5.5.7.1.12';

    /**
     * Mapping from extension ID to implementation class name.
     *
     * @internal
     *
     * @var array<string, string>
     */
    private const MAP_OID_TO_CLASS = [
        self::OID_AUTHORITY_KEY_IDENTIFIER => AuthorityKeyIdentifierExtension::class,
        self::OID_SUBJECT_KEY_IDENTIFIER => SubjectKeyIdentifierExtension::class,
        self::OID_KEY_USAGE => KeyUsageExtension::class,
        self::OID_CERTIFICATE_POLICIES => CertificatePoliciesExtension::class,
        self::OID_POLICY_MAPPINGS => PolicyMappingsExtension::class,
        self::OID_SUBJECT_ALT_NAME => SubjectAlternativeNameExtension::class,
        self::OID_ISSUER_ALT_NAME => IssuerAlternativeNameExtension::class,
        self::OID_SUBJECT_DIRECTORY_ATTRIBUTES => SubjectDirectoryAttributesExtension::class,
        self::OID_BASIC_CONSTRAINTS => BasicConstraintsExtension::class,
        self::OID_NAME_CONSTRAINTS => NameConstraintsExtension::class,
        self::OID_POLICY_CONSTRAINTS => PolicyConstraintsExtension::class,
        self::OID_EXT_KEY_USAGE => ExtendedKeyUsageExtension::class,
        self::OID_CRL_DISTRIBUTION_POINTS => CRLDistributionPointsExtension::class,
        self::OID_INHIBIT_ANY_POLICY => InhibitAnyPolicyExtension::class,
        self::OID_FRESHEST_CRL => FreshestCRLExtension::class,
        self::OID_NO_REV_AVAIL => NoRevocationAvailableExtension::class,
        self::OID_TARGET_INFORMATION => TargetInformationExtension::class,
        self::OID_AUTHORITY_INFORMATION_ACCESS => AuthorityInformationAccessExtension::class,
        self::OID_AA_CONTROLS => AAControlsExtension::class,
        self::OID_SUBJECT_INFORMATION_ACCESS => SubjectInformationAccessExtension::class,
    ];

    /**
     * Mapping from extensions ID to short name.
     *
     * @internal
     *
     * @var array<string, string>
     */
    private const MAP_OID_TO_NAME = [
        self::OID_AUTHORITY_KEY_IDENTIFIER => 'authorityKeyIdentifier',
        self::OID_SUBJECT_KEY_IDENTIFIER => 'subjectKeyIdentifier',
        self::OID_KEY_USAGE => 'keyUsage',
        self::OID_PRIVATE_KEY_USAGE_PERIOD => 'privateKeyUsagePeriod',
        self::OID_CERTIFICATE_POLICIES => 'certificatePolicies',
        self::OID_POLICY_MAPPINGS => 'policyMappings',
        self::OID_SUBJECT_ALT_NAME => 'subjectAltName',
        self::OID_ISSUER_ALT_NAME => 'issuerAltName',
        self::OID_SUBJECT_DIRECTORY_ATTRIBUTES => 'subjectDirectoryAttributes',
        self::OID_BASIC_CONSTRAINTS => 'basicConstraints',
        self::OID_NAME_CONSTRAINTS => 'nameConstraints',
        self::OID_POLICY_CONSTRAINTS => 'policyConstraints',
        self::OID_EXT_KEY_USAGE => 'extKeyUsage',
        self::OID_CRL_DISTRIBUTION_POINTS => 'cRLDistributionPoints',
        self::OID_INHIBIT_ANY_POLICY => 'inhibitAnyPolicy',
        self::OID_FRESHEST_CRL => 'freshestCRL',
        self::OID_NO_REV_AVAIL => 'noRevAvail',
        self::OID_TARGET_INFORMATION => 'targetInformation',
        self::OID_AUTHORITY_INFORMATION_ACCESS => 'authorityInfoAccess',
        self::OID_AA_CONTROLS => 'aaControls',
        self::OID_SUBJECT_INFORMATION_ACCESS => 'subjectInfoAccess',
        self::OID_LOGOTYPE => 'logotype',
    ];

    /**
     * @param string $oid Extension OID
     * @param bool $critical Whether extension is critical
     */
    protected function __construct(
        private readonly string $oid,
        private readonly bool $critical
    ) {
    }

    public function __toString(): string
    {
        return $this->extensionName();
    }

    /**
     * Initialize from ASN.1.
     */
    public static function fromASN1(Sequence $seq): self
    {
        $idx = 0;
        $extnID = $seq->at($idx++)
            ->asObjectIdentifier()
            ->oid();
        $critical = false;
        if ($seq->has($idx, Element::TYPE_BOOLEAN)) {
            $critical = $seq->at($idx++)
                ->asBoolean()
                ->value();
        }
        $data = $seq->at($idx)
            ->asOctetString()
            ->string();
        if (array_key_exists($extnID, self::MAP_OID_TO_CLASS)) {
            $cls = self::MAP_OID_TO_CLASS[$extnID];
            return $cls::fromDER($data, $critical);
        }
        return UnknownExtension::fromRawString($extnID, $critical, $data);
    }

    /**
     * Get extension OID.
     */
    public function oid(): string
    {
        return $this->oid;
    }

    /**
     * Check whether extension is critical.
     */
    public function isCritical(): bool
    {
        return $this->critical;
    }

    /**
     * Generate ASN.1 structure.
     */
    public function toASN1(): Sequence
    {
        $elements = [ObjectIdentifier::create($this->oid)];
        if ($this->critical) {
            $elements[] = Boolean::create(true);
        }
        $elements[] = $this->extnValue();
        return Sequence::create(...$elements);
    }

    /**
     * Get short name of the extension.
     */
    public function extensionName(): string
    {
        if (array_key_exists($this->oid, self::MAP_OID_TO_NAME)) {
            return self::MAP_OID_TO_NAME[$this->oid];
        }
        return $this->oid();
    }

    /**
     * Get ASN.1 structure of the extension value.
     */
    abstract protected function valueASN1(): Element;

    /**
     * Parse extension value from DER.
     *
     * @param string $data DER data
     * @param bool $critical Whether extension is critical
     */
    abstract protected static function fromDER(string $data, bool $critical): static;

    /**
     * Get the extnValue element.
     */
    protected function extnValue(): OctetString
    {
        return OctetString::create($this->valueASN1()->toDER());
    }
}
