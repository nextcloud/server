<?php

declare(strict_types=1);

namespace Webauthn\Denormalizer;

use ParagonIE\ConstantTime\Base64UrlSafe;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Webauthn\AuthenticatorResponse;
use Webauthn\Exception\InvalidDataException;
use Webauthn\PublicKeyCredential;
use Webauthn\Util\Base64;
use function array_key_exists;

final class PublicKeyCredentialDenormalizer implements DenormalizerInterface, DenormalizerAwareInterface
{
    use DenormalizerAwareTrait;

    public function denormalize(mixed $data, string $type, string $format = null, array $context = []): mixed
    {
        if (! array_key_exists('id', $data)) {
            return $data;
        }
        $id = Base64UrlSafe::decodeNoPadding($data['id']);
        $rawId = Base64::decode($data['rawId']);
        hash_equals($id, $rawId) || throw InvalidDataException::create($data, 'Invalid ID');
        $data['rawId'] = $rawId;

        return PublicKeyCredential::create(
            null,
            $data['type'],
            $data['rawId'],
            $this->denormalizer->denormalize($data['response'], AuthenticatorResponse::class, $format, $context),
        );
    }

    public function supportsDenormalization(mixed $data, string $type, string $format = null, array $context = []): bool
    {
        return $type === PublicKeyCredential::class;
    }

    /**
     * @return array<class-string, bool>
     */
    public function getSupportedTypes(?string $format): array
    {
        return [
            PublicKeyCredential::class => true,
        ];
    }
}
