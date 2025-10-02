<?php

declare(strict_types=1);

namespace Webauthn\Denormalizer;

use CBOR\ByteStringObject;
use CBOR\Decoder;
use CBOR\ListObject;
use CBOR\MapObject;
use CBOR\NegativeIntegerObject;
use CBOR\TextStringObject;
use CBOR\UnsignedIntegerObject;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Uid\Uuid;
use Webauthn\AttestedCredentialData;
use Webauthn\AuthenticationExtensions\AuthenticationExtensions;
use Webauthn\AuthenticationExtensions\AuthenticationExtensionsClientOutputsLoader;
use Webauthn\AuthenticatorData;
use Webauthn\Exception\InvalidDataException;
use Webauthn\StringStream;
use function chr;
use function ord;

final class AuthenticatorDataDenormalizer implements DenormalizerInterface, DenormalizerAwareInterface
{
    use DenormalizerAwareTrait;

    private readonly Decoder $decoder;

    public function __construct()
    {
        $this->decoder = Decoder::create();
    }

    public function denormalize(mixed $data, string $type, string $format = null, array $context = []): mixed
    {
        $authData = $this->fixIncorrectEdDSAKey($data);
        $authDataStream = new StringStream($authData);
        $rp_id_hash = $authDataStream->read(32);
        $flags = $authDataStream->read(1);
        $signCount = $authDataStream->read(4);
        $signCount = unpack('N', $signCount);

        $attestedCredentialData = null;
        if (0 !== (ord($flags) & AuthenticatorData::FLAG_AT)) {
            $aaguid = Uuid::fromBinary($authDataStream->read(16));
            $credentialLength = $authDataStream->read(2);
            $credentialLength = unpack('n', $credentialLength);
            $credentialId = $authDataStream->read($credentialLength[1]);
            $credentialPublicKey = $this->decoder->decode($authDataStream);
            $credentialPublicKey instanceof MapObject || throw InvalidDataException::create(
                $authData,
                'The data does not contain a valid credential public key.'
            );
            $attestedCredentialData = AttestedCredentialData::create(
                $aaguid,
                $credentialId,
                (string) $credentialPublicKey,
            );
        }
        $extension = null;
        if (0 !== (ord($flags) & AuthenticatorData::FLAG_ED)) {
            $extension = $this->decoder->decode($authDataStream);
            $extension = AuthenticationExtensionsClientOutputsLoader::load($extension);
        }
        $authDataStream->isEOF() || throw InvalidDataException::create(
            $authData,
            'Invalid authentication data. Presence of extra bytes.'
        );
        $authDataStream->close();

        return AuthenticatorData::create(
            $authData,
            $rp_id_hash,
            $flags,
            $signCount[1],
            $attestedCredentialData,
            $extension === null ? null : $this->denormalizer->denormalize(
                $extension,
                AuthenticationExtensions::class,
                $format,
                $context
            ),
        );
    }

    public function supportsDenormalization(mixed $data, string $type, string $format = null, array $context = []): bool
    {
        return $type === AuthenticatorData::class;
    }

    /**
     * @return array<class-string, bool>
     */
    public function getSupportedTypes(?string $format): array
    {
        return [
            AuthenticatorData::class => true,
        ];
    }

    private function fixIncorrectEdDSAKey(string $data): string
    {
        $needle = hex2bin('a301634f4b500327206745643235353139');
        $correct = hex2bin('a401634f4b500327206745643235353139');
        $position = mb_strpos($data, $needle, 0, '8bit');
        if ($position === false) {
            return $data;
        }

        $begin = mb_substr($data, 0, $position, '8bit');
        $end = mb_substr($data, $position, null, '8bit');
        $end = str_replace($needle, $correct, $end);
        $cbor = new StringStream($end);
        $badKey = $this->decoder->decode($cbor);

        ($badKey instanceof MapObject && $cbor->isEOF()) || throw InvalidDataException::create(
            $end,
            'Invalid authentication data. Presence of extra bytes.'
        );
        $badX = $badKey->get(-2);
        $badX instanceof ListObject || throw InvalidDataException::create($end, 'Invalid authentication data.');
        $keyBytes = array_reduce(
            $badX->normalize(),
            static fn (string $carry, string $item): string => $carry . chr((int) $item),
            ''
        );
        $correctX = ByteStringObject::create($keyBytes);
        $correctKey = MapObject::create()
            ->add(UnsignedIntegerObject::create(1), TextStringObject::create('OKP'))
            ->add(UnsignedIntegerObject::create(3), NegativeIntegerObject::create(-8))
            ->add(NegativeIntegerObject::create(-1), TextStringObject::create('Ed25519'))
            ->add(NegativeIntegerObject::create(-2), $correctX);

        return $begin . $correctKey;
    }
}
