<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2020 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace Webauthn\AttestationStatement;

use function array_key_exists;
use Assert\Assertion;
use CBOR\Decoder;
use CBOR\MapObject;
use CBOR\OtherObject\OtherObjectManager;
use CBOR\Tag\TagObjectManager;
use Cose\Algorithm\Manager;
use Cose\Algorithm\Signature\Signature;
use Cose\Algorithms;
use Cose\Key\Key;
use function in_array;
use InvalidArgumentException;
use function is_array;
use RuntimeException;
use Webauthn\AuthenticatorData;
use Webauthn\CertificateToolbox;
use Webauthn\StringStream;
use Webauthn\TrustPath\CertificateTrustPath;
use Webauthn\TrustPath\EcdaaKeyIdTrustPath;
use Webauthn\TrustPath\EmptyTrustPath;
use Webauthn\Util\CoseSignatureFixer;

final class PackedAttestationStatementSupport implements AttestationStatementSupport
{
    /**
     * @var Decoder
     */
    private $decoder;

    /**
     * @var Manager
     */
    private $algorithmManager;

    public function __construct(Manager $algorithmManager)
    {
        $this->decoder = new Decoder(new TagObjectManager(), new OtherObjectManager());
        $this->algorithmManager = $algorithmManager;
    }

    public function name(): string
    {
        return 'packed';
    }

    /**
     * @param mixed[] $attestation
     */
    public function load(array $attestation): AttestationStatement
    {
        Assertion::keyExists($attestation['attStmt'], 'sig', 'The attestation statement value "sig" is missing.');
        Assertion::keyExists($attestation['attStmt'], 'alg', 'The attestation statement value "alg" is missing.');
        Assertion::string($attestation['attStmt']['sig'], 'The attestation statement value "sig" is missing.');
        switch (true) {
            case array_key_exists('x5c', $attestation['attStmt']):
                return $this->loadBasicType($attestation);
            case array_key_exists('ecdaaKeyId', $attestation['attStmt']):
                return $this->loadEcdaaType($attestation['attStmt']);
            default:
                return $this->loadEmptyType($attestation);
        }
    }

    public function isValid(string $clientDataJSONHash, AttestationStatement $attestationStatement, AuthenticatorData $authenticatorData): bool
    {
        $trustPath = $attestationStatement->getTrustPath();
        switch (true) {
            case $trustPath instanceof CertificateTrustPath:
                return $this->processWithCertificate($clientDataJSONHash, $attestationStatement, $authenticatorData, $trustPath);
            case $trustPath instanceof EcdaaKeyIdTrustPath:
                return $this->processWithECDAA();
            case $trustPath instanceof EmptyTrustPath:
                return $this->processWithSelfAttestation($clientDataJSONHash, $attestationStatement, $authenticatorData);
            default:
                throw new InvalidArgumentException('Unsupported attestation statement');
        }
    }

    /**
     * @param mixed[] $attestation
     */
    private function loadBasicType(array $attestation): AttestationStatement
    {
        $certificates = $attestation['attStmt']['x5c'];
        Assertion::isArray($certificates, 'The attestation statement value "x5c" must be a list with at least one certificate.');
        Assertion::minCount($certificates, 1, 'The attestation statement value "x5c" must be a list with at least one certificate.');
        $certificates = CertificateToolbox::convertAllDERToPEM($certificates);

        return AttestationStatement::createBasic($attestation['fmt'], $attestation['attStmt'], new CertificateTrustPath($certificates));
    }

    private function loadEcdaaType(array $attestation): AttestationStatement
    {
        $ecdaaKeyId = $attestation['attStmt']['ecdaaKeyId'];
        Assertion::string($ecdaaKeyId, 'The attestation statement value "ecdaaKeyId" is invalid.');

        return AttestationStatement::createEcdaa($attestation['fmt'], $attestation['attStmt'], new EcdaaKeyIdTrustPath($attestation['ecdaaKeyId']));
    }

    /**
     * @param mixed[] $attestation
     */
    private function loadEmptyType(array $attestation): AttestationStatement
    {
        return AttestationStatement::createSelf($attestation['fmt'], $attestation['attStmt'], new EmptyTrustPath());
    }

