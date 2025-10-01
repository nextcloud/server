<?php

declare(strict_types=1);

namespace Webauthn\Denormalizer;

use ParagonIE\ConstantTime\Base64UrlSafe;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Webauthn\PublicKeyCredentialDescriptor;
use function assert;
use function count;

final class PublicKeyCredentialDescriptorNormalizer implements NormalizerInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    /**
     * @return array<string, mixed>
     */
    public function normalize(mixed $data, ?string $format = null, array $context = []): array
    {
        assert($data instanceof PublicKeyCredentialDescriptor);
        $result = [
            'type' => $data->type,
            'id' => Base64UrlSafe::encodeUnpadded($data->id),
        ];
        if (count($data->transports) !== 0) {
            $result['transports'] = $data->transports;
        }

        return $result;
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof PublicKeyCredentialDescriptor;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            PublicKeyCredentialDescriptor::class => true,
        ];
    }
}
