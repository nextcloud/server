<?php

declare(strict_types=1);

namespace Webauthn\CeremonyStep;

use Webauthn\AuthenticationExtensions\AuthenticationExtensions;
use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\Exception\AuthenticatorResponseVerificationException;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialSource;
use function in_array;
use function is_array;
use function is_string;

final class CheckOrigin implements CeremonyStep
{
    /**
     * @param string[] $securedRelyingPartyId
     */
    public function __construct(
        private readonly array $securedRelyingPartyId
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
        $C = $authenticatorResponse->clientDataJSON;
        $rpId = $publicKeyCredentialOptions->rpId ?? $publicKeyCredentialOptions->rp->id ?? $host;
        $facetId = $this->getFacetId($rpId, $publicKeyCredentialOptions->extensions, $authData->extensions);
        $parsedRelyingPartyId = parse_url($C->origin);
        is_array($parsedRelyingPartyId) || throw AuthenticatorResponseVerificationException::create(
            'Invalid origin'
        );
        if (! in_array($facetId, $this->securedRelyingPartyId, true)) {
            $scheme = $parsedRelyingPartyId['scheme'] ?? '';
            $scheme === 'https' || throw AuthenticatorResponseVerificationException::create(
                'Invalid scheme. HTTPS required.'
            );
        }
        $clientDataRpId = $parsedRelyingPartyId['host'] ?? '';
        $clientDataRpId !== '' || throw AuthenticatorResponseVerificationException::create('Invalid origin rpId.');
        $rpIdLength = mb_strlen($facetId);

        mb_substr(
            '.' . $clientDataRpId,
            -($rpIdLength + 1)
        ) === '.' . $facetId || throw AuthenticatorResponseVerificationException::create('rpId mismatch.');
    }

    private function getFacetId(
        string $rpId,
        AuthenticationExtensions $authenticationExtensionsClientInputs,
        null|AuthenticationExtensions $authenticationExtensionsClientOutputs
    ): string {
        if ($authenticationExtensionsClientOutputs === null || ! $authenticationExtensionsClientInputs->has(
            'appid'
        ) || ! $authenticationExtensionsClientOutputs->has('appid')) {
            return $rpId;
        }
        $appId = $authenticationExtensionsClientInputs->get('appid')
            ->value;
        $wasUsed = $authenticationExtensionsClientOutputs->get('appid')
            ->value;
        if (! is_string($appId) || $wasUsed !== true) {
            return $rpId;
        }
        return $appId;
    }
}
