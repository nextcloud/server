<?php

declare(strict_types=1);

namespace Webauthn\TrustPath;

use Webauthn\Exception\InvalidTrustPathException;
use function array_key_exists;
use function is_array;
use function is_string;

/**
 * @deprecated since 4.9.0 and will be removed in 5.0.0. Use the serializer instead
 */
final class TrustPathLoader
{
    /**
     * @param mixed[] $data
     */
    public static function loadTrustPath(array $data): TrustPath
    {
        return match (true) {
            $data === [] || $data === [
                'type' => EmptyTrustPath::class,
            ] => EmptyTrustPath::create(),
            array_key_exists('x5c', $data) && is_array($data['x5c']) => CertificateTrustPath::create($data['x5c']),
            array_key_exists('ecdaaKeyId', $data) && is_string($data['ecdaaKeyId']) => new EcdaaKeyIdTrustPath(
                $data['ecdaaKeyId']
            ),
            default => throw InvalidTrustPathException::create('Unsupported trust path'),
        };
    }
}
