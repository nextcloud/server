<?php

declare(strict_types=1);

namespace Webauthn\CeremonyStep;

use CBOR\Decoder;
use CBOR\Normalizable;
use Cose\Algorithm\Manager;
use Cose\Algorithm\Signature\ECDSA\ES256;
use Cose\Algorithm\Signature\RSA\RS256;
use Cose\Algorithm\Signature\Signature;
use Cose\Key\Key;
use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\Exception\AuthenticatorResponseVerificationException;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\StringStream;
use Webauthn\U2FPublicKey;
use Webauthn\Util\CoseSignatureFixer;
use function is_array;

final class CheckSignature implements CeremonyStep
{
    private readonly Manager $algorithmManager;

    public function __construct(
        null|Manager $algorithmManager = null,
    ) {
        $this->algorithmManager = $algorithmManager ?? Manager::create()->add(ES256::create(), RS256::create());
    }

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
        $credentialPublicKey = $publicKeyCredentialSource->getAttestedCredentialData()
->credentialPublicKey;
        $credentialPublicKey !== null || throw AuthenticatorResponseVerificationException::create(
            'No public key available.'
        );
        $coseKey = $this->getCoseKey($credentialPublicKey);

        $getClientDataJSONHash = hash('sha256', $authenticatorResponse->clientDataJSON->rawData, true);
        $dataToVerify = $authenticatorResponse->authenticatorData->authData . $getClientDataJSONHash;
        $signature = $authenticatorResponse->signature;
        $algorithm = $this->algorithmManager->get($coseKey->alg());
        $algorithm instanceof Signature || throw AuthenticatorResponseVerificationException::create(
            'Invalid algorithm identifier. Should refer to a signature algorithm'
        );
        $signature = CoseSignatureFixer::fix($signature, $algorithm);
        $algorithm->verify(
            $dataToVerify,
            $coseKey,
            $signature
        ) || throw AuthenticatorResponseVerificationException::create('Invalid signature.');
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
