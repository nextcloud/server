<?php

declare(strict_types=1);

namespace Webauthn\Denormalizer;

use Symfony\Component\Serializer\Normalizer\DenormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\AuthenticatorResponse;
use Webauthn\Exception\InvalidDataException;
use function array_key_exists;

final class AuthenticatorResponseDenormalizer implements DenormalizerInterface, DenormalizerAwareInterface
{
    use DenormalizerAwareTrait;

    public function denormalize(mixed $data, string $type, string $format = null, array $context = []): mixed
    {
        $realType = match (true) {
            array_key_exists('attestationObject', $data) => AuthenticatorAttestationResponse::class,
            array_key_exists('signature', $data) => AuthenticatorAssertionResponse::class,
            default => throw InvalidDataException::create($data, 'Unable to create the response object'),
        };

        return $this->denormalizer->denormalize($data, $realType, $format, $context);
    }

    public function supportsDenormalization(mixed $data, string $type, string $format = null, array $context = []): bool
    {
        return $type === AuthenticatorResponse::class;
    }

    /**
     * @return array<class-string, bool>
     */
    public function getSupportedTypes(?string $format): array
    {
        return [
            AuthenticatorResponse::class => true,
        ];
    }
}
