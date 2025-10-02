<?php

declare(strict_types=1);

namespace Webauthn;

use JsonSerializable;
use ParagonIE\ConstantTime\Base64;
use Symfony\Component\Uid\Uuid;
use Webauthn\Exception\InvalidDataException;
use function array_key_exists;
use function is_string;

/**
 * @see https://www.w3.org/TR/webauthn/#sec-attested-credential-data
 */
class AttestedCredentialData implements JsonSerializable
{
    public function __construct(
        public Uuid $aaguid,
        public readonly string $credentialId,
        public readonly ?string $credentialPublicKey
    ) {
    }

    public static function create(Uuid $aaguid, string $credentialId, ?string $credentialPublicKey = null): self
    {
        return new self($aaguid, $credentialId, $credentialPublicKey);
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
    public function setAaguid(Uuid $aaguid): void
    {
        $this->aaguid = $aaguid;
    }

    /**
     * @deprecated since 4.7.0. Please use the property directly.
     * @infection-ignore-all
     */
    public function getCredentialId(): string
    {
        return $this->credentialId;
    }

    /**
     * @deprecated since 4.7.0. Please use the property directly.
     * @infection-ignore-all
     */
    public function getCredentialPublicKey(): ?string
    {
        return $this->credentialPublicKey;
    }

    /**
     * @param mixed[] $json
     * @deprecated since 4.9.0 and will be removed in 5.0.0. Please use the serializer instead.
     */
    public static function createFromArray(array $json): self
    {
        array_key_exists('aaguid', $json) || throw InvalidDataException::create(
            $json,
            'Invalid input. "aaguid" is missing.'
        );
        $aaguid = $json['aaguid'];
        is_string($aaguid) || throw InvalidDataException::create(
            $json,
            'Invalid input. "aaguid" shall be a string of 36 characters'
        );
        mb_strlen($aaguid, '8bit') === 36 || throw InvalidDataException::create(
            $json,
            'Invalid input. "aaguid" shall be a string of 36 characters'
        );
        $uuid = Uuid::fromString($aaguid);

        array_key_exists('credentialId', $json) || throw InvalidDataException::create(
            $json,
            'Invalid input. "credentialId" is missing.'
        );
        $credentialId = $json['credentialId'];
        is_string($credentialId) || throw InvalidDataException::create(
            $json,
            'Invalid input. "credentialId" shall be a string'
        );
        $credentialId = Base64::decode($credentialId, true);

        $credentialPublicKey = null;
        if (isset($json['credentialPublicKey'])) {
            $credentialPublicKey = Base64::decode($json['credentialPublicKey'], true);
        }

        return self::create($uuid, $credentialId, $credentialPublicKey);
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
            'aaguid' => $this->aaguid->__toString(),
            'credentialId' => base64_encode($this->credentialId),
        ];
        if ($this->credentialPublicKey !== null) {
            $result['credentialPublicKey'] = base64_encode($this->credentialPublicKey);
        }

        return $result;
    }
}
