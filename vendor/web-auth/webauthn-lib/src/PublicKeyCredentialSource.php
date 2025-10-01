<?php

declare(strict_types=1);

namespace Webauthn;

use JsonSerializable;
use ParagonIE\ConstantTime\Base64UrlSafe;
use Symfony\Component\Uid\Uuid;
use Throwable;
use Webauthn\Exception\InvalidDataException;
use Webauthn\TrustPath\TrustPath;
use Webauthn\TrustPath\TrustPathLoader;
use function array_key_exists;
use function in_array;

/**
 * @see https://www.w3.org/TR/webauthn/#iface-pkcredential
 */
class PublicKeyCredentialSource implements JsonSerializable
{
    /**
     * @private
     * @param string[] $transports
     * @param array<string, mixed>|null $otherUI
     */
    public function __construct(
        public string $publicKeyCredentialId,
        public string $type,
        public array $transports,
        public string $attestationType,
        public TrustPath $trustPath,
        public Uuid $aaguid,
        public string $credentialPublicKey,
        public string $userHandle,
        public int $counter,
        public ?array $otherUI = null,
        public ?bool $backupEligible = null,
        public ?bool $backupStatus = null,
        public ?bool $uvInitialized = null,
    ) {
    }

    /**
     * @param string[] $transports
     * @param array<string, mixed>|null $otherUI
     */
    public static function create(
        string $publicKeyCredentialId,
        string $type,
        array $transports,
        string $attestationType,
        TrustPath $trustPath,
        Uuid $aaguid,
        string $credentialPublicKey,
        string $userHandle,
        int $counter,
        ?array $otherUI = null,
        ?bool $backupEligible = null,
        ?bool $backupStatus = null,
        ?bool $uvInitialized = null,
    ): self {
        return new self(
            $publicKeyCredentialId,
            $type,
            $transports,
            $attestationType,
            $trustPath,
            $aaguid,
            $credentialPublicKey,
            $userHandle,
            $counter,
            $otherUI,
            $backupEligible,
            $backupStatus,
            $uvInitialized
        );
    }

    /**
     * @deprecated since 4.7.0. Please use the property directly.
     * @infection-ignore-all
     */
    public function getPublicKeyCredentialId(): string
    {
        return $this->publicKeyCredentialId;
    }

    public function getPublicKeyCredentialDescriptor(): PublicKeyCredentialDescriptor
    {
        return PublicKeyCredentialDescriptor::create($this->type, $this->publicKeyCredentialId, $this->transports);
    }

    /**
     * @deprecated since 4.7.0. Please use the property directly.
     * @infection-ignore-all
     */
    public function getAttestationType(): string
    {
        return $this->attestationType;
    }

    /**
     * @deprecated since 4.7.0. Please use the property directly.
     * @infection-ignore-all
     */
    public function getTrustPath(): TrustPath
    {
        return $this->trustPath;
    }

    public function getAttestedCredentialData(): AttestedCredentialData
    {
        return AttestedCredentialData::create($this->aaguid, $this->publicKeyCredentialId, $this->credentialPublicKey);
    }

    /**
     * @deprecated since 4.7.0. Please use the property directly.
     * @infection-ignore-all
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return string[]
     * @deprecated since 4.7.0. Please use the property directly.
     * @infection-ignore-all
     */
    public function getTransports(): array
    {
        return $this->transports;
    }

    /**
     * @deprecated since 4.7.0. Please use the property directly.
     * @infection-ignore-all
     */
    public function getAaguid(): Uuid
    {
        return $this->aaguid;
    }

    /**
     * @deprecated since 4.7.0. Please use the property directly.
     * @infection-ignore-all
     */
    public function getCredentialPublicKey(): string
    {
        return $this->credentialPublicKey;
    }

    /**
     * @deprecated since 4.7.0. Please use the property directly.
     * @infection-ignore-all
     */
    public function getUserHandle(): string
    {
        return $this->userHandle;
    }

    /**
     * @deprecated since 4.7.0. Please use the property directly.
     * @infection-ignore-all
     */
    public function getCounter(): int
    {
        return $this->counter;
    }

    /**
     * @deprecated since 4.7.0. Please use the property directly.
     * @infection-ignore-all
     */
    public function setCounter(int $counter): void
    {
        $this->counter = $counter;
    }

    /**
     * @return array<string, mixed>|null
     * @deprecated since 4.7.0. Please use the property directly.
     * @infection-ignore-all
     */
    public function getOtherUI(): ?array
    {
        return $this->otherUI;
    }

    /**
     * @param array<string, mixed>|null $otherUI
     * @deprecated since 4.7.0. Please use the property directly.
     * @infection-ignore-all
     */
    public function setOtherUI(?array $otherUI): self
    {
        $this->otherUI = $otherUI;

        return $this;
    }

    /**
     * @param mixed[] $data
     * @deprecated since 4.8.0. Please use {Webauthn\Denormalizer\WebauthnSerializerFactory} for converting the object.
     * @infection-ignore-all
     */
    public static function createFromArray(array $data): self
    {
        $keys = array_keys(get_class_vars(self::class));
        foreach ($keys as $key) {
            if (in_array($key, ['otherUI', 'backupEligible', 'backupStatus', 'uvInitialized'], true)) {
                continue;
            }
            array_key_exists($key, $data) || throw InvalidDataException::create($data, sprintf(
                'The parameter "%s" is missing',
                $key
            ));
        }
        mb_strlen((string) $data['aaguid'], '8bit') === 36 || throw InvalidDataException::create(
            $data,
            'Invalid AAGUID'
        );
        $uuid = Uuid::fromString($data['aaguid']);

        try {
            return self::create(
                Base64UrlSafe::decodeNoPadding($data['publicKeyCredentialId']),
                $data['type'],
                $data['transports'],
                $data['attestationType'],
                TrustPathLoader::loadTrustPath($data['trustPath']),
                $uuid,
                Base64UrlSafe::decodeNoPadding($data['credentialPublicKey']),
                Base64UrlSafe::decodeNoPadding($data['userHandle']),
                $data['counter'],
                $data['otherUI'] ?? null,
                $data['backupEligible'] ?? null,
                $data['backupStatus'] ?? null,
            );
        } catch (Throwable $throwable) {
            throw InvalidDataException::create($data, 'Unable to load the data', $throwable);
        }
    }

    /**
     * @return mixed[]
     */
    public function jsonSerialize(): array
    {
        trigger_deprecation(
            'web-auth/webauthn-bundle',
            '4.9.0',
            'The "%s" method is deprecated and will be removed in 5.0. Please use the serializer instead.',
            __METHOD__
        );
        $result = [
            'publicKeyCredentialId' => Base64UrlSafe::encodeUnpadded($this->publicKeyCredentialId),
            'type' => $this->type,
            'transports' => $this->transports,
            'attestationType' => $this->attestationType,
            'trustPath' => $this->trustPath,
            'aaguid' => $this->aaguid->__toString(),
            'credentialPublicKey' => Base64UrlSafe::encodeUnpadded($this->credentialPublicKey),
            'userHandle' => Base64UrlSafe::encodeUnpadded($this->userHandle),
            'counter' => $this->counter,
            'otherUI' => $this->otherUI,
            'backupEligible' => $this->backupEligible,
            'backupStatus' => $this->backupStatus,
            'uvInitialized' => $this->uvInitialized,
        ];

        return array_filter($result, static fn ($value): bool => $value !== null);
    }
}
