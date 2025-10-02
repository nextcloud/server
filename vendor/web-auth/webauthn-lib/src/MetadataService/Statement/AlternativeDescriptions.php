<?php

declare(strict_types=1);

namespace Webauthn\MetadataService\Statement;

use JsonSerializable;

class AlternativeDescriptions implements JsonSerializable
{
    /**
     * @param array<string, string> $descriptions
     */
    public function __construct(
        public array $descriptions = []
    ) {
    }

    /**
     * @param array<string, string> $descriptions
     */
    public static function create(array $descriptions = []): self
    {
        return new self($descriptions);
    }

    /**
     * @return array<string, string>
     * @deprecated since 4.7.0. Please use the property directly.
     * @infection-ignore-all
     */
    public function all(): array
    {
        return $this->descriptions;
    }

    /**
     * @deprecated since 4.7.0. Please use the property directly.
     * @infection-ignore-all
     */
    public function add(string $locale, string $description): self
    {
        $this->descriptions[$locale] = $description;

        return $this;
    }

    /**
     * @return array<string, string>
     */
    public function jsonSerialize(): array
    {
        trigger_deprecation(
            'web-auth/webauthn-bundle',
            '4.9.0',
            'The "%s" method is deprecated and will be removed in 5.0. Please use the serializer instead.',
            __METHOD__
        );
        return $this->descriptions;
    }
}
