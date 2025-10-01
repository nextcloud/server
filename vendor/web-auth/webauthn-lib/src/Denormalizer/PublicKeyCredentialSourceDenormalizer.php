<?php

declare(strict_types=1);

namespace Webauthn\Denormalizer;

use ParagonIE\ConstantTime\Base64UrlSafe;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Uid\Uuid;
use Webauthn\Exception\InvalidDataException;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\TrustPath\TrustPath;
use Webauthn\Util\Base64;
use function array_key_exists;
use function assert;

final class PublicKeyCredentialSourceDenormalizer implements DenormalizerInterface, DenormalizerAwareInterface, NormalizerInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;
    use DenormalizerAwareTrait;

    public function denormalize(mixed $data, string $type, string $format = null, array $context = []): mixed
    {
        $keys = ['publicKeyCredentialId', 'credentialPublicKey', 'userHandle'];
        foreach ($keys as $key) {
            array_key_exists($key, $data) || throw InvalidDataException::create($data, 'Missing ' . $key);
            $data[$key] = Base64::decode($data[$key]);
        }

        return PublicKeyCredentialSource::create(
            $data['publicKeyCredentialId'],
            $data['type'],
            $data['transports'],
            $data['attestationType'],
            $this->denormalizer->denormalize($data['trustPath'], TrustPath::class, $format, $context),
            Uuid::fromString($data['aaguid']),
            $data['credentialPublicKey'],
            $data['userHandle'],
            $data['counter'],
            $data['otherUI'] ?? null,
            $data['backupEligible'] ?? null,
            $data['backupStatus'] ?? null,
            $data['uvInitialized'] ?? null,
        );
    }

    public function supportsDenormalization(mixed $data, string $type, string $format = null, array $context = []): bool
    {
        return $type === PublicKeyCredentialSource::class;
    }

    /**
     * @return array<class-string, bool>
     */
    public function getSupportedTypes(?string $format): array
    {
        return [
            PublicKeyCredentialSource::class => true,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function normalize(mixed $data, ?string $format = null, array $context = []): array
    {
        assert($data instanceof PublicKeyCredentialSource);
        $result = [
            'publicKeyCredentialId' => Base64UrlSafe::encodeUnpadded($data->publicKeyCredentialId),
            'type' => $data->type,
            'transports' => $data->transports,
            'attestationType' => $data->attestationType,
            'trustPath' => $this->normalizer->normalize($data->trustPath, $format, $context),
            'aaguid' => $this->normalizer->normalize($data->aaguid, $format, $context),
            'credentialPublicKey' => Base64UrlSafe::encodeUnpadded($data->credentialPublicKey),
            'userHandle' => Base64UrlSafe::encodeUnpadded($data->userHandle),
            'counter' => $data->counter,
            'otherUI' => $data->otherUI,
            'backupEligible' => $data->backupEligible,
            'backupStatus' => $data->backupStatus,
            'uvInitialized' => $data->uvInitialized,
        ];

        return array_filter($result, static fn ($value): bool => $value !== null);
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof PublicKeyCredentialSource;
    }
}
