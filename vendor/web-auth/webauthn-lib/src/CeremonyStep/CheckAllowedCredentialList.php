<?php

declare(strict_types=1);

namespace Webauthn\CeremonyStep;

use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\Exception\AuthenticatorResponseVerificationException;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialSource;
use function count;

final class CheckAllowedCredentialList implements CeremonyStep
{
    public function process(
        PublicKeyCredentialSource $publicKeyCredentialSource,
        AuthenticatorAssertionResponse|AuthenticatorAttestationResponse $authenticatorResponse,
        PublicKeyCredentialRequestOptions|PublicKeyCredentialCreationOptions $publicKeyCredentialOptions,
        ?string $userHandle,
        string $host
    ): void {
        if (! $publicKeyCredentialOptions instanceof PublicKeyCredentialRequestOptions) {
            return;
        }
        if (count($publicKeyCredentialOptions->allowCredentials) === 0) {
            return;
        }

        foreach ($publicKeyCredentialOptions->allowCredentials as $allowedCredential) {
            if (hash_equals($allowedCredential->id, $publicKeyCredentialSource->publicKeyCredentialId)) {
                return;
            }
        }
        throw AuthenticatorResponseVerificationException::create('The credential ID is not allowed.');
    }
}
