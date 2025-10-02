<?php

declare(strict_types=1);

namespace Webauthn\CeremonyStep;

use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\Exception\InvalidUserHandleException;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialSource;

final class CheckUserHandle implements CeremonyStep
{
    public function process(
        PublicKeyCredentialSource $publicKeyCredentialSource,
        AuthenticatorAssertionResponse|AuthenticatorAttestationResponse $authenticatorResponse,
        PublicKeyCredentialRequestOptions|PublicKeyCredentialCreationOptions $publicKeyCredentialOptions,
        ?string $userHandle,
        string $host
    ): void {
        if (! $authenticatorResponse instanceof AuthenticatorAssertionResponse) {
            return;
        }
        $credentialUserHandle = $publicKeyCredentialSource->userHandle;
        $responseUserHandle = $authenticatorResponse->userHandle;
        if ($userHandle !== null) { //If the user was identified before the authentication ceremony was initiated,
            $credentialUserHandle === $userHandle || throw InvalidUserHandleException::create();
            if ($responseUserHandle !== null && $responseUserHandle !== '') {
                $credentialUserHandle === $responseUserHandle || throw InvalidUserHandleException::create();
            }
        } else {
            ($responseUserHandle !== '' && $credentialUserHandle === $responseUserHandle) || throw InvalidUserHandleException::create();
        }
    }
}
