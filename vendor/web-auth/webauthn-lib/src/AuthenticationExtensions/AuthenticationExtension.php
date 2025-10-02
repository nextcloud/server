<?php

declare(strict_types=1);

namespace Webauthn\AuthenticationExtensions;

use JsonSerializable;

class AuthenticationExtension implements JsonSerializable
{
    public function __construct(
        public readonly string $name,
        public readonly mixed $value
    ) {
    }

    public static function create(string $name, mixed $value): self
    {
        return new self($name, $value);
    }

    /**
     * @deprecated since 4.7.0. Please use the property directly.
     * @infection-ignore-all
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * @deprecated since 4.7.0. Please use the property directly.
     * @infection-ignore-all
     */
    public function value(): mixed
    {
        return $this->value;
    }

    public function jsonSerialize(): mixed
    {
        trigger_deprecation(
            'web-auth/webauthn-bundle',
            '4.9.0',
            'The "%s" method is deprecated and will be removed in 5.0. Please use the serializer instead.',
            __METHOD__
        );
        return $this->value;
    }
}