    private function checkCertificate(string $attestnCert, AuthenticatorData $authenticatorData): void
    {
        $parsed = openssl_x509_parse($attestnCert);
        Assertion::isArray($parsed, 'Invalid certificate');

        //Check version
        Assertion::false(!isset($parsed['version']) || 2 !== $parsed['version'], 'Invalid certificate version');

        //Check subject field
        Assertion::false(!isset($parsed['name']) || false === mb_strpos($parsed['name'], '/OU=Authenticator Attestation'), 'Invalid certificate name. The Subject Organization Unit must be "Authenticator Attestation"');

        //Check extensions
        Assertion::false(!isset($parsed['extensions']) || !is_array($parsed['extensions']), 'Certificate extensions are missing');

        //Check certificate is not a CA cert
        Assertion::false(!isset($parsed['extensions']['basicConstraints']) || 'CA:FALSE' !== $parsed['extensions']['basicConstraints'], 'The Basic Constraints extension must have the CA component set to false');

        $attestedCredentialData = $authenticatorData->getAttestedCredentialData();
        Assertion::notNull($attestedCredentialData, 'No attested credential available');

        // id-fido-gen-ce-aaguid OID check
        Assertion::false(in_array('1.3.6.1.4.1.45724.1.1.4', $parsed['extensions'], true) && !hash_equals($attestedCredentialData->getAaguid()->getBytes(), $parsed['extensions']['1.3.6.1.4.1.45724.1.1.4']), 'The value of the "aaguid" does not match with the certificate');
    }

    private function processWithCertificate(string $clientDataJSONHash, AttestationStatement $attestationStatement, AuthenticatorData $authenticatorData, CertificateTrustPath $trustPath): bool
    {
        $certificates = $trustPath->getCertificates();

        // Check leaf certificate
        $this->checkCertificate($certificates[0], $authenticatorData);

        // Get the COSE algorithm identifier and the corresponding OpenSSL one
        $coseAlgorithmIdentifier = (int) $attestationStatement->get('alg');
        $opensslAlgorithmIdentifier = Algorithms::getOpensslAlgorithmFor($coseAlgorithmIdentifier);

        // Verification of the signature
        $signedData = $authenticatorData->getAuthData().$clientDataJSONHash;
        $result = openssl_verify($signedData, $attestationStatement->get('sig'), $certificates[0], $opensslAlgorithmIdentifier);

        return 1 === $result;
    }

    private function processWithECDAA(): bool
    {
        throw new RuntimeException('ECDAA not supported');
    }

    private function processWithSelfAttestation(string $clientDataJSONHash, AttestationStatement $attestationStatement, AuthenticatorData $authenticatorData): bool
    {
        $attestedCredentialData = $authenticatorData->getAttestedCredentialData();
        Assertion::notNull($attestedCredentialData, 'No attested credential available');
        $credentialPublicKey = $attestedCredentialData->getCredentialPublicKey();
        Assertion::notNull($credentialPublicKey, 'No credential public key available');
        $publicKeyStream = new StringStream($credentialPublicKey);
        $publicKey = $this->decoder->decode($publicKeyStream);
        Assertion::true($publicKeyStream->isEOF(), 'Invalid public key. Presence of extra bytes.');
        $publicKeyStream->close();
        Assertion::isInstanceOf($publicKey, MapObject::class, 'The attested credential data does not contain a valid public key.');
        $publicKey = $publicKey->getNormalizedData(false);
        $publicKey = new Key($publicKey);
        Assertion::eq($publicKey->alg(), (int) $attestationStatement->get('alg'), 'The algorithm of the attestation statement and the key are not identical.');

        $dataToVerify = $authenticatorData->getAuthData().$clientDataJSONHash;
        $algorithm = $this->algorithmManager->get((int) $attestationStatement->get('alg'));
        if (!$algorithm instanceof Signature) {
            throw new RuntimeException('Invalid algorithm');
        }
        $signature = CoseSignatureFixer::fix($attestationStatement->get('sig'), $algorithm);

        return $algorithm->verify($dataToVerify, $publicKey, $signature);
    }
}
