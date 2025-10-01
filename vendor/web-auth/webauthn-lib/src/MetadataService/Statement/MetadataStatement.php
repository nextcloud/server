<?php

declare(strict_types=1);

namespace Webauthn\MetadataService\Statement;

use JsonSerializable;
use Webauthn\Exception\MetadataStatementLoadingException;
use Webauthn\MetadataService\CertificateChain\CertificateToolbox;
use Webauthn\MetadataService\ValueFilter;
use function array_key_exists;
use function is_array;
use function is_string;
use const JSON_THROW_ON_ERROR;

class MetadataStatement implements JsonSerializable
{
    use ValueFilter;

    final public const KEY_PROTECTION_SOFTWARE = 'software';

    final public const KEY_PROTECTION_SOFTWARE_INT = 0x0001;

    final public const KEY_PROTECTION_HARDWARE = 'hardware';

    final public const KEY_PROTECTION_HARDWARE_INT = 0x0002;

    final public const KEY_PROTECTION_TEE = 'tee';

    final public const KEY_PROTECTION_TEE_INT = 0x0004;

    final public const KEY_PROTECTION_SECURE_ELEMENT = 'secure_element';

    final public const KEY_PROTECTION_SECURE_ELEMENT_INT = 0x0008;

    final public const KEY_PROTECTION_REMOTE_HANDLE = 'remote_handle';

    final public const KEY_PROTECTION_REMOTE_HANDLE_INT = 0x0010;

    final public const KEY_PROTECTION_TYPES = [
        self::KEY_PROTECTION_SOFTWARE,
        self::KEY_PROTECTION_HARDWARE,
        self::KEY_PROTECTION_TEE,
        self::KEY_PROTECTION_SECURE_ELEMENT,
        self::KEY_PROTECTION_REMOTE_HANDLE,
    ];

    final public const KEY_PROTECTION_TYPES_INT = [
        self::KEY_PROTECTION_SOFTWARE_INT,
        self::KEY_PROTECTION_HARDWARE_INT,
        self::KEY_PROTECTION_TEE_INT,
        self::KEY_PROTECTION_SECURE_ELEMENT_INT,
        self::KEY_PROTECTION_REMOTE_HANDLE_INT,
    ];

    final public const MATCHER_PROTECTION_SOFTWARE = 'software';

    final public const MATCHER_PROTECTION_SOFTWARE_INT = 0x0001;

    final public const MATCHER_PROTECTION_TEE = 'tee';

    final public const MATCHER_PROTECTION_TEE_INT = 0x0002;

    final public const MATCHER_PROTECTION_ON_CHIP = 'on_chip';

    final public const MATCHER_PROTECTION_ON_CHIP_INT = 0x0004;

    final public const MATCHER_PROTECTION_TYPES = [
        self::MATCHER_PROTECTION_SOFTWARE,
        self::MATCHER_PROTECTION_TEE,
        self::MATCHER_PROTECTION_ON_CHIP,
    ];

    final public const MATCHER_PROTECTION_TYPES_INT = [
        self::MATCHER_PROTECTION_SOFTWARE_INT,
        self::MATCHER_PROTECTION_TEE_INT,
        self::MATCHER_PROTECTION_ON_CHIP_INT,
    ];

    final public const ATTACHMENT_HINT_INTERNAL = 'internal';

    final public const ATTACHMENT_HINT_EXTERNAL = 'external';

    final public const ATTACHMENT_HINT_WIRED = 'wired';

    final public const ATTACHMENT_HINT_WIRELESS = 'wireless';

    final public const ATTACHMENT_HINT_NFC = 'nfc';

    final public const ATTACHMENT_HINT_BLUETOOTH = 'bluetooth';

    final public const ATTACHMENT_HINT_NETWORK = 'network';

    final public const ATTACHMENT_HINT_READY = 'ready';

    final public const ATTACHMENT_HINT_WIFI_DIRECT = 'wifi_direct';

    final public const TRANSACTION_CONFIRMATION_DISPLAY_ANY = 'any';

