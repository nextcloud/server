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
use function in_array;
use JsonSerializable;
use function Safe\sprintf;

class StatusReport implements JsonSerializable
{
    /**
     * @var string
     *
     * @see AuthenticatorStatus
     */
    private $status;

    /**
     * @var string|null
     */
    private $effectiveDate;

    /**
     * @var string|null
     */
    private $certificate;

    /**
     * @var string|null
     */
    private $url;

    /**
     * @var string|null
     */
    private $certificationDescriptor;

    /**
     * @var string|null
     */
    private $certificateNumber;

    /**
     * @var string|null
     */
    private $certificationPolicyVersion;

    /**
     * @var string|null
     */
    private $certificationRequirementsVersion;

    public function __construct(string $status, ?string $effectiveDate, ?string $certificate, ?string $url, ?string $certificationDescriptor, ?string $certificateNumber, ?string $certificationPolicyVersion, ?string $certificationRequirementsVersion)
    {
        Assertion::inArray($status, AuthenticatorStatus::list(), Utils::logicException('The value of the key "status" is not acceptable'));

        $this->status = $status;
        $this->effectiveDate = $effectiveDate;
        $this->certificate = $certificate;
        $this->url = $url;
        $this->certificationDescriptor = $certificationDescriptor;
        $this->certificateNumber = $certificateNumber;
        $this->certificationPolicyVersion = $certificationPolicyVersion;
        $this->certificationRequirementsVersion = $certificationRequirementsVersion;
    }

    public function isCompromised(): bool
    {
        return in_array($this->status, [
            AuthenticatorStatus::ATTESTATION_KEY_COMPROMISE,
            AuthenticatorStatus::USER_KEY_PHYSICAL_COMPROMISE,
            AuthenticatorStatus::USER_KEY_REMOTE_COMPROMISE,
            AuthenticatorStatus::USER_VERIFICATION_BYPASS,
        ], true);
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getEffectiveDate(): ?string
    {
        return $this->effectiveDate;
    }

    public function getCertificate(): ?string
    {
        return $this->certificate;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function getCertificationDescriptor(): ?string
    {
        return $this->certificationDescriptor;
    }

    public function getCertificateNumber(): ?string
    {
        return $this->certificateNumber;
    }

    public function getCertificationPolicyVersion(): ?string
    {
        return $this->certificationPolicyVersion;
    }

    public function getCertificationRequirementsVersion(): ?string
    {
        return $this->certificationRequirementsVersion;
    }

    public static function createFromArray(array $data): self
    {
        $data = Utils::filterNullValues($data);
        Assertion::keyExists($data, 'status', Utils::logicException('The key "status" is missing'));
        foreach (['effectiveDate', 'certificate', 'url', 'certificationDescriptor', 'certificateNumber', 'certificationPolicyVersion', 'certificationRequirementsVersion'] as $key) {
            if (isset($data[$key])) {
                Assertion::nullOrString($data[$key], Utils::logicException(sprintf('The value of the key "%s" is invalid', $key)));
            }
        }

        return new self(
            $data['status'],
            $data['effectiveDate'] ?? null,
            $data['certificate'] ?? null,
            $data['url'] ?? null,
            $data['certificationDescriptor'] ?? null,
            $data['certificateNumber'] ?? null,
            $data['certificationPolicyVersion'] ?? null,
            $data['certificationRequirementsVersion'] ?? null
        );
    }

    public function jsonSerialize(): array
    {
        $data = [
            'status' => $this->status,
            'effectiveDate' => $this->effectiveDate,
            'certificate' => $this->certificate,
            'url' => $this->url,
            'certificationDescriptor' => $this->certificationDescriptor,
            'certificateNumber' => $this->certificateNumber,
            'certificationPolicyVersion' => $this->certificationPolicyVersion,
            'certificationRequirementsVersion' => $this->certificationRequirementsVersion,
        ];

        return Utils::filterNullValues($data);
    }
}
