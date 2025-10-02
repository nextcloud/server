<?php

declare(strict_types=1);

namespace Webauthn\CeremonyStep;

use CBOR\Decoder;
use CBOR\Normalizable;
use Cose\Algorithms;
use Cose\Key\Key;
use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\Exception\AuthenticatorResponseVerificationException;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\StringStream;
use Webauthn\U2FPublicKey;
use function count;
use function in_array;
use function is_array;

class CheckAlgorithm implements CeremonyStep
{
    public function process(
        PublicKeyCredentialSource $publicKeyCredentialSource,
        AuthenticatorAssertionResponse|AuthenticatorAttestationResponse $authenticatorResponse,
        PublicKeyCredentialRequestOptions|PublicKeyCredentialCreationOptions $publicKeyCredentialOptions,
        ?string $userHandle,
        string $host
    ): void {
        if (! $publicKeyCredentialOptions instanceof PublicKeyCredentialCreationOptions) {
            return;
        }
        $credentialPublicKey = $publicKeyCredentialSource->getAttestedCredentialData()
->credentialPublicKey;
        $credentialPublicKey !== null || throw AuthenticatorResponseVerificationException::create(
            'No public key available.'
        );
        $algorithms = array_map(
            fn ($pubKeyCredParam) => $pubKeyCredParam->alg,
            $publicKeyCredentialOptions->pubKeyCredParams
        );
        if (count($algorithms) === 0) {
            $algorithms = [Algorithms::COSE_ALGORITHM_ES256, Algorithms::COSE_ALGORITHM_RS256];
        }
        $coseKey = $this->getCoseKey($credentialPublicKey);
        in_array($coseKey->alg(), $algorithms, true) || throw AuthenticatorResponseVerificationException::create(
            sprintf('Invalid algorithm. Expected one of %s but got %d', implode(', ', $algorithms), $coseKey->alg())
        );
    }

    private function getCoseKey(string $credentialPublicKey): Key
    {
        $isU2F = U2FPublicKey::isU2FKey($credentialPublicKey);
        if ($isU2F === true) {
            $credentialPublicKey = U2FPublicKey::convertToCoseKey($credentialPublicKey);
        }
        $stream = new StringStream($credentialPublicKey);
        $credentialPublicKeyStream = Decoder::create()->decode($stream);
        $stream->isEOF() || throw AuthenticatorResponseVerificationException::create(
            'Invalid key. Presence of extra bytes.'
        );
        $stream->close();
        $credentialPublicKeyStream instanceof Normalizable || throw AuthenticatorResponseVerificationException::create(
            'Invalid attestation object. Unexpected object.'
        );
        $normalizedData = $credentialPublicKeyStream->normalize();
        is_array($normalizedData) || throw AuthenticatorResponseVerificationException::create(
            'Invalid attestation object. Unexpected object.'
        );
        /** @var array<int|string, mixed> $normalizedData */

        return Key::create($normalizedData);
    }
}
