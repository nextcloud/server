<?php

declare(strict_types=1);

namespace Webauthn\TrustPath;

final class EmptyTrustPath implements TrustPath
{
    public static function create(): self
    {
        return new self();
    }

    /**
     * @return string[]
     */
    public function jsonSerialize(): array
    {
        trigger_deprecation(
            'web-auth/webauthn-bundle',
            '4.9.0',
            'The "%s" method is deprecated and will be removed in 5.0. Please use the serializer instead.',
            __METHOD__
        );
        return [
            'type' => self::class,
        ];
    }

    /**
     * @deprecated since 4.8.0. Please use {Webauthn\Denormalizer\WebauthnSerializerFactory} for converting the object.
     * @infection-ignore-all
     */
    public static function createFromArray(array $data): static
    {
        return self::create();
    }
}
