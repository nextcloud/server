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

use Assert\Assertion;
use CBOR\Decoder;
use CBOR\OtherObject\OtherObjectManager;
use CBOR\Tag\TagObjectManager;
use Cose\Key\Ec2Key;
use Cose\Key\Key;
use Cose\Key\RsaKey;
use function count;
use FG\ASN1\Universal\Sequence;
use function Safe\openssl_pkey_get_public;
use function Safe\sprintf;
use Webauthn\AuthenticatorData;
use Webauthn\CertificateToolbox;
use Webauthn\StringStream;
use Webauthn\TrustPath\CertificateTrustPath;

final class AppleAttestationStatementSupport implements AttestationStatementSupport
{
    /**
     * @var Decoder
     */
    private $decoder;

    public function __construct()
    {
        $this->decoder = new Decoder(new TagObjectManager(), new OtherObjectManager());
    }

    public function name(): string
    {
        return 'apple';
    }

    /**
     * @param mixed[] $attestation
     */
    public function load(array $attestation): AttestationStatement
    {
        Assertion::keyExists($attestation, 'attStmt', 'Invalid attestation object');
        foreach (['x5c'] as $key) {
            Assertion::keyExists($attestation['attStmt'], $key, sprintf('The attestation statement value "%s" is missing.', $key));
        }
        $certificates = $attestation['attStmt']['x5c'];
        Assertion::isArray($certificates, 'The attestation statement value "x5c" must be a list with at least one certificate.');
        Assertion::greaterThan(count($certificates), 0, 'The attestation statement value "x5c" must be a list with at least one certificate.');
        Assertion::allString($certificates, 'The attestation statement value "x5c" must be a list with at least one certificate.');
        $certificates = CertificateToolbox::convertAllDERToPEM($certificates);

        return AttestationStatement::createAnonymizationCA($attestation['fmt'], $attestation['attStmt'], new CertificateTrustPath($certificates));
    }

    public function isValid(string $clientDataJSONHash, AttestationStatement $attestationStatement, AuthenticatorData $authenticatorData): bool
    {
        $trustPath = $attestationStatement->getTrustPath();
        Assertion::isInstanceOf($trustPath, CertificateTrustPath::class, 'Invalid trust path');

        $certificates = $trustPath->getCertificates();

        //Decode leaf attestation certificate
        $leaf = $certificates[0];

        $this->checkCertificateAndGetPublicKey($leaf, $clientDataJSONHash, $authenticatorData);

        return true;
    }

    private function checkCertificateAndGetPublicKey(string $certificate, string $clientDataHash, AuthenticatorData $authenticatorData): void
    {
        $resource = openssl_pkey_get_public($certificate);
        $details = openssl_pkey_get_details($resource);
        Assertion::isArray($details, 'Unable to read the certificate');

        //Check that authData publicKey matches the public key in the attestation certificate
        $attestedCredentialData = $authenticatorData->getAttestedCredentialData();
        Assertion::notNull($attestedCredentialData, 'No attested credential data found');
        $publicKeyData = $attestedCredentialData->getCredentialPublicKey();
        Assertion::notNull($publicKeyData, 'No attested public key found');
        $publicDataStream = new StringStream($publicKeyData);
        $coseKey = $this->decoder->decode($publicDataStream)->getNormalizedData(false);
        Assertion::true($publicDataStream->isEOF(), 'Invalid public key data. Presence of extra bytes.');
        $publicDataStream->close();
        $publicKey = Key::createFromData($coseKey);

        Assertion::true(($publicKey instanceof Ec2Key) || ($publicKey instanceof RsaKey), 'Unsupported key type');

        //We check the attested key corresponds to the key in the certificate
        Assertion::eq($publicKey->asPEM(), $details['key'], 'Invalid key');

        /*---------------------------*/
        $certDetails = openssl_x509_parse($certificate);

        //Find Apple Extension with OID “1.2.840.113635.100.8.2” in certificate extensions
        Assertion::isArray($certDetails, 'The certificate is not valid');
        Assertion::keyExists($certDetails, 'extensions', 'The certificate has no extension');
        Assertion::isArray($certDetails['extensions'], 'The certificate has no extension');
        Assertion::keyExists($certDetails['extensions'], '1.2.840.113635.100.8.2', 'The certificate extension "1.2.840.113635.100.8.2" is missing');
        $extension = $certDetails['extensions']['1.2.840.113635.100.8.2'];

        $nonceToHash = $authenticatorData->getAuthData().$clientDataHash;
        $nonce = hash('sha256', $nonceToHash);

        //'3024a1220420' corresponds to the Sequence+Explicitly Tagged Object + Octet Object
        Assertion::eq('3024a1220420'.$nonce, bin2hex($extension), 'The client data hash is not valid');
    }
}
