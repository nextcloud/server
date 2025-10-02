<?php

declare(strict_types=1);

namespace Webauthn\ClientDataCollector;

use Webauthn\AuthenticatorResponse;
use Webauthn\CollectedClientData;
use Webauthn\PublicKeyCredentialOptions;

interface ClientDataCollector
{
    /**
     * @return string[]
     */
    public function supportedTypes(): array;

    public function verifyCollectedClientData(
        CollectedClientData $collectedClientData,
        PublicKeyCredentialOptions $publicKeyCredentialOptions,
        AuthenticatorResponse $authenticatorResponse,
        string $host
    ): void;
}
