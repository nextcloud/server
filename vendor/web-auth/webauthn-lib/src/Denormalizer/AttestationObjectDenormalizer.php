<?php

declare(strict_types=1);

namespace Webauthn\Denormalizer;

use CBOR\Decoder;
use CBOR\Normalizable;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Webauthn\AttestationStatement\AttestationObject;
use Webauthn\AttestationStatement\AttestationStatement;
use Webauthn\AuthenticatorData;
use Webauthn\Exception\InvalidDataException;
use Webauthn\StringStream;

final class AttestationObjectDenormalizer implements DenormalizerInterface, DenormalizerAwareInterface
{
    use DenormalizerAwareTrait;

    public function denormalize(mixed $data, string $type, string $format = null, array $context = []): mixed
    {
        $stream = new StringStream($data);
        $parsed = Decoder::create()->decode($stream);

        $parsed instanceof Normalizable || throw InvalidDataException::create(
            $parsed,
            'Invalid attestation object. Unexpected object.'
        );
        $attestationObject = $parsed->normalize();
        $stream->isEOF() || throw InvalidDataException::create(
            null,
            'Invalid attestation object. Presence of extra bytes.'
        );
        $stream->close();
        $authData = $attestationObject['authData'] ?? throw InvalidDataException::create(
            $attestationObject,
            'Invalid attestation object. Missing "authData" field.'
        );

        return AttestationObject::create(
            $data,
            $this->denormalizer->denormalize($attestationObject, AttestationStatement::class, $format, $context),
            $this->denormalizer->denormalize($authData, AuthenticatorData::class, $format, $context),
        );
    }

    public function supportsDenormalization(mixed $data, string $type, string $format = null, array $context = []): bool
    {
        return $type === AttestationObject::class;
    }

    /**
     * @return array<class-string, bool>
     */
    public function getSupportedTypes(?string $format): array
    {
        return [
            AttestationObject::class => true,
        ];
    }
}
