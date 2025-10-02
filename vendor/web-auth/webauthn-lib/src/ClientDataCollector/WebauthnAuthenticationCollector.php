<?php

declare(strict_types=1);

namespace Webauthn\ClientDataCollector;

use Webauthn\AuthenticatorResponse;
use Webauthn\CollectedClientData;
use Webauthn\Exception\AuthenticatorResponseVerificationException;
use Webauthn\PublicKeyCredentialOptions;
use function in_array;

final class WebauthnAuthenticationCollector implements ClientDataCollector
{
    public function supportedTypes(): array
    {
        return ['webauthn.get', 'webauthn.create'];
    }

    public function verifyCollectedClientData(
        CollectedClientData $collectedClientData,
        PublicKeyCredentialOptions $publicKeyCredentialOptions,
        AuthenticatorResponse $authenticatorResponse,
        string $host
    ): void {
        in_array(
            $collectedClientData->type,
            $this->supportedTypes(),
            true
        ) || throw AuthenticatorResponseVerificationException::create(
            sprintf('The client data type is not "%s" supported.', implode('", "', $this->supportedTypes()))
        );
    }
}
