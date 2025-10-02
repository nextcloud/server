<?php

declare(strict_types=1);

namespace Webauthn;

use JsonSerializable;

abstract class PublicKeyCredentialEntity implements JsonSerializable
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $icon
    ) {
    }

    /**
     * @deprecated since 4.7.0. Please use the property directly.
     * @infection-ignore-all
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @deprecated since 4.7.0. Please use the property directly.
     * @infection-ignore-all
     */
    public function getIcon(): ?string
    {
        return $this->icon;
    }

    /**
     * @return mixed[]
     */
    public function jsonSerialize(): array
    {
        $json = [
            'name' => $this->name,
        ];
        if ($this->icon !== null) {
            $json['icon'] = $this->icon;
        }

        return $json;
    }
}
