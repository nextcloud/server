<?php

declare(strict_types=1);

namespace Webauthn\MetadataService\Statement;

use JsonSerializable;

class BiometricStatusReport implements JsonSerializable
{
    private function __construct(
        public readonly ?int $certLevel,
        public readonly ?int $modality,
        public readonly ?string $effectiveDate,
        public readonly ?string $certificationDescriptor,
        public readonly ?string $certificateNumber,
        public readonly ?string $certificationPolicyVersion,
        public readonly ?string $certificationRequirementsVersion,
    ) {
    }

    public static function create(
        ?int $certLevel,
        ?int $modality,
        ?string $effectiveDate,
        ?string $certificationDescriptor,
        ?string $certificateNumber,
        ?string $certificationPolicyVersion,
        ?string $certificationRequirementsVersion,
    ): self {
        return new self(
            $certLevel,
            $modality,
            $effectiveDate,
            $certificationDescriptor,
            $certificateNumber,
            $certificationPolicyVersion,
            $certificationRequirementsVersion,
        );
    }

    /**
     * @deprecated since 4.7.0. Please use the property directly.
     * @infection-ignore-all
     */
    public function getCertLevel(): int|null
    {
        return $this->certLevel;
    }

    /**
     * @deprecated since 4.7.0. Please use the property directly.
     * @infection-ignore-all
     */
    public function getModality(): int|null
    {
        return $this->modality;
    }

    /**
     * @deprecated since 4.7.0. Please use the property directly.
     * @infection-ignore-all
     */
    public function getEffectiveDate(): ?string
    {
        return $this->effectiveDate;
    }

    /**
     * @deprecated since 4.7.0. Please use the property directly.
     * @infection-ignore-all
     */
    public function getCertificationDescriptor(): ?string
    {
        return $this->certificationDescriptor;
    }

    /**
     * @deprecated since 4.7.0. Please use the property directly.
     * @infection-ignore-all
     */
    public function getCertificateNumber(): ?string
    {
        return $this->certificateNumber;
    }

    /**
     * @deprecated since 4.7.0. Please use the property directly.
     * @infection-ignore-all
     */
    public function getCertificationPolicyVersion(): ?string
    {
        return $this->certificationPolicyVersion;
    }

    /**
     * @deprecated since 4.7.0. Please use the property directly.
     * @infection-ignore-all
     */
    public function getCertificationRequirementsVersion(): ?string
    {
        return $this->certificationRequirementsVersion;
    }

    /**
     * @param array<string, mixed> $data
     * @deprecated since 4.7.0. Please use the symfony/serializer for converting the object.
     * @infection-ignore-all
     */
    public static function createFromArray(array $data): self
    {
        return self::create(
            $data['certLevel'] ?? null,
            $data['modality'] ?? null,
            $data['effectiveDate'] ?? null,
            $data['certificationDescriptor'] ?? null,
            $data['certificateNumber'] ?? null,
            $data['certificationPolicyVersion'] ?? null,
            $data['certificationRequirementsVersion'] ?? null,
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
            'certLevel' => $this->certLevel,
            'modality' => $this->modality,
            'effectiveDate' => $this->effectiveDate,
            'certificationDescriptor' => $this->certificationDescriptor,
            'certificateNumber' => $this->certificateNumber,
            'certificationPolicyVersion' => $this->certificationPolicyVersion,
            'certificationRequirementsVersion' => $this->certificationRequirementsVersion,
        ];

        return array_filter($data, static fn ($var): bool => $var !== null);
    }
}
