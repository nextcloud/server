<?php

declare(strict_types=1);

namespace Webauthn\Denormalizer;

use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Webauthn\AuthenticationExtensions\AuthenticationExtension;
use function assert;

final class AuthenticationExtensionNormalizer implements NormalizerInterface
{
    /**
     * @return array<class-string, bool>
     */
    public function getSupportedTypes(?string $format): array
    {
        return [
            AuthenticationExtension::class => true,
        ];
    }

    /**
     * @return array<mixed>
     */
    public function normalize(mixed $data, ?string $format = null, array $context = []): array
    {
        assert($data instanceof AuthenticationExtension);

        return $data->value;
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof AuthenticationExtension;
    }
}
