<?php

declare(strict_types=1);

namespace Webauthn\TrustPath;

use Webauthn\Exception\InvalidTrustPathException;
use function array_key_exists;
use function is_array;

final class CertificateTrustPath implements TrustPath
{
    /**
     * @param string[] $certificates
     */
    public function __construct(
        public readonly array $certificates
    ) {
    }

    /**
     * @param string[] $certificates
     */
    public static function create(array $certificates): self
    {
        return new self($certificates);
    }

    /**
     * @return string[]
     * @deprecated since 4.7.0. Please use the property directly.
     * @infection-ignore-all
     */
    public function getCertificates(): array
    {
        return $this->certificates;
    }

    /**
     * @deprecated since 4.8.0. Please use {Webauthn\Denormalizer\WebauthnSerializerFactory} for converting the object.
     * @infection-ignore-all
     */
    public static function createFromArray(array $data): static
    {
        array_key_exists('x5c', $data) || throw InvalidTrustPathException::create('The trust path type is invalid');
        $x5c = $data['x5c'];
        is_array($x5c) || throw InvalidTrustPathException::create(
            'The trust path type is invalid. The parameter "x5c" shall contain strings.'
        );

        return self::create($x5c);
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
        return [
            'type' => self::class,
            'x5c' => $this->certificates,
        ];
    }
}
