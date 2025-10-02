<?php

declare(strict_types=1);

namespace Webauthn\CeremonyStep;

use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\ClientDataCollector\ClientDataCollectorManager;
use Webauthn\ClientDataCollector\WebauthnAuthenticationCollector;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialSource;

final class CheckClientDataCollectorType implements CeremonyStep
{
    private readonly ClientDataCollectorManager $clientDataCollectorManager;

    public function __construct(
        null|ClientDataCollectorManager $clientDataCollectorManager = null,
    ) {
        $this->clientDataCollectorManager = $clientDataCollectorManager ?? new ClientDataCollectorManager([
            new WebauthnAuthenticationCollector(),
        ]);
    }

    public function process(
        PublicKeyCredentialSource $publicKeyCredentialSource,
        AuthenticatorAssertionResponse|AuthenticatorAttestationResponse $authenticatorResponse,
        PublicKeyCredentialRequestOptions|PublicKeyCredentialCreationOptions $publicKeyCredentialOptions,
        ?string $userHandle,
        string $host
    ): void {
        $this->clientDataCollectorManager->collect(
            $authenticatorResponse->clientDataJSON,
            $publicKeyCredentialOptions,
            $authenticatorResponse,
            $host
        );
    }
}
