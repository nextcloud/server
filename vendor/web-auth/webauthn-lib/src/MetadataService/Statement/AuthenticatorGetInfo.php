<?php

declare(strict_types=1);

namespace Webauthn\MetadataService\Statement;

use JsonSerializable;

class AuthenticatorGetInfo implements JsonSerializable
{
    /**
     * @param array<string|int, mixed> $info
     */
    public function __construct(
        public array $info = []
    ) {
    }

    /**
     * @param array<string|int, mixed> $info
     */
    public static function create(array $info = []): self
    {
        return new self($info);
    }

    /**
     * @deprecated since 4.7.0. Please use the constructor directly.
     * @infection-ignore-all
     */
    public function add(string|int $key, mixed $value): self
    {
        $this->info[$key] = $value;

        return $this;
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
        return $this->info;
    }
}
