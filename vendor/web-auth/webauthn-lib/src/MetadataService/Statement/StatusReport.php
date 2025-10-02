<?php

declare(strict_types=1);

namespace Webauthn\MetadataService\Statement;

use JsonSerializable;
use Webauthn\Exception\MetadataStatementLoadingException;
use Webauthn\MetadataService\ValueFilter;
use function array_key_exists;
use function in_array;
use function is_string;

class StatusReport implements JsonSerializable
{
    use ValueFilter;

    /**
     * @see AuthenticatorStatus
     */
    public function __construct(
        public readonly string $status,
        public readonly ?string $effectiveDate,
        public readonly ?string $certificate,
        public readonly ?string $url,
        public readonly ?string $certificationDescriptor,
        public readonly ?string $certificateNumber,
        public readonly ?string $certificationPolicyVersion,
        public readonly ?string $certificationRequirementsVersion
    ) {
        in_array($status, AuthenticatorStatus::STATUSES, true) || throw MetadataStatementLoadingException::create(
            'The value of the key "status" is not acceptable'
        );
    }

    public static function create(
        string $status,
        ?string $effectiveDate,
        ?string $certificate,
        ?string $url,
        ?string $certificationDescriptor,
        ?string $certificateNumber,
        ?string $certificationPolicyVersion,
        ?string $certificationRequirementsVersion
    ): self {
        return new self(
            $status,
            $effectiveDate,
            $certificate,
            $url,
            $certificationDescriptor,
            $certificateNumber,
            $certificationPolicyVersion,
            $certificationRequirementsVersion
        );
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

    /**
     * @deprecated since 4.7.0. Please use the property directly.
     * @infection-ignore-all
     */
    public function getStatus(): string
    {
        return $this->status;
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
    public function getCertificate(): ?string
    {
        return $this->certificate;
    }

    /**
     * @deprecated since 4.7.0. Please use the property directly.
     * @infection-ignore-all
     */
    public function getUrl(): ?string
    {
        return $this->url;
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
        $data = self::filterNullValues($data);
        array_key_exists('status', $data) || throw MetadataStatementLoadingException::create(
            'The key "status" is missing'
        );
        foreach ([
            'effectiveDate',
            'certificate',
            'url',
            'certificationDescriptor',
            'certificateNumber',
            'certificationPolicyVersion',
            'certificationRequirementsVersion',
        ] as $key) {
            if (isset($data[$key])) {
                $value = $data[$key];
                $value === null || is_string($value) || throw MetadataStatementLoadingException::create(sprintf(
                    'The value of the key "%s" is invalid',
                    $key
                ));
            }
        }

        return self::create(
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
            'status' => $this->status,
            'effectiveDate' => $this->effectiveDate,
            'certificate' => $this->certificate,
            'url' => $this->url,
            'certificationDescriptor' => $this->certificationDescriptor,
            'certificateNumber' => $this->certificateNumber,
            'certificationPolicyVersion' => $this->certificationPolicyVersion,
            'certificationRequirementsVersion' => $this->certificationRequirementsVersion,
        ];

        return self::filterNullValues($data);
    }
}