    final public const TRANSACTION_CONFIRMATION_DISPLAY_PRIVILEGED_SOFTWARE = 'privileged_software';

    final public const TRANSACTION_CONFIRMATION_DISPLAY_TEE = 'tee';

    final public const TRANSACTION_CONFIRMATION_DISPLAY_HARDWARE = 'hardware';

    final public const TRANSACTION_CONFIRMATION_DISPLAY_REMOTE = 'remote';

    final public const ALG_SIGN_SECP256R1_ECDSA_SHA256_RAW = 'secp256r1_ecdsa_sha256_raw';

    final public const ALG_SIGN_SECP256R1_ECDSA_SHA256_DER = 'secp256r1_ecdsa_sha256_der';

    final public const ALG_SIGN_RSASSA_PSS_SHA256_RAW = 'rsassa_pss_sha256_raw';

    final public const ALG_SIGN_RSASSA_PSS_SHA256_DER = 'rsassa_pss_sha256_der';

    final public const ALG_SIGN_SECP256K1_ECDSA_SHA256_RAW = 'secp256k1_ecdsa_sha256_raw';

    final public const ALG_SIGN_SECP256K1_ECDSA_SHA256_DER = 'secp256k1_ecdsa_sha256_der';

    final public const ALG_SIGN_SM2_SM3_RAW = 'sm2_sm3_raw';

    final public const ALG_SIGN_RSA_EMSA_PKCS1_SHA256_RAW = 'rsa_emsa_pkcs1_sha256_raw';

    final public const ALG_SIGN_RSA_EMSA_PKCS1_SHA256_DER = 'rsa_emsa_pkcs1_sha256_der';

    final public const ALG_SIGN_RSASSA_PSS_SHA384_RAW = 'rsassa_pss_sha384_raw';

    final public const ALG_SIGN_RSASSA_PSS_SHA512_RAW = 'rsassa_pss_sha256_raw';

    final public const ALG_SIGN_RSASSA_PKCSV15_SHA256_RAW = 'rsassa_pkcsv15_sha256_raw';

    final public const ALG_SIGN_RSASSA_PKCSV15_SHA384_RAW = 'rsassa_pkcsv15_sha384_raw';

    final public const ALG_SIGN_RSASSA_PKCSV15_SHA512_RAW = 'rsassa_pkcsv15_sha512_raw';

    final public const ALG_SIGN_RSASSA_PKCSV15_SHA1_RAW = 'rsassa_pkcsv15_sha1_raw';

    final public const ALG_SIGN_SECP384R1_ECDSA_SHA384_RAW = 'secp384r1_ecdsa_sha384_raw';

    final public const ALG_SIGN_SECP521R1_ECDSA_SHA512_RAW = 'secp512r1_ecdsa_sha256_raw';

    final public const ALG_SIGN_ED25519_EDDSA_SHA256_RAW = 'ed25519_eddsa_sha512_raw';

    final public const ALG_KEY_ECC_X962_RAW = 'ecc_x962_raw';

    final public const ALG_KEY_ECC_X962_DER = 'ecc_x962_der';

    final public const ALG_KEY_RSA_2048_RAW = 'rsa_2048_raw';

    final public const ALG_KEY_RSA_2048_DER = 'rsa_2048_der';

    final public const ALG_KEY_COSE = 'cose';

    final public const ATTESTATION_BASIC_FULL = 'basic_full';

    final public const ATTESTATION_BASIC_SURROGATE = 'basic_surrogate';

    /**
     * @deprecated since 4.2.0 and will be removed in 5.0.0. The ECDAA Trust Anchor does no longer exist in Webauthn specification.
     * @infection-ignore-all
     */
    final public const ATTESTATION_ECDAA = 'ecdaa';

    final public const ATTESTATION_ATTCA = 'attca';

    final public const ATTESTATION_ANONCA = 'anonca';

    public readonly AuthenticatorGetInfo $authenticatorGetInfo;

