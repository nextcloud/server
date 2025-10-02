<?php

declare(strict_types=1);

namespace Webauthn\CeremonyStep;

use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialSource;

final class CeremonyStepManager
{
    /**
     * @param CeremonyStep[] $steps
     */
    public function __construct(
        private readonly array $steps
    ) {
    }

    public function process(
        PublicKeyCredentialSource $publicKeyCredentialSource,
        AuthenticatorAssertionResponse|AuthenticatorAttestationResponse $authenticatorResponse,
        PublicKeyCredentialRequestOptions|PublicKeyCredentialCreationOptions $publicKeyCredentialOptions,
        ?string $userHandle,
        string $host
    ): void {
        foreach ($this->steps as $step) {
            $step->process(
                $publicKeyCredentialSource,
                $authenticatorResponse,
                $publicKeyCredentialOptions,
                $userHandle,
                $host
            );
        }
    }
}
