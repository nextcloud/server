<?php

declare(strict_types=1);

namespace Webauthn\TrustPath;

use JsonSerializable;

interface TrustPath extends JsonSerializable
{
    /**
     * @param array<string, mixed> $data
     * @deprecated since 4.8.0. Please use {Webauthn\Denormalizer\WebauthnSerializerFactory} for converting the object.
     * @infection-ignore-all
     */
    public static function createFromArray(array $data): static;
}