    /**
     * @param Version[] $upv
     * @param string[] $authenticationAlgorithms
     * @param string[] $publicKeyAlgAndEncodings
     * @param string[] $attestationTypes
     * @param VerificationMethodANDCombinations[] $userVerificationDetails
     * @param string[] $matcherProtection
     * @param string[] $tcDisplay
     * @param string[] $attestationRootCertificates
     * @param string[] $attestationCertificateKeyIdentifiers
     * @param string[] $keyProtection
     * @param string[] $attachmentHint
     * @param EcdaaTrustAnchor[] $ecdaaTrustAnchors
     * @param ExtensionDescriptor[] $supportedExtensions
     */
    public function __construct(
        public readonly string $description,
        public readonly int $authenticatorVersion,
        public readonly string $protocolFamily,
        public readonly int $schema,
        public readonly array $upv,
        public readonly array $authenticationAlgorithms,
        public readonly array $publicKeyAlgAndEncodings,
        public readonly array $attestationTypes,
        public readonly array $userVerificationDetails,
        public readonly array $matcherProtection,
        public readonly array $tcDisplay,
        public readonly array $attestationRootCertificates,
        public readonly ?AlternativeDescriptions $alternativeDescriptions = null,
        public ?string $legalHeader = null,
        public ?string $aaid = null,
        public ?string $aaguid = null,
        public array $attestationCertificateKeyIdentifiers = [],
        public array $keyProtection = [],
        public ?bool $isKeyRestricted = null,
        public ?bool $isFreshUserVerificationRequired = null,
        public ?int $cryptoStrength = null,
        public array $attachmentHint = [],
        public ?string $tcDisplayContentType = null,
        public array $tcDisplayPNGCharacteristics = [],
        public array $ecdaaTrustAnchors = [],
        public ?string $icon = null,
        public array $supportedExtensions = [],
        ?AuthenticatorGetInfo $authenticatorGetInfo = null,
    ) {
        $this->authenticatorGetInfo = $authenticatorGetInfo ?? AuthenticatorGetInfo::create($attestationTypes);
    }

    public static function create(
        string $description,
        int $authenticatorVersion,
        string $protocolFamily,
        int $schema,
        array $upv,
        array $authenticationAlgorithms,
        array $publicKeyAlgAndEncodings,
        array $attestationTypes,
        array $userVerificationDetails,
        array $matcherProtection,
        array $tcDisplay,
        array $attestationRootCertificates,
        array $alternativeDescriptions = [],
        ?string $legalHeader = null,
        ?string $aaid = null,
        ?string $aaguid = null,
        array $attestationCertificateKeyIdentifiers = [],
        array $keyProtection = [],
        ?bool $isKeyRestricted = null,
        ?bool $isFreshUserVerificationRequired = null,
        ?int $cryptoStrength = null,
        array $attachmentHint = [],
        ?string $tcDisplayContentType = null,
        array $tcDisplayPNGCharacteristics = [],
        array $ecdaaTrustAnchors = [],
        ?string $icon = null,
        array $supportedExtensions = [],
        ?AuthenticatorGetInfo $authenticatorGetInfo = null,
    ): self {
        return new self(
            $description,
            $authenticatorVersion,
            $protocolFamily,
            $schema,
            $upv,
            $authenticationAlgorithms,
            $publicKeyAlgAndEncodings,
            $attestationTypes,
            $userVerificationDetails,
            $matcherProtection,
            $tcDisplay,
            $attestationRootCertificates,
            AlternativeDescriptions::create($alternativeDescriptions),
            $legalHeader,
            $aaid,
            $aaguid,
            $attestationCertificateKeyIdentifiers,
            $keyProtection,
            $isKeyRestricted,
            $isFreshUserVerificationRequired,
            $cryptoStrength,
            $attachmentHint,
            $tcDisplayContentType,
            $tcDisplayPNGCharacteristics,
            $ecdaaTrustAnchors,
            $icon,
            $supportedExtensions,
            $authenticatorGetInfo,
        );
    }

