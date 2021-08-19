<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2020 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace Webauthn\MetadataService;

use Assert\Assertion;
use InvalidArgumentException;
use JsonSerializable;
use function Safe\json_decode;
use function Safe\sprintf;

class MetadataStatement implements JsonSerializable
{
    public const KEY_PROTECTION_SOFTWARE = 0x0001;
    public const KEY_PROTECTION_HARDWARE = 0x0002;
    public const KEY_PROTECTION_TEE = 0x0004;
    public const KEY_PROTECTION_SECURE_ELEMENT = 0x0008;
    public const KEY_PROTECTION_REMOTE_HANDLE = 0x0010;

    public const MATCHER_PROTECTION_SOFTWARE = 0x0001;
    public const MATCHER_PROTECTION_TEE = 0x0002;
    public const MATCHER_PROTECTION_ON_CHIP = 0x0004;

    public const ATTACHMENT_HINT_INTERNAL = 0x0001;
    public const ATTACHMENT_HINT_EXTERNAL = 0x0002;
    public const ATTACHMENT_HINT_WIRED = 0x0004;
    public const ATTACHMENT_HINT_WIRELESS = 0x0008;
    public const ATTACHMENT_HINT_NFC = 0x0010;
    public const ATTACHMENT_HINT_BLUETOOTH = 0x0020;
    public const ATTACHMENT_HINT_NETWORK = 0x0040;
    public const ATTACHMENT_HINT_READY = 0x0080;
    public const ATTACHMENT_HINT_WIFI_DIRECT = 0x0100;

    public const TRANSACTION_CONFIRMATION_DISPLAY_ANY = 0x0001;
    public const TRANSACTION_CONFIRMATION_DISPLAY_PRIVILEGED_SOFTWARE = 0x0002;
    public const TRANSACTION_CONFIRMATION_DISPLAY_TEE = 0x0004;
    public const TRANSACTION_CONFIRMATION_DISPLAY_HARDWARE = 0x0008;
    public const TRANSACTION_CONFIRMATION_DISPLAY_REMOTE = 0x0010;

    public const ALG_SIGN_SECP256R1_ECDSA_SHA256_RAW = 0x0001;
    public const ALG_SIGN_SECP256R1_ECDSA_SHA256_DER = 0x0002;
    public const ALG_SIGN_RSASSA_PSS_SHA256_RAW = 0x0003;
    public const ALG_SIGN_RSASSA_PSS_SHA256_DER = 0x0004;
    public const ALG_SIGN_SECP256K1_ECDSA_SHA256_RAW = 0x0005;
    public const ALG_SIGN_SECP256K1_ECDSA_SHA256_DER = 0x0006;
    public const ALG_SIGN_SM2_SM3_RAW = 0x0007;
    public const ALG_SIGN_RSA_EMSA_PKCS1_SHA256_RAW = 0x0008;
    public const ALG_SIGN_RSA_EMSA_PKCS1_SHA256_DER = 0x0009;
    public const ALG_SIGN_RSASSA_PSS_SHA384_RAW = 0x000A;
    public const ALG_SIGN_RSASSA_PSS_SHA512_RAW = 0x000B;
    public const ALG_SIGN_RSASSA_PKCSV15_SHA256_RAW = 0x000C;
    public const ALG_SIGN_RSASSA_PKCSV15_SHA384_RAW = 0x000D;
    public const ALG_SIGN_RSASSA_PKCSV15_SHA512_RAW = 0x000E;
    public const ALG_SIGN_RSASSA_PKCSV15_SHA1_RAW = 0x000F;
    public const ALG_SIGN_SECP384R1_ECDSA_SHA384_RAW = 0x0010;
    public const ALG_SIGN_SECP521R1_ECDSA_SHA512_RAW = 0x0011;
    public const ALG_SIGN_ED25519_EDDSA_SHA256_RAW = 0x0012;

    public const ALG_KEY_ECC_X962_RAW = 0x0100;
    public const ALG_KEY_ECC_X962_DER = 0x0101;
    public const ALG_KEY_RSA_2048_RAW = 0x0102;
    public const ALG_KEY_RSA_2048_DER = 0x0103;
    public const ALG_KEY_COSE = 0x0104;

    public const ATTESTATION_BASIC_FULL = 0x3E07;
    public const ATTESTATION_BASIC_SURROGATE = 0x3E08;
    public const ATTESTATION_ECDAA = 0x3E09;
    public const ATTESTATION_ATTCA = 0x3E0A;

    /**
     * @var string|null
     */
    private $legalHeader;

    /**
     * @var string|null
     */
    private $aaid;

