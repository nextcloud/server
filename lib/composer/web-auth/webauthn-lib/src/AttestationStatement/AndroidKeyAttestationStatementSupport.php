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
use Cose\Algorithms;
use Cose\Key\Ec2Key;
use Cose\Key\Key;
use Cose\Key\RsaKey;
use function count;
use FG\ASN1\ASNObject;
use FG\ASN1\ExplicitlyTaggedObject;
use FG\ASN1\Universal\OctetString;
use FG\ASN1\Universal\Sequence;
use function Safe\hex2bin;
use function Safe\openssl_pkey_get_public;
use function Safe\sprintf;
use Webauthn\AuthenticatorData;
use Webauthn\CertificateToolbox;
use Webauthn\StringStream;
use Webauthn\TrustPath\CertificateTrustPath;

final class AndroidKeyAttestationStatementSupport implements AttestationStatementSupport
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
        return 'android-key';
    }

    /**
     * @param mixed[] $attestation
     */
    public function load(array $attestation): AttestationStatement
    {
        Assertion::keyExists($attestation, 'attStmt', 'Invalid attestation object');
        foreach (['sig', 'x5c', 'alg'] as $key) {
            Assertion::keyExists($attestation['attStmt'], $key, sprintf('The attestation statement value "%s" is missing.', $key));
        }
        $certificates = $attestation['attStmt']['x5c'];
        Assertion::isArray($certificates, 'The attestation statement value "x5c" must be a list with at least one certificate.');
        Assertion::greaterThan(count($certificates), 0, 'The attestation statement value "x5c" must be a list with at least one certificate.');
        Assertion::allString($certificates, 'The attestation statement value "x5c" must be a list with at least one certificate.');
        $certificates = CertificateToolbox::convertAllDERToPEM($certificates);

        return AttestationStatement::createBasic($attestation['fmt'], $attestation['attStmt'], new CertificateTrustPath($certificates));
    }

    public function isValid(string $clientDataJSONHash, AttestationStatement $attestationStatement, AuthenticatorData $authenticatorData): bool
    {
        $trustPath = $attestationStatement->getTrustPath();
        Assertion::isInstanceOf($trustPath, CertificateTrustPath::class, 'Invalid trust path');

        $certificates = $trustPath->getCertificates();

        //Decode leaf attestation certificate
        $leaf = $certificates[0];
        $this->checkCertificateAndGetPublicKey($leaf, $clientDataJSONHash, $authenticatorData);

        $signedData = $authenticatorData->getAuthData().$clientDataJSONHash;
        $alg = $attestationStatement->get('alg');

        return 1 === openssl_verify($signedData, $attestationStatement->get('sig'), $leaf, Algorithms::getOpensslAlgorithmFor((int) $alg));
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
        Assertion::eq($publicKey->asPEM(), $details['key'], 'Invalid key');

        /*---------------------------*/
        $certDetails = openssl_x509_parse($certificate);

        //Find Android KeyStore Extension with OID “1.3.6.1.4.1.11129.2.1.17” in certificate extensions
        Assertion::isArray($certDetails, 'The certificate is not valid');
        Assertion::keyExists($certDetails, 'extensions', 'The certificate has no extension');
        Assertion::isArray($certDetails['extensions'], 'The certificate has no extension');
        Assertion::keyExists($certDetails['extensions'], '1.3.6.1.4.1.11129.2.1.17', 'The certificate extension "1.3.6.1.4.1.11129.2.1.17" is missing');
        $extension = $certDetails['extensions']['1.3.6.1.4.1.11129.2.1.17'];
        $extensionAsAsn1 = ASNObject::fromBinary($extension);
        Assertion::isInstanceOf($extensionAsAsn1, Sequence::class, 'The certificate extension "1.3.6.1.4.1.11129.2.1.17" is invalid');
        $objects = $extensionAsAsn1->getChildren();

        //Check that attestationChallenge is set to the clientDataHash.
        Assertion::keyExists($objects, 4, 'The certificate extension "1.3.6.1.4.1.11129.2.1.17" is invalid');
        Assertion::isInstanceOf($objects[4], OctetString::class, 'The certificate extension "1.3.6.1.4.1.11129.2.1.17" is invalid');
        Assertion::eq($clientDataHash, hex2bin(($objects[4])->getContent()), 'The client data hash is not valid');

        //Check that both teeEnforced and softwareEnforced structures don’t contain allApplications(600) tag.
        Assertion::keyExists($objects, 6, 'The certificate extension "1.3.6.1.4.1.11129.2.1.17" is invalid');
        $softwareEnforcedFlags = $objects[6];
        Assertion::isInstanceOf($softwareEnforcedFlags, Sequence::class, 'The certificate extension "1.3.6.1.4.1.11129.2.1.17" is invalid');
        $this->checkAbsenceOfAllApplicationsTag($softwareEnforcedFlags);

        Assertion::keyExists($objects, 7, 'The certificate extension "1.3.6.1.4.1.11129.2.1.17" is invalid');
        $teeEnforcedFlags = $objects[6];
        Assertion::isInstanceOf($teeEnforcedFlags, Sequence::class, 'The certificate extension "1.3.6.1.4.1.11129.2.1.17" is invalid');
        $this->checkAbsenceOfAllApplicationsTag($teeEnforcedFlags);
    }

    private function checkAbsenceOfAllApplicationsTag(Sequence $sequence): void
    {
        foreach ($sequence->getChildren() as $tag) {
            Assertion::isInstanceOf($tag, ExplicitlyTaggedObject::class, 'Invalid tag');
            /* @var ExplicitlyTaggedObject $tag */
            Assertion::notEq(600, (int) $tag->getTag(), 'Forbidden tag 600 found');
        }
    }
}
