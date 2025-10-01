<?php

declare(strict_types=1);

namespace Webauthn\CeremonyStep;

use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\Exception\AuthenticatorResponseVerificationException;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialSource;

class CheckTopOrigin implements CeremonyStep
{
    public function __construct(
        private readonly null|TopOriginValidator $topOriginValidator = null
    ) {
    }

    public function process(
        PublicKeyCredentialSource $publicKeyCredentialSource,
        AuthenticatorAssertionResponse|AuthenticatorAttestationResponse $authenticatorResponse,
        PublicKeyCredentialRequestOptions|PublicKeyCredentialCreationOptions $publicKeyCredentialOptions,
        ?string $userHandle,
        string $host
    ): void {
        $topOrigin = $authenticatorResponse->clientDataJSON->topOrigin;
        if ($topOrigin === null) {
            return;
        }
        if ($authenticatorResponse->clientDataJSON->crossOrigin !== true) {
            throw AuthenticatorResponseVerificationException::create('The response is not cross-origin.');
        }
        if ($this->topOriginValidator === null) {
            (new HostTopOriginValidator($host))->validate($topOrigin);
        } else {
            $this->topOriginValidator->validate($topOrigin);
        }
    }
}