    /**
     * @deprecated since 4.7.0. Please use the symfony/serializer for converting the object.
     * @infection-ignore-all
     */
    public static function createFromString(string $statement): self
    {
        $data = json_decode($statement, true, flags: JSON_THROW_ON_ERROR);

        return self::createFromArray($data);
    }

    /**
     * @deprecated since 4.7.0. Please use the property directly.
     * @infection-ignore-all
     */
    public function getLegalHeader(): ?string
    {
        return $this->legalHeader;
    }

    /**
     * @deprecated since 4.7.0. Please use the property directly.
     * @infection-ignore-all
     */
    public function getAaid(): ?string
    {
        return $this->aaid;
    }

    /**
     * @deprecated since 4.7.0. Please use the property directly.
     * @infection-ignore-all
     */
    public function getAaguid(): ?string
    {
        return $this->aaguid;
    }

    public function isKeyRestricted(): ?bool
    {
        return $this->isKeyRestricted;
    }

    /**
     * @deprecated since 4.7.0. Please use the property directly.
     * @infection-ignore-all
     */
    public function isFreshUserVerificationRequired(): ?bool
    {
        return $this->isFreshUserVerificationRequired;
    }

    /**
     * @deprecated since 4.7.0. Please use the property directly.
     * @infection-ignore-all
     */
    public function getAuthenticatorGetInfo(): AuthenticatorGetInfo|null
    {
        return $this->authenticatorGetInfo;
    }

    /**
     * @return string[]
     * @deprecated since 4.7.0. Please use the property directly.
     * @infection-ignore-all
     */
    public function getAttestationCertificateKeyIdentifiers(): array
    {
        return $this->attestationCertificateKeyIdentifiers;
    }

    /**
     * @deprecated since 4.7.0. Please use the property directly.
     * @infection-ignore-all
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @deprecated since 4.7.0. Please use the property directly.
     * @infection-ignore-all
     */
    public function getAlternativeDescriptions(): null|AlternativeDescriptions
    {
        return $this->alternativeDescriptions;
    }

    /**
     * @deprecated since 4.7.0. Please use the property directly.
     * @infection-ignore-all
     */
    public function getAuthenticatorVersion(): int
    {
        return $this->authenticatorVersion;
    }

    /**
     * @deprecated since 4.7.0. Please use the property directly.
     * @infection-ignore-all
     */
    public function getProtocolFamily(): string
    {
        return $this->protocolFamily;
    }

    /**
     * @return Version[]
     * @deprecated since 4.7.0. Please use the property directly.
     * @infection-ignore-all
     */
    public function getUpv(): array
    {
        return $this->upv;
    }

    /**
     * @deprecated since 4.7.0. Please use the property directly.
     * @infection-ignore-all
     */
    public function getSchema(): ?int
    {
        return $this->schema;
    }

    /**
     * @return string[]
     * @deprecated since 4.7.0. Please use the property directly.
     * @infection-ignore-all
     */
    public function getAuthenticationAlgorithms(): array
    {
        return $this->authenticationAlgorithms;
    }

    /**
     * @return string[]
     * @deprecated since 4.7.0. Please use the property directly.
     * @infection-ignore-all
     */
    public function getPublicKeyAlgAndEncodings(): array
    {
        return $this->publicKeyAlgAndEncodings;
    }

    /**
     * @return string[]
     * @deprecated since 4.7.0. Please use the property directly.
     * @infection-ignore-all
     */
    public function getAttestationTypes(): array
    {
        return $this->attestationTypes;
    }

    /**
     * @return VerificationMethodANDCombinations[]
     * @deprecated since 4.7.0. Please use the property directly.
     * @infection-ignore-all
     */
    public function getUserVerificationDetails(): array
    {
        return $this->userVerificationDetails;
    }

    /**
     * @return string[]
     * @deprecated since 4.7.0. Please use the property directly.
     * @infection-ignore-all
     */
    public function getKeyProtection(): array
    {
        return $this->keyProtection;
    }

    /**
     * @return string[]
     * @deprecated since 4.7.0. Please use the property directly.
     * @infection-ignore-all
     */
    public function getMatcherProtection(): array
    {
        return $this->matcherProtection;
    }

