<?php

declare(strict_types=1);

namespace Webauthn;

use InvalidArgumentException;
use JsonSerializable;
use Webauthn\AuthenticationExtensions\AuthenticationExtension;
use Webauthn\AuthenticationExtensions\AuthenticationExtensions;
use Webauthn\AuthenticationExtensions\AuthenticationExtensionsClientInputs;

abstract class PublicKeyCredentialOptions implements JsonSerializable
{
    public AuthenticationExtensions $extensions;

    /**
     * @param positive-int|null $timeout
     * @param null|AuthenticationExtensions|array<string|int, mixed|AuthenticationExtensions> $extensions
     * @protected
     */
    public function __construct(
        public readonly string $challenge,
        public null|int $timeout = null,
        null|array|AuthenticationExtensions $extensions = null,
    ) {
        ($this->timeout === null || $this->timeout > 0) || throw new InvalidArgumentException('Invalid timeout');
        if ($extensions === null) {
            $this->extensions = AuthenticationExtensionsClientInputs::create();
        } elseif ($extensions instanceof AuthenticationExtensions) {
            $this->extensions = $extensions;
        } else {
            $this->extensions = AuthenticationExtensions::create($extensions);
        }
    }

    /**
     * @deprecated since 4.7.0. Please use the {self::create} instead.
     * @infection-ignore-all
     */
    public function setTimeout(?int $timeout): static
    {
        $this->timeout = $timeout;

        return $this;
    }

    /**
     * @deprecated since 4.7.0. Please use the {self::create} instead.
     * @infection-ignore-all
     */
    public function addExtension(AuthenticationExtension $extension): static
    {
        $this->extensions[$extension->name] = $extension;

        return $this;
    }

    /**
     * @param AuthenticationExtension[] $extensions
     * @deprecated since 4.7.0. No replacement. Please use the {self::create} instead.
     * @infection-ignore-all
     */
    public function addExtensions(array $extensions): static
    {
        foreach ($extensions as $extension) {
            $this->extensions[$extension->name] = $extension;
        }

        return $this;
    }

    /**
     * @deprecated since 4.7.0. Please use the {self::create} instead.
     * @infection-ignore-all
     */
    public function setExtensions(AuthenticationExtensions $extensions): static
    {
        $this->extensions = $extensions;

        return $this;
    }

    /**
     * @deprecated since 4.7.0. Please use the property directly.
     * @infection-ignore-all
     */
    public function getChallenge(): string
    {
        return $this->challenge;
    }

    /**
     * @deprecated since 4.7.0. Please use the property directly.
     * @infection-ignore-all
     */
    public function getTimeout(): ?int
    {
        return $this->timeout;
    }

    /**
     * @deprecated since 4.7.0. Please use the property directly.
     * @infection-ignore-all
     */
    public function getExtensions(): AuthenticationExtensions
    {
        return $this->extensions;
    }

    /**
     * @deprecated since 4.8.0. Please use {Webauthn\Denormalizer\WebauthnSerializerFactory} for converting the object.
     * @infection-ignore-all
     */
    abstract public static function createFromString(string $data): static;

    /**
     * @param mixed[] $json
     * @deprecated since 4.8.0. Please use {Webauthn\Denormalizer\WebauthnSerializerFactory} for converting the object.
     * @infection-ignore-all
     */
    abstract public static function createFromArray(array $json): static;
}
