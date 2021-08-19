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

use JsonSerializable;

class BiometricStatusReport implements JsonSerializable
{
    /**
     * @var int
     */
    private $certLevel;

    /**
     * @var int
     */
    private $modality;

    /**
     * @var string|null
     */
    private $effectiveDate;

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

    public function getCertLevel(): int
    {
        return $this->certLevel;
    }

    public function getModality(): int
    {
        return $this->modality;
    }

    public function getEffectiveDate(): ?string
    {
        return $this->effectiveDate;
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
        $object = new self();
        $object->certLevel = $data['certLevel'] ?? null;
        $object->modality = $data['modality'] ?? null;
        $object->effectiveDate = $data['effectiveDate'] ?? null;
        $object->certificationDescriptor = $data['certificationDescriptor'] ?? null;
        $object->certificateNumber = $data['certificateNumber'] ?? null;
        $object->certificationPolicyVersion = $data['certificationPolicyVersion'] ?? null;
        $object->certificationRequirementsVersion = $data['certificationRequirementsVersion'] ?? null;

        return $object;
    }

    public function jsonSerialize(): array
    {
        $data = [
            'certLevel' => $this->certLevel,
            'modality' => $this->modality,
            'effectiveDate' => $this->effectiveDate,
            'certificationDescriptor' => $this->certificationDescriptor,
            'certificateNumber' => $this->certificateNumber,
            'certificationPolicyVersion' => $this->certificationPolicyVersion,
            'certificationRequirementsVersion' => $this->certificationRequirementsVersion,
        ];

        return array_filter($data, static function ($var): bool {return null !== $var; });
    }
}
