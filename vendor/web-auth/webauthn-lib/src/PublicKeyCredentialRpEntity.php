<?php

declare(strict_types=1);

namespace Webauthn;

use Webauthn\Exception\InvalidDataException;
use function array_key_exists;

class PublicKeyCredentialRpEntity extends PublicKeyCredentialEntity
{
    public function __construct(
        string $name,
        public readonly ?string $id = null,
        ?string $icon = null
    ) {
        parent::__construct($name, $icon);
    }

    public static function create(string $name, ?string $id = null, ?string $icon = null): self
    {
        return new self($name, $id, $icon);
    }

    /**
     * @deprecated since 4.7.0. Please use the property directly.
     * @infection-ignore-all
     */
    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * @param mixed[] $json
     * @deprecated since 4.8.0. Please use {Webauthn\Denormalizer\WebauthnSerializerFactory} for converting the object.
     * @infection-ignore-all
     */
    public static function createFromArray(array $json): self
    {
        array_key_exists('name', $json) || throw InvalidDataException::create(
            $json,
            'Invalid input. "name" is missing.'
        );

        return self::create($json['name'], $json['id'] ?? null, $json['icon'] ?? null);
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
        $json = parent::jsonSerialize();
        if ($this->id !== null) {
            $json['id'] = $this->id;
        }

        return $json;
    }
}
