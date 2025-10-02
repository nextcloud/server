<?php

declare(strict_types=1);

namespace Webauthn\Denormalizer;

use ParagonIE\ConstantTime\Base64UrlSafe;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Webauthn\PublicKeyCredentialUserEntity;
use Webauthn\Util\Base64;
use function array_key_exists;
use function assert;

final class PublicKeyCredentialUserEntityDenormalizer implements DenormalizerInterface, NormalizerInterface
{
    public function denormalize(mixed $data, string $type, string $format = null, array $context = []): mixed
    {
        if (! array_key_exists('id', $data)) {
            return $data;
        }
        $data['id'] = Base64::decode($data['id']);

        return PublicKeyCredentialUserEntity::create(
            $data['name'],
            $data['id'],
            $data['displayName'],
            $data['icon'] ?? null
        );
    }

    public function supportsDenormalization(mixed $data, string $type, string $format = null, array $context = []): bool
    {
        return $type === PublicKeyCredentialUserEntity::class;
    }

    /**
     * @return array<class-string, bool>
     */
    public function getSupportedTypes(?string $format): array
    {
        return [
            PublicKeyCredentialUserEntity::class => true,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function normalize(mixed $data, ?string $format = null, array $context = []): array
    {
        assert($data instanceof PublicKeyCredentialUserEntity);
        $normalized = [
            'id' => Base64UrlSafe::encodeUnpadded($data->id),
            'name' => $data->name,
            'displayName' => $data->displayName,
            'icon' => $data->icon,
        ];

        return array_filter($normalized, fn ($value) => $value !== null);
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof PublicKeyCredentialUserEntity;
    }
}