    /**
     * @deprecated since 4.7.0. Please use the property directly.
     * @infection-ignore-all
     */
    public function getCryptoStrength(): ?int
    {
        return $this->cryptoStrength;
    }

    /**
     * @return string[]
     * @deprecated since 4.7.0. Please use the property directly.
     * @infection-ignore-all
     */
    public function getAttachmentHint(): array
    {
        return $this->attachmentHint;
    }

    /**
     * @return string[]
     * @deprecated since 4.7.0. Please use the property directly.
     * @infection-ignore-all
     */
    public function getTcDisplay(): array
    {
        return $this->tcDisplay;
    }

    /**
     * @deprecated since 4.7.0. Please use the property directly.
     * @infection-ignore-all
     */
    public function getTcDisplayContentType(): ?string
    {
        return $this->tcDisplayContentType;
    }

    /**
     * @return DisplayPNGCharacteristicsDescriptor[]
     * @deprecated since 4.7.0. Please use the property directly.
     * @infection-ignore-all
     */
    public function getTcDisplayPNGCharacteristics(): array
    {
        return $this->tcDisplayPNGCharacteristics;
    }

    /**
     * @return string[]
     * @deprecated since 4.7.0. Please use the property directly.
     * @infection-ignore-all
     */
    public function getAttestationRootCertificates(): array
    {
        return $this->attestationRootCertificates;
    }

    /**
     * @return EcdaaTrustAnchor[]
     *
     * @deprecated since 4.2.0 and will be removed in 5.0.0. The ECDAA Trust Anchor does no longer exist in Webauthn specification.
     * @infection-ignore-all
     */
    public function getEcdaaTrustAnchors(): array
    {
        return $this->ecdaaTrustAnchors;
    }

    /**
     * @deprecated since 4.7.0. Please use the property directly.
     * @infection-ignore-all
     */
    public function getIcon(): ?string
    {
        return $this->icon;
    }

    /**
     * @return ExtensionDescriptor[]
     * @deprecated since 4.7.0. Please use the property directly.
     * @infection-ignore-all
     */
    public function getSupportedExtensions(): array
    {
        return $this->supportedExtensions;
    }

