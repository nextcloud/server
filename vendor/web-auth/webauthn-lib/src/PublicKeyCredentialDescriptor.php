<?php

declare(strict_types=1);

namespace Webauthn;

use JsonSerializable;
use ParagonIE\ConstantTime\Base64UrlSafe;
use Webauthn\Exception\InvalidDataException;
use function array_key_exists;
use function count;
use const JSON_THROW_ON_ERROR;

class PublicKeyCredentialDescriptor implements JsonSerializable
{
    final public const CREDENTIAL_TYPE_PUBLIC_KEY = 'public-key';

    final public const AUTHENTICATOR_TRANSPORT_USB = 'usb';

    final public const AUTHENTICATOR_TRANSPORT_NFC = 'nfc';

    final public const AUTHENTICATOR_TRANSPORT_BLE = 'ble';

    final public const AUTHENTICATOR_TRANSPORT_CABLE = 'cable';

    final public const AUTHENTICATOR_TRANSPORT_INTERNAL = 'internal';

    final public const AUTHENTICATOR_TRANSPORTS = [
        self::AUTHENTICATOR_TRANSPORT_USB,
        self::AUTHENTICATOR_TRANSPORT_NFC,
        self::AUTHENTICATOR_TRANSPORT_BLE,
        self::AUTHENTICATOR_TRANSPORT_CABLE,
        self::AUTHENTICATOR_TRANSPORT_INTERNAL,
    ];

    /**
     * @param string[] $transports
     */
    public function __construct(
        public readonly string $type,
        public readonly string $id,
        public readonly array $transports = []
    ) {
    }

    /**
     * @param string[] $transports
     */
    public static function create(string $type, string $id, array $transports = []): self
    {
        return new self($type, $id, $transports);
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
     * @deprecated since 4.7.0. Please use the property directly.
     * @infection-ignore-all
     */
    public function getId(): string
    {
        return $this->id;
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
     * @deprecated since 4.9.0 and will be removed in 5.0.0. Please use the serializer instead.
     */
    public static function createFromString(string $data): self
    {
        $data = json_decode($data, true, flags: JSON_THROW_ON_ERROR);

        return self::createFromArray($data);
    }

    /**
     * @param mixed[] $json
     * @deprecated since 4.9.0 and will be removed in 5.0.0. Please use the serializer instead.
     */
    public static function createFromArray(array $json): self
    {
        array_key_exists('type', $json) || throw InvalidDataException::create(
            $json,
            'Invalid input. "type" is missing.'
        );
        array_key_exists('id', $json) || throw InvalidDataException::create($json, 'Invalid input. "id" is missing.');

        $id = Base64UrlSafe::decodeNoPadding($json['id']);

        return self::create($json['type'], $id, $json['transports'] ?? []);
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
        $json = [
            'type' => $this->type,
            'id' => Base64UrlSafe::encodeUnpadded($this->id),
        ];
        if (count($this->transports) !== 0) {
            $json['transports'] = $this->transports;
        }

        return $json;
    }
}
