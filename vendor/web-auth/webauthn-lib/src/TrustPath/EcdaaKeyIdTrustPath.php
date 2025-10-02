<?php

declare(strict_types=1);

namespace Webauthn\TrustPath;

use Webauthn\Exception\InvalidTrustPathException;
use function array_key_exists;

/**
 * @deprecated since 4.2.0 and will be removed in 5.0.0. The ECDAA Trust Anchor does no longer exist in Webauthn specification.
 * @infection-ignore-all
 */
final class EcdaaKeyIdTrustPath implements TrustPath
{
    public function __construct(
        private readonly string $ecdaaKeyId
    ) {
    }

    public function getEcdaaKeyId(): string
    {
        return $this->ecdaaKeyId;
    }

    /**
     * @return string[]
     */
    public function jsonSerialize(): array
    {
        return [
            'type' => self::class,
            'ecdaaKeyId' => $this->ecdaaKeyId,
        ];
    }

    public static function createFromArray(array $data): static
    {
        array_key_exists('ecdaaKeyId', $data) || throw InvalidTrustPathException::create(
            'The trust path type is invalid'
        );

        return new self($data['ecdaaKeyId']);
    }
}
