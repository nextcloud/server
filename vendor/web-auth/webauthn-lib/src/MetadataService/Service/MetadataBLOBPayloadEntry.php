<?php

declare(strict_types=1);

namespace Webauthn\MetadataService\Service;

use JsonSerializable;
use Webauthn\Exception\MetadataStatementLoadingException;
use Webauthn\MetadataService\Statement\BiometricStatusReport;
use Webauthn\MetadataService\Statement\MetadataStatement;
use Webauthn\MetadataService\Statement\StatusReport;
use Webauthn\MetadataService\ValueFilter;
use function array_key_exists;
use function count;
use function is_array;
use function is_string;

class MetadataBLOBPayloadEntry implements JsonSerializable
{
    use ValueFilter;

    /**
     * @param StatusReport[] $statusReports
     * @param BiometricStatusReport[] $biometricStatusReports
     * @param string[] $attestationCertificateKeyIdentifiers
     */
    public function __construct(
        public readonly string $timeOfLastStatusChange,
        public array $statusReports,
        public readonly ?string $aaid = null,
        public readonly ?string $aaguid = null,
        public array $attestationCertificateKeyIdentifiers = [],
        public readonly ?MetadataStatement $metadataStatement = null,
        public readonly ?string $rogueListURL = null,
        public readonly ?string $rogueListHash = null,
        public array $biometricStatusReports = []
    ) {
        if ($aaid !== null && $aaguid !== null) {
            throw MetadataStatementLoadingException::create('Authenticators cannot support both AAID and AAGUID');
        }
        if ($aaid === null && $aaguid === null && count($attestationCertificateKeyIdentifiers) === 0) {
            throw MetadataStatementLoadingException::create(
                'If neither AAID nor AAGUID are set, the attestation certificate identifier list shall not be empty'
            );
        }
        foreach ($attestationCertificateKeyIdentifiers as $attestationCertificateKeyIdentifier) {
            is_string($attestationCertificateKeyIdentifier) || throw MetadataStatementLoadingException::create(
                'Invalid attestation certificate identifier. Shall be a list of strings'
            );
            preg_match(
                '/^[0-9a-f]+$/',
                $attestationCertificateKeyIdentifier
            ) === 1 || throw MetadataStatementLoadingException::create(
                'Invalid attestation certificate identifier. Shall be a list of strings'
            );
        }
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
    public function getMetadataStatement(): ?MetadataStatement
    {
        return $this->metadataStatement;
    }

    /**
     * @deprecated since 4.7.0. Please use the property directly.
     * @infection-ignore-all
     */
    public function addBiometricStatusReports(BiometricStatusReport ...$biometricStatusReports): self
    {
        foreach ($biometricStatusReports as $biometricStatusReport) {
            $this->biometricStatusReports[] = $biometricStatusReport;
        }

        return $this;
    }

    /**
     * @return BiometricStatusReport[]
     * @deprecated since 4.7.0. Please use the property directly.
     * @infection-ignore-all
     */
    public function getBiometricStatusReports(): array
    {
        return $this->biometricStatusReports;
    }

    /**
     * @deprecated since 4.7.0. Please use the property directly.
     * @infection-ignore-all
     */
    public function addStatusReports(StatusReport ...$statusReports): self
    {
        foreach ($statusReports as $statusReport) {
            $this->statusReports[] = $statusReport;
        }

        return $this;
    }

    /**
     * @return StatusReport[]
     * @deprecated since 4.7.0. Please use the property directly.
     * @infection-ignore-all
     */
    public function getStatusReports(): array
    {
        return $this->statusReports;
    }

    /**
     * @deprecated since 4.7.0. Please use the property directly.
     * @infection-ignore-all
     */
    public function getTimeOfLastStatusChange(): string
    {
        return $this->timeOfLastStatusChange;
    }

    /**
     * @deprecated since 4.7.0. Please use the property directly.
     * @infection-ignore-all
     */
    public function getRogueListURL(): string|null
    {
        return $this->rogueListURL;
    }

    /**
     * @deprecated since 4.7.0. Please use the property directly.
     * @infection-ignore-all
     */
    public function getRogueListHash(): string|null
    {
        return $this->rogueListHash;
    }

    /**
     * @param array<string, mixed> $data
     * @deprecated since 4.7.0. Please use the symfony/serializer for converting the object.
     * @infection-ignore-all
     */
    public static function createFromArray(array $data): self
    {
        $data = self::filterNullValues($data);
        array_key_exists('timeOfLastStatusChange', $data) || throw MetadataStatementLoadingException::create(
            'Invalid data. The parameter "timeOfLastStatusChange" is missing'
        );
        array_key_exists('statusReports', $data) || throw MetadataStatementLoadingException::create(
            'Invalid data. The parameter "statusReports" is missing'
        );
        is_array($data['statusReports']) || throw MetadataStatementLoadingException::create(
            'Invalid data. The parameter "statusReports" shall be an array of StatusReport objects'
        );

        return new self(
            $data['timeOfLastStatusChange'],
            array_map(
                static fn (array $statusReport) => StatusReport::createFromArray($statusReport),
                $data['statusReports']
            ),
            $data['aaid'] ?? null,
            $data['aaguid'] ?? null,
            $data['attestationCertificateKeyIdentifiers'] ?? [],
            isset($data['metadataStatement']) ? MetadataStatement::createFromArray($data['metadataStatement']) : null,
            $data['rogueListURL'] ?? null,
            $data['rogueListHash'] ?? null,
            array_map(
                static fn (array $biometricStatusReport) => BiometricStatusReport::createFromArray(
                    $biometricStatusReport
                ),
                $data['biometricStatusReports'] ?? []
            )
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
            'aaid' => $this->aaid,
            'aaguid' => $this->aaguid,
            'attestationCertificateKeyIdentifiers' => $this->attestationCertificateKeyIdentifiers,
            'statusReports' => $this->statusReports,
            'timeOfLastStatusChange' => $this->timeOfLastStatusChange,
            'rogueListURL' => $this->rogueListURL,
            'rogueListHash' => $this->rogueListHash,
        ];

        return self::filterNullValues($data);
    }
}
