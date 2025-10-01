<?php

declare(strict_types=1);

namespace Webauthn\MetadataService\Denormalizer;

use Symfony\Component\Serializer\SerializerInterface;
use Webauthn\AttestationStatement\AttestationStatementSupportManager;
use Webauthn\Denormalizer\WebauthnSerializerFactory;

/**
 * @deprecated since 4.9.0 and will be removed in 5.0.0. Use Webauthn\Denormalizer\WebauthnSerializerFactory instead
 */
final class MetadataStatementSerializerFactory
{
    public static function create(): SerializerInterface
    {
        $attestationStatementSupportManager = AttestationStatementSupportManager::create();
        $factory = new WebauthnSerializerFactory($attestationStatementSupportManager);

        return $factory->create();
    }
}