    /**
     * @param array<string, mixed> $data
     * @deprecated since 4.7.0. Please use the symfony/serializer for converting the object.
     * @infection-ignore-all
     */
    public static function createFromArray(array $data): self
    {
        $requiredKeys = [
            'description',
            'authenticatorVersion',
            'protocolFamily',
            'schema',
            'upv',
            'authenticationAlgorithms',
            'publicKeyAlgAndEncodings',
            'attestationTypes',
            'userVerificationDetails',
            'matcherProtection',
            'tcDisplay',
            'attestationRootCertificates',
        ];
        foreach ($requiredKeys as $key) {
            array_key_exists($key, $data) || throw MetadataStatementLoadingException::create(sprintf(
                'Invalid data. The key "%s" is missing',
                $key
            ));
        }
        $subObjects = [
            'authenticationAlgorithms',
            'publicKeyAlgAndEncodings',
            'attestationTypes',
            'matcherProtection',
            'tcDisplay',
            'attestationRootCertificates',
        ];
        foreach ($subObjects as $subObject) {
            is_array($data[$subObject]) || throw MetadataStatementLoadingException::create(sprintf(
                'Invalid Metadata Statement. The parameter "%s" shall be a list of strings.',
                $subObject
            ));
            foreach ($data[$subObject] as $datum) {
                is_string($datum) || throw MetadataStatementLoadingException::create(sprintf(
                    'Invalid Metadata Statement. The parameter "%s" shall be a list of strings.',
                    $subObject
                ));
            }
        }

        return self::create(
            $data['description'],
            $data['authenticatorVersion'],
            $data['protocolFamily'],
            $data['schema'],
            array_map(static function ($upv): Version {
                is_array($upv) || throw MetadataStatementLoadingException::create(
                    'Invalid Metadata Statement. The parameter "upv" shall be a list of objects.'
                );

                return Version::createFromArray($upv);
            }, $data['upv']),
            $data['authenticationAlgorithms'],
            $data['publicKeyAlgAndEncodings'],
            $data['attestationTypes'],
            array_map(static function ($userVerificationDetails): VerificationMethodANDCombinations {
                is_array($userVerificationDetails) || throw MetadataStatementLoadingException::create(
                    'Invalid Metadata Statement. The parameter "userVerificationDetails" shall be a list of objects.'
                );

                return VerificationMethodANDCombinations::createFromArray($userVerificationDetails);
            }, $data['userVerificationDetails']),
            $data['matcherProtection'],
            $data['tcDisplay'],
            CertificateToolbox::fixPEMStructures($data['attestationRootCertificates']),
            $data['alternativeDescriptions'] ?? [],
            $data['legalHeader'] ?? null,
            $data['aaid'] ?? null,
            $data['aaguid'] ?? null,
            $data['attestationCertificateKeyIdentifiers'] ?? [],
            $data['keyProtection'] ?? [],
            $data['isKeyRestricted'] ?? null,
            $data['isFreshUserVerificationRequired'] ?? null,
            $data['cryptoStrength'] ?? null,
            $data['attachmentHint'] ?? [],
            $data['tcDisplayContentType'] ?? null,
            array_map(
                static fn (array $data): DisplayPNGCharacteristicsDescriptor => DisplayPNGCharacteristicsDescriptor::createFromArray(
                    $data
                ),
                $data['tcDisplayPNGCharacteristics'] ?? []
            ),
            $data['ecdaaTrustAnchors'] ?? [],
            $data['icon'] ?? null,
            array_map(
                static fn ($supportedExtension): ExtensionDescriptor => ExtensionDescriptor::createFromArray(
                    $supportedExtension
                ),
                $data['supportedExtensions'] ?? []
            ),
            isset($data['authenticatorGetInfo']) ? AuthenticatorGetInfo::create($data['authenticatorGetInfo']) : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        trigger_deprecation(
            'web-auth/webauthn-bundle',
            '4.9.0',
            'The "%s" method is deprecated and will be removed in 5.0. Please use the serializer instead.',
            __METHOD__
        );
        $data = [
            'legalHeader' => $this->legalHeader,
            'aaid' => $this->aaid,
            'aaguid' => $this->aaguid,
            'attestationCertificateKeyIdentifiers' => $this->attestationCertificateKeyIdentifiers,
            'description' => $this->description,
            'alternativeDescriptions' => $this->alternativeDescriptions,
            'authenticatorVersion' => $this->authenticatorVersion,
            'protocolFamily' => $this->protocolFamily,
            'schema' => $this->schema,
            'upv' => $this->upv,
            'authenticationAlgorithms' => $this->authenticationAlgorithms,
            'publicKeyAlgAndEncodings' => $this->publicKeyAlgAndEncodings,
            'attestationTypes' => $this->attestationTypes,
            'userVerificationDetails' => $this->userVerificationDetails,
            'keyProtection' => $this->keyProtection,
            'isKeyRestricted' => $this->isKeyRestricted,
            'isFreshUserVerificationRequired' => $this->isFreshUserVerificationRequired,
            'matcherProtection' => $this->matcherProtection,
            'cryptoStrength' => $this->cryptoStrength,
            'attachmentHint' => $this->attachmentHint,
            'tcDisplay' => $this->tcDisplay,
            'tcDisplayContentType' => $this->tcDisplayContentType,
            'tcDisplayPNGCharacteristics' => $this->tcDisplayPNGCharacteristics,
            'attestationRootCertificates' => CertificateToolbox::fixPEMStructures($this->attestationRootCertificates),
            'ecdaaTrustAnchors' => $this->ecdaaTrustAnchors,
            'icon' => $this->icon,
            'authenticatorGetInfo' => $this->authenticatorGetInfo,
            'supportedExtensions' => $this->supportedExtensions,
        ];

        return self::filterNullValues($data);
    }
}
