<?php

declare(strict_types=1);

namespace Webauthn\Denormalizer;

use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Webauthn\Exception\InvalidDataException;
use Webauthn\PublicKeyCredentialParameters;
use function array_key_exists;

final class PublicKeyCredentialParametersDenormalizer implements DenormalizerInterface
{
    public function denormalize(mixed $data, string $type, string $format = null, array $context = []): mixed
    {
        if (! array_key_exists('type', $data) || ! array_key_exists('alg', $data)) {
            throw new InvalidDataException($data, 'Missing type or alg');
        }

        return PublicKeyCredentialParameters::create($data['type'], $data['alg']);
    }

    public function supportsDenormalization(mixed $data, string $type, string $format = null, array $context = []): bool
    {
        return $type === PublicKeyCredentialParameters::class;
    }

    /**
     * @return array<class-string, bool>
     */
    public function getSupportedTypes(?string $format): array
    {
        return [
            PublicKeyCredentialParameters::class => true,
        ];
    }
}