    /**
     * @var string|null
     */
    private $aaguid;
    /**
     * @var string[]
     */
    private $attestationCertificateKeyIdentifiers = [];

    /**
     * @var string
     */
    private $description;

    /**
     * @var string[]
     */
    private $alternativeDescriptions = [];

    /**
     * @var int
     */
    private $authenticatorVersion;

    /**
     * @var string
     */
    private $protocolFamily;

    /**
     * @var Version[]
     */
    private $upv = [];

    /**
     * @var string|null
     */
    private $assertionScheme;

    /**
     * @var int|null
     */
    private $authenticationAlgorithm;

    /**
     * @var int[]
     */
    private $authenticationAlgorithms = [];

    /**
     * @var int|null
     */
    private $publicKeyAlgAndEncoding;

    /**
     * @var int[]
     */
    private $publicKeyAlgAndEncodings = [];

    /**
     * @var int[]
     */
    private $attestationTypes = [];

    /**
     * @var VerificationMethodANDCombinations[]
     */
    private $userVerificationDetails = [];

    /**
     * @var int
     */
    private $keyProtection;

    /**
     * @var bool|null
     */
    private $isKeyRestricted;

    /**
     * @var bool|null
     */
    private $isFreshUserVerificationRequired;

    /**
     * @var int
     */
    private $matcherProtection;

    /**
     * @var int|null
     */
    private $cryptoStrength;

    /**
     * @var string|null
     */
    private $operatingEnv;

    /**
     * @var int
     */
    private $attachmentHint = 0;

    /**
     * @var bool|null
     */
    private $isSecondFactorOnly;

    /**
     * @var int
     */
    private $tcDisplay;

    /**
     * @var string|null
     */
    private $tcDisplayContentType;

    /**
     * @var DisplayPNGCharacteristicsDescriptor[]
     */
    private $tcDisplayPNGCharacteristics = [];

    /**
     * @var string[]
     */
    private $attestationRootCertificates = [];

    /**
     * @var EcdaaTrustAnchor[]
     */
    private $ecdaaTrustAnchors = [];

    /**
     * @var string|null
     */
    private $icon;

    /**
     * @var ExtensionDescriptor[]
     */
    private $supportedExtensions = [];

    /**
     * @var array<int, StatusReport>
     */
    private $statusReports = [];

    /**
     * @var string[]
     */
    private $rootCertificates = [];

    public static function createFromString(string $statement): self
    {
        $data = json_decode($statement, true);
        Assertion::isArray($data, 'Invalid Metadata Statement');

        return self::createFromArray($data);
    }

    public function getLegalHeader(): ?string
    {
        return $this->legalHeader;
    }

    public function getAaid(): ?string
    {
        return $this->aaid;
    }

    public function getAaguid(): ?string
    {
        return $this->aaguid;
    }

