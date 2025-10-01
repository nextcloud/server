<?php

declare(strict_types=1);

namespace Webauthn\CeremonyStep;

use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\Counter\CounterChecker;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialSource;

final class CheckCounter implements CeremonyStep
{
    public function __construct(
        private readonly CounterChecker $counterChecker
    ) {
    }

    public function process(
        PublicKeyCredentialSource $publicKeyCredentialSource,
        AuthenticatorAssertionResponse|AuthenticatorAttestationResponse $authenticatorResponse,
        PublicKeyCredentialRequestOptions|PublicKeyCredentialCreationOptions $publicKeyCredentialOptions,
        ?string $userHandle,
        string $host
    ): void {
        $authData = $authenticatorResponse instanceof AuthenticatorAssertionResponse ? $authenticatorResponse->authenticatorData : $authenticatorResponse->attestationObject->authData;
        $storedCounter = $publicKeyCredentialSource->counter;
        $responseCounter = $authData->signCount;
        if ($responseCounter !== 0 || $storedCounter !== 0) {
            $this->counterChecker->check($publicKeyCredentialSource, $responseCounter);
        }
        $publicKeyCredentialSource->counter = $responseCounter;
    }
}
