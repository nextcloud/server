<?php

declare(strict_types=1);

namespace Webauthn\Denormalizer;

use ParagonIE\ConstantTime\Base64UrlSafe;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Webauthn\AttestationStatement\AttestationObject;
use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\AuthenticatorData;
use Webauthn\CollectedClientData;
use Webauthn\Util\Base64;

final class AuthenticatorAssertionResponseDenormalizer implements DenormalizerInterface, DenormalizerAwareInterface
{
    use DenormalizerAwareTrait;

    public function denormalize(mixed $data, string $type, string $format = null, array $context = []): mixed
    {
        $data['authenticatorData'] = Base64::decode($data['authenticatorData']);
        $data['signature'] = Base64::decode($data['signature']);
        $data['clientDataJSON'] = Base64UrlSafe::decodeNoPadding($data['clientDataJSON']);
        $userHandle = $data['userHandle'] ?? null;
        if ($userHandle !== '' && $userHandle !== null) {
            $userHandle = Base64::decode($userHandle);
        }

        return AuthenticatorAssertionResponse::create(
            $this->denormalizer->denormalize($data['clientDataJSON'], CollectedClientData::class, $format, $context),
            $this->denormalizer->denormalize($data['authenticatorData'], AuthenticatorData::class, $format, $context),
            $data['signature'],
            $userHandle ?? null,
            ! isset($data['attestationObject']) ? null : $this->denormalizer->denormalize(
                $data['attestationObject'],
                AttestationObject::class,
                $format,
                $context
            ),
        );
    }

    public function supportsDenormalization(mixed $data, string $type, string $format = null, array $context = []): bool
    {
        return $type === AuthenticatorAssertionResponse::class;
    }

    /**
     * @return array<class-string, bool>
     */
    public function getSupportedTypes(?string $format): array
    {
        return [
            AuthenticatorAssertionResponse::class => true,
        ];
    }
}