    /**
     * @return string[]
     */
    public function getAttestationCertificateKeyIdentifiers(): array
    {
        return $this->attestationCertificateKeyIdentifiers;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @return string[]
     */
    public function getAlternativeDescriptions(): array
    {
        return $this->alternativeDescriptions;
    }

    public function getAuthenticatorVersion(): int
    {
        return $this->authenticatorVersion;
    }

    public function getProtocolFamily(): string
    {
        return $this->protocolFamily;
    }

    /**
     * @return Version[]
     */
    public function getUpv(): array
    {
        return $this->upv;
    }

    public function getAssertionScheme(): ?string
    {
        return $this->assertionScheme;
    }

    public function getAuthenticationAlgorithm(): ?int
    {
        return $this->authenticationAlgorithm;
    }

    /**
     * @return int[]
     */
    public function getAuthenticationAlgorithms(): array
    {
        return $this->authenticationAlgorithms;
    }

    public function getPublicKeyAlgAndEncoding(): ?int
    {
        return $this->publicKeyAlgAndEncoding;
    }

    /**
     * @return int[]
     */
    public function getPublicKeyAlgAndEncodings(): array
    {
        return $this->publicKeyAlgAndEncodings;
    }

    /**
     * @return int[]
     */
    public function getAttestationTypes(): array
    {
        return $this->attestationTypes;
    }

    /**
     * @return VerificationMethodANDCombinations[]
     */
    public function getUserVerificationDetails(): array
    {
        return $this->userVerificationDetails;
    }

    public function getKeyProtection(): int
    {
        return $this->keyProtection;
    }

    public function isKeyRestricted(): ?bool
    {
        return (bool) $this->isKeyRestricted;
    }

    public function isFreshUserVerificationRequired(): ?bool
    {
        return (bool) $this->isFreshUserVerificationRequired;
    }

    public function getMatcherProtection(): int
    {
        return $this->matcherProtection;
    }

    public function getCryptoStrength(): ?int
    {
        return $this->cryptoStrength;
    }

    public function getOperatingEnv(): ?string
    {
        return $this->operatingEnv;
    }

    public function getAttachmentHint(): int
    {
        return $this->attachmentHint;
    }

    public function isSecondFactorOnly(): ?bool
    {
        return (bool) $this->isSecondFactorOnly;
    }

    public function getTcDisplay(): int
    {
        return $this->tcDisplay;
    }

    public function getTcDisplayContentType(): ?string
    {
        return $this->tcDisplayContentType;
    }

    /**
     * @return DisplayPNGCharacteristicsDescriptor[]
     */
    public function getTcDisplayPNGCharacteristics(): array
    {
        return $this->tcDisplayPNGCharacteristics;
    }

    /**
     * @return string[]
     */
    public function getAttestationRootCertificates(): array
    {
        return $this->attestationRootCertificates;
    }

    /**
     * @return EcdaaTrustAnchor[]
     */
    public function getEcdaaTrustAnchors(): array
    {
        return $this->ecdaaTrustAnchors;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    /**
     * @return ExtensionDescriptor[]
     */
    public function getSupportedExtensions(): array
    {
        return $this->supportedExtensions;
    }

    public static function createFromArray(array $data): self
    {
        $object = new self();
        foreach (['description', 'protocolFamily'] as $key) {
            if (!isset($data[$key])) {
                throw new InvalidArgumentException(sprintf('The parameter "%s" is missing', $key));
            }
        }
        $object->legalHeader = $data['legalHeader'] ?? null;
        $object->aaid = $data['aaid'] ?? null;
        $object->aaguid = $data['aaguid'] ?? null;
        $object->attestationCertificateKeyIdentifiers = $data['attestationCertificateKeyIdentifiers'] ?? [];
        $object->description = $data['description'];
        $object->alternativeDescriptions = $data['alternativeDescriptions'] ?? [];
        $object->authenticatorVersion = $data['authenticatorVersion'] ?? 0;
        $object->protocolFamily = $data['protocolFamily'];
        if (isset($data['upv'])) {
            $upv = $data['upv'];
            Assertion::isArray($upv, 'Invalid Metadata Statement');
            foreach ($upv as $value) {
                Assertion::isArray($value, 'Invalid Metadata Statement');
                $object->upv[] = Version::createFromArray($value);
            }
        }
        $object->assertionScheme = $data['assertionScheme'] ?? null;
        $object->authenticationAlgorithm = $data['authenticationAlgorithm'] ?? null;
        $object->authenticationAlgorithms = $data['authenticationAlgorithms'] ?? [];
        $object->publicKeyAlgAndEncoding = $data['publicKeyAlgAndEncoding'] ?? null;
        $object->publicKeyAlgAndEncodings = $data['publicKeyAlgAndEncodings'] ?? [];
        $object->attestationTypes = $data['attestationTypes'] ?? [];
        if (isset($data['userVerificationDetails'])) {
            $userVerificationDetails = $data['userVerificationDetails'];
            Assertion::isArray($userVerificationDetails, 'Invalid Metadata Statement');
            foreach ($userVerificationDetails as $value) {
                Assertion::isArray($value, 'Invalid Metadata Statement');
                $object->userVerificationDetails[] = VerificationMethodANDCombinations::createFromArray($value);
            }
        }
        $object->keyProtection = $data['keyProtection'] ?? 0;
        $object->isKeyRestricted = $data['isKeyRestricted'] ?? null;
        $object->isFreshUserVerificationRequired = $data['isFreshUserVerificationRequired'] ?? null;
        $object->matcherProtection = $data['matcherProtection'] ?? 0;
        $object->cryptoStrength = $data['cryptoStrength'] ?? null;
        $object->operatingEnv = $data['operatingEnv'] ?? null;
        $object->attachmentHint = $data['attachmentHint'] ?? 0;
        $object->isSecondFactorOnly = $data['isSecondFactorOnly'] ?? null;
        $object->tcDisplay = $data['tcDisplay'] ?? 0;
        $object->tcDisplayContentType = $data['tcDisplayContentType'] ?? null;
        if (isset($data['tcDisplayPNGCharacteristics'])) {
            $tcDisplayPNGCharacteristics = $data['tcDisplayPNGCharacteristics'];
            Assertion::isArray($tcDisplayPNGCharacteristics, 'Invalid Metadata Statement');
            foreach ($tcDisplayPNGCharacteristics as $tcDisplayPNGCharacteristic) {
                Assertion::isArray($tcDisplayPNGCharacteristic, 'Invalid Metadata Statement');
                $object->tcDisplayPNGCharacteristics[] = DisplayPNGCharacteristicsDescriptor::createFromArray($tcDisplayPNGCharacteristic);
            }
        }
        $object->attestationRootCertificates = $data['attestationRootCertificates'] ?? [];
        $object->ecdaaTrustAnchors = $data['ecdaaTrustAnchors'] ?? [];
        $object->icon = $data['icon'] ?? null;
        if (isset($data['supportedExtensions'])) {
            $supportedExtensions = $data['supportedExtensions'];
            Assertion::isArray($supportedExtensions, 'Invalid Metadata Statement');
            foreach ($supportedExtensions as $supportedExtension) {
                Assertion::isArray($supportedExtension, 'Invalid Metadata Statement');
                $object->supportedExtensions[] = ExtensionDescriptor::createFromArray($supportedExtension);
            }
        }
        $object->rootCertificates = $data['rootCertificates'] ?? [];
        if (isset($data['statusReports'])) {
            $reports = $data['statusReports'];
            Assertion::isArray($reports, 'Invalid Metadata Statement');
            foreach ($reports as $report) {
                Assertion::isArray($report, 'Invalid Metadata Statement');
                $object->statusReports[] = StatusReport::createFromArray($report);
            }
        }

        return $object;
    }

    public function jsonSerialize(): array
    {
        $data = [
            'legalHeader' => $this->legalHeader,
            'aaid' => $this->aaid,
            'aaguid' => $this->aaguid,
            'attestationCertificateKeyIdentifiers' => $this->attestationCertificateKeyIdentifiers,
            'description' => $this->description,
            'alternativeDescriptions' => $this->alternativeDescriptions,
            'authenticatorVersion' => $this->authenticatorVersion,
            'protocolFamily' => $this->protocolFamily,
            'upv' => $this->upv,
            'assertionScheme' => $this->assertionScheme,
            'authenticationAlgorithm' => $this->authenticationAlgorithm,
            'authenticationAlgorithms' => $this->authenticationAlgorithms,
            'publicKeyAlgAndEncoding' => $this->publicKeyAlgAndEncoding,
            'publicKeyAlgAndEncodings' => $this->publicKeyAlgAndEncodings,
            'attestationTypes' => $this->attestationTypes,
            'userVerificationDetails' => $this->userVerificationDetails,
            'keyProtection' => $this->keyProtection,
            'isKeyRestricted' => $this->isKeyRestricted,
            'isFreshUserVerificationRequired' => $this->isFreshUserVerificationRequired,
            'matcherProtection' => $this->matcherProtection,
            'cryptoStrength' => $this->cryptoStrength,
            'operatingEnv' => $this->operatingEnv,
            'attachmentHint' => $this->attachmentHint,
            'isSecondFactorOnly' => $this->isSecondFactorOnly,
            'tcDisplay' => $this->tcDisplay,
            'tcDisplayContentType' => $this->tcDisplayContentType,
            'tcDisplayPNGCharacteristics' => array_map(static function (DisplayPNGCharacteristicsDescriptor $object): array {
                return $object->jsonSerialize();
            }, $this->tcDisplayPNGCharacteristics),
            'attestationRootCertificates' => $this->attestationRootCertificates,
            'ecdaaTrustAnchors' => array_map(static function (EcdaaTrustAnchor $object): array {
                return $object->jsonSerialize();
            }, $this->ecdaaTrustAnchors),
            'icon' => $this->icon,
            'supportedExtensions' => array_map(static function (ExtensionDescriptor $object): array {
                return $object->jsonSerialize();
            }, $this->supportedExtensions),
            'rootCertificates' => $this->rootCertificates,
            'statusReports' => $this->statusReports,
        ];

        return Utils::filterNullValues($data);
    }

    /**
     * @return StatusReport[]
     */
    public function getStatusReports(): array
    {
        return $this->statusReports;
    }

    /**
     * @param StatusReport[] $statusReports
     */
    public function setStatusReports(array $statusReports): self
    {
        $this->statusReports = $statusReports;

        return $this;
    }

    /**
     * @return string[]
     */
    public function getRootCertificates(): array
    {
        return $this->rootCertificates;
    }

    /**
     * @param string[] $rootCertificates
     */
    public function setRootCertificates(array $rootCertificates): self
    {
        $this->rootCertificates = $rootCertificates;

        return $this;
    }
}
