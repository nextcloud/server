<?php

declare(strict_types=1);

namespace Webauthn\Denormalizer;

use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Webauthn\AttestationStatement\AttestationStatement;
use Webauthn\AttestationStatement\AttestationStatementSupportManager;

final class AttestationStatementDenormalizer implements DenormalizerInterface
{
    public function __construct(
        private readonly AttestationStatementSupportManager $attestationStatementSupportManager
    ) {
    }

    public function denormalize(mixed $data, string $type, string $format = null, array $context = []): mixed
    {
        $attestationStatementSupport = $this->attestationStatementSupportManager->get($data['fmt']);

        return $attestationStatementSupport->load($data);
    }

    public function supportsDenormalization(mixed $data, string $type, string $format = null, array $context = []): bool
    {
        return $type === AttestationStatement::class;
    }

    /**
     * @return array<class-string, bool>
     */
    public function getSupportedTypes(?string $format): array
    {
        return [
            AttestationStatement::class => true,
        ];
    }
}
