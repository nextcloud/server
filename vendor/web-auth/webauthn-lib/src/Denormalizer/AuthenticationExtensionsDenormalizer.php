<?php

declare(strict_types=1);

namespace Webauthn\Denormalizer;

use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Webauthn\AuthenticationExtensions\AuthenticationExtension;
use Webauthn\AuthenticationExtensions\AuthenticationExtensions;
use Webauthn\AuthenticationExtensions\AuthenticationExtensionsClientInputs;
use Webauthn\AuthenticationExtensions\AuthenticationExtensionsClientOutputs;
use function assert;
use function in_array;
use function is_array;
use function is_string;

final class AuthenticationExtensionsDenormalizer implements DenormalizerInterface, NormalizerInterface
{
    public function denormalize(mixed $data, string $type, string $format = null, array $context = []): mixed
    {
        if ($data instanceof AuthenticationExtensions) {
            return AuthenticationExtensions::create($data->extensions);
        }
        assert(is_array($data), 'The data should be an array.');
        foreach ($data as $key => $value) {
            if (! is_string($key)) {
                continue;
            }
            $data[$key] = AuthenticationExtension::create($key, $value);
        }

        return AuthenticationExtensions::create($data);
    }

    public function supportsDenormalization(mixed $data, string $type, string $format = null, array $context = []): bool
    {
        return in_array(
            $type,
            [
                AuthenticationExtensions::class,
                AuthenticationExtensionsClientOutputs::class,
                AuthenticationExtensionsClientInputs::class,
            ],
            true
        );
    }

    /**
     * @return array<class-string, bool>
     */
    public function getSupportedTypes(?string $format): array
    {
        return [
            AuthenticationExtensions::class => true,
            AuthenticationExtensionsClientInputs::class => true,
            AuthenticationExtensionsClientOutputs::class => true,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function normalize(mixed $data, ?string $format = null, array $context = []): array
    {
        assert($data instanceof AuthenticationExtensions);
        $extensions = [];
        foreach ($data->extensions as $extension) {
            $extensions[$extension->name] = $extension->value;
        }

        return $extensions;
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof AuthenticationExtensions;
    }
}
