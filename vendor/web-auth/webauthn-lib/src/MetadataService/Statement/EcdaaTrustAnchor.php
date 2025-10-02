<?php

declare(strict_types=1);

namespace Webauthn\MetadataService\Statement;

use JsonSerializable;
use ParagonIE\ConstantTime\Base64UrlSafe;
use Webauthn\Exception\MetadataStatementLoadingException;
use Webauthn\MetadataService\ValueFilter;
use function array_key_exists;

/**
 * @deprecated since 4.2.0 and will be removed in 5.0.0. The ECDAA Trust Anchor does no longer exist in Webauthn specification.
 * @infection-ignore-all
 */
class EcdaaTrustAnchor implements JsonSerializable
{
    use ValueFilter;

    public function __construct(
        private readonly string $X,
        private readonly string $Y,
        private readonly string $c,
        private readonly string $sx,
        private readonly string $sy,
        private readonly string $G1Curve
    ) {
    }

    public function getX(): string
    {
        return $this->X;
    }

    public function getY(): string
    {
        return $this->Y;
    }

    public function getC(): string
    {
        return $this->c;
    }

    public function getSx(): string
    {
        return $this->sx;
    }

    public function getSy(): string
    {
        return $this->sy;
    }

    public function getG1Curve(): string
    {
        return $this->G1Curve;
    }

    /**
     * @param array<string, mixed> $data
     * @deprecated since 4.7.0. Please use the symfony/serializer for converting the object.
     */
    public static function createFromArray(array $data): self
    {
        $data = self::filterNullValues($data);
        foreach (['X', 'Y', 'c', 'sx', 'sy', 'G1Curve'] as $key) {
            array_key_exists($key, $data) || throw MetadataStatementLoadingException::create(sprintf(
                'Invalid data. The key "%s" is missing',
                $key
            ));
        }

        return new self(
            Base64UrlSafe::decode($data['X']),
            Base64UrlSafe::decode($data['Y']),
            Base64UrlSafe::decode($data['c']),
            Base64UrlSafe::decode($data['sx']),
            Base64UrlSafe::decode($data['sy']),
            $data['G1Curve']
        );
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
        $data = [
            'X' => Base64UrlSafe::encodeUnpadded($this->X),
            'Y' => Base64UrlSafe::encodeUnpadded($this->Y),
            'c' => Base64UrlSafe::encodeUnpadded($this->c),
            'sx' => Base64UrlSafe::encodeUnpadded($this->sx),
            'sy' => Base64UrlSafe::encodeUnpadded($this->sy),
            'G1Curve' => $this->G1Curve,
        ];

        return self::filterNullValues($data);
    }
}
