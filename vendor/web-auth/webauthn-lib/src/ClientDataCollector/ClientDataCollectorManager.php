<?php

declare(strict_types=1);

namespace Webauthn\ClientDataCollector;

use Webauthn\AuthenticatorResponse;
use Webauthn\CollectedClientData;
use Webauthn\Exception\AuthenticatorResponseVerificationException;
use Webauthn\PublicKeyCredentialOptions;
use function in_array;

final class ClientDataCollectorManager
{
    /**
     * @param ClientDataCollector[] $clientDataCollectors
     */
    public function __construct(
        private readonly iterable $clientDataCollectors,
    ) {
    }

    public function collect(
        CollectedClientData $collectedClientData,
        PublicKeyCredentialOptions $publicKeyCredentialOptions,
        AuthenticatorResponse $authenticatorResponse,
        string $host
    ): void {
        foreach ($this->clientDataCollectors as $clientDataCollector) {
            if (in_array($collectedClientData->type, $clientDataCollector->supportedTypes(), true)) {
                $clientDataCollector->verifyCollectedClientData(
                    $collectedClientData,
                    $publicKeyCredentialOptions,
                    $authenticatorResponse,
                    $host
                );
                return;
            }
        }

        throw AuthenticatorResponseVerificationException::create('No client data collector found.');
    }
}
