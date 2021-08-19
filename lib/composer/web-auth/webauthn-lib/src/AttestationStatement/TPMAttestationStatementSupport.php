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
use Base64Url\Base64Url;
use CBOR\Decoder;
use CBOR\MapObject;
use CBOR\OtherObject\OtherObjectManager;
use CBOR\Tag\TagObjectManager;
use Cose\Algorithms;
use Cose\Key\Ec2Key;
use Cose\Key\Key;
use Cose\Key\OkpKey;
use Cose\Key\RsaKey;
use function count;
use function in_array;
use InvalidArgumentException;
use function is_array;
use RuntimeException;
use Safe\DateTimeImmutable;
use function Safe\sprintf;
use function Safe\unpack;
use Webauthn\AuthenticatorData;
use Webauthn\CertificateToolbox;
use Webauthn\StringStream;
use Webauthn\TrustPath\CertificateTrustPath;
use Webauthn\TrustPath\EcdaaKeyIdTrustPath;

final class TPMAttestationStatementSupport implements AttestationStatementSupport
{
    public function name(): string
    {
        return 'tpm';
    }

    /**
     * @param mixed[] $attestation
     */
    public function load(array $attestation): AttestationStatement
    {
        Assertion::keyExists($attestation, 'attStmt', 'Invalid attestation object');
        Assertion::keyNotExists($attestation['attStmt'], 'ecdaaKeyId', 'ECDAA not supported');
        foreach (['ver', 'ver', 'sig', 'alg', 'certInfo', 'pubArea'] as $key) {
            Assertion::keyExists($attestation['attStmt'], $key, sprintf('The attestation statement value "%s" is missing.', $key));
        }
        Assertion::eq('2.0', $attestation['attStmt']['ver'], 'Invalid attestation object');

        $certInfo = $this->checkCertInfo($attestation['attStmt']['certInfo']);
        Assertion::eq('8017', bin2hex($certInfo['type']), 'Invalid attestation object');

        $pubArea = $this->checkPubArea($attestation['attStmt']['pubArea']);
        $pubAreaAttestedNameAlg = mb_substr($certInfo['attestedName'], 0, 2, '8bit');
        $pubAreaHash = hash($this->getTPMHash($pubAreaAttestedNameAlg), $attestation['attStmt']['pubArea'], true);
        $attestedName = $pubAreaAttestedNameAlg.$pubAreaHash;
        Assertion::eq($attestedName, $certInfo['attestedName'], 'Invalid attested name');

        $attestation['attStmt']['parsedCertInfo'] = $certInfo;
        $attestation['attStmt']['parsedPubArea'] = $pubArea;

        $certificates = CertificateToolbox::convertAllDERToPEM($attestation['attStmt']['x5c']);
        Assertion::minCount($certificates, 1, 'The attestation statement value "x5c" must be a list with at least one certificate.');

        return AttestationStatement::createAttCA(
            $this->name(),
            $attestation['attStmt'],
            new CertificateTrustPath($certificates)
        );
    }

    public function isValid(string $clientDataJSONHash, AttestationStatement $attestationStatement, AuthenticatorData $authenticatorData): bool
    {
        $attToBeSigned = $authenticatorData->getAuthData().$clientDataJSONHash;
        $attToBeSignedHash = hash(Algorithms::getHashAlgorithmFor((int) $attestationStatement->get('alg')), $attToBeSigned, true);
        Assertion::eq($attestationStatement->get('parsedCertInfo')['extraData'], $attToBeSignedHash, 'Invalid attestation hash');
        $this->checkUniquePublicKey(
            $attestationStatement->get('parsedPubArea')['unique'],
            $authenticatorData->getAttestedCredentialData()->getCredentialPublicKey()
        );

        switch (true) {
            case $attestationStatement->getTrustPath() instanceof CertificateTrustPath:
                return $this->processWithCertificate($clientDataJSONHash, $attestationStatement, $authenticatorData);
            case $attestationStatement->getTrustPath() instanceof EcdaaKeyIdTrustPath:
                return $this->processWithECDAA();
            default:
                throw new InvalidArgumentException('Unsupported attestation statement');
        }
    }

    private function checkUniquePublicKey(string $unique, string $cborPublicKey): void
    {
        $cborDecoder = new Decoder(new TagObjectManager(), new OtherObjectManager());
        $publicKey = $cborDecoder->decode(new StringStream($cborPublicKey));
        Assertion::isInstanceOf($publicKey, MapObject::class, 'Invalid public key');
        $key = new Key($publicKey->getNormalizedData(false));

        switch ($key->type()) {
            case Key::TYPE_OKP:
                $uniqueFromKey = (new OkpKey($key->getData()))->x();
                break;
            case Key::TYPE_EC2:
                $ec2Key = new Ec2Key($key->getData());
                $uniqueFromKey = "\x04".$ec2Key->x().$ec2Key->y();
                break;
            case Key::TYPE_RSA:
                $uniqueFromKey = (new RsaKey($key->getData()))->n();
                break;
            default:
                throw new InvalidArgumentException('Invalid or unsupported key type.');
        }

        Assertion::eq($unique, $uniqueFromKey, 'Invalid pubArea.unique value');
    }

    /**
     * @return mixed[]
     */
    private function checkCertInfo(string $data): array
    {
        $certInfo = new StringStream($data);

        $magic = $certInfo->read(4);
        Assertion::eq('ff544347', bin2hex($magic), 'Invalid attestation object');

        $type = $certInfo->read(2);

        $qualifiedSignerLength = unpack('n', $certInfo->read(2))[1];
        $qualifiedSigner = $certInfo->read($qualifiedSignerLength); //Ignored

        $extraDataLength = unpack('n', $certInfo->read(2))[1];
        $extraData = $certInfo->read($extraDataLength);

        $clockInfo = $certInfo->read(17); //Ignore

        $firmwareVersion = $certInfo->read(8);

        $attestedNameLength = unpack('n', $certInfo->read(2))[1];
        $attestedName = $certInfo->read($attestedNameLength);

        $attestedQualifiedNameLength = unpack('n', $certInfo->read(2))[1];
        $attestedQualifiedName = $certInfo->read($attestedQualifiedNameLength); //Ignore
        Assertion::true($certInfo->isEOF(), 'Invalid certificate information. Presence of extra bytes.');
        $certInfo->close();

        return [
            'magic' => $magic,
            'type' => $type,
            'qualifiedSigner' => $qualifiedSigner,
            'extraData' => $extraData,
            'clockInfo' => $clockInfo,
            'firmwareVersion' => $firmwareVersion,
            'attestedName' => $attestedName,
            'attestedQualifiedName' => $attestedQualifiedName,
        ];
    }

    /**
     * @return mixed[]
     */
    private function checkPubArea(string $data): array
    {
        $pubArea = new StringStream($data);

        $type = $pubArea->read(2);

        $nameAlg = $pubArea->read(2);

        $objectAttributes = $pubArea->read(4);

        $authPolicyLength = unpack('n', $pubArea->read(2))[1];
        $authPolicy = $pubArea->read($authPolicyLength);

        $parameters = $this->getParameters($type, $pubArea);

        $uniqueLength = unpack('n', $pubArea->read(2))[1];
        $unique = $pubArea->read($uniqueLength);
        Assertion::true($pubArea->isEOF(), 'Invalid public area. Presence of extra bytes.');
        $pubArea->close();

        return [
            'type' => $type,
            'nameAlg' => $nameAlg,
            'objectAttributes' => $objectAttributes,
            'authPolicy' => $authPolicy,
            'parameters' => $parameters,
            'unique' => $unique,
        ];
    }

    /**
     * @return mixed[]
     */
    private function getParameters(string $type, StringStream $stream): array
    {
        switch (bin2hex($type)) {
            case '0001':
            case '0014':
            case '0016':
                return [
                    'symmetric' => $stream->read(2),
                    'scheme' => $stream->read(2),
                    'keyBits' => unpack('n', $stream->read(2))[1],
                    'exponent' => $this->getExponent($stream->read(4)),
                ];
            case '0018':
                return [
                    'symmetric' => $stream->read(2),
                    'scheme' => $stream->read(2),
                    'curveId' => $stream->read(2),
                    'kdf' => $stream->read(2),
                ];
            default:
                throw new InvalidArgumentException('Unsupported type');
        }
    }

    private function getExponent(string $exponent): string
    {
        return '00000000' === bin2hex($exponent) ? Base64Url::decode('AQAB') : $exponent;
    }

    private function getTPMHash(string $nameAlg): string
    {
        switch (bin2hex($nameAlg)) {
            case '0004':
                return 'sha1'; //: "TPM_ALG_SHA1",
            case '000b':
                return 'sha256'; //: "TPM_ALG_SHA256",
            case '000c':
                return 'sha384'; //: "TPM_ALG_SHA384",
            case '000d':
                return 'sha512'; //: "TPM_ALG_SHA512",
            default:
                throw new InvalidArgumentException('Unsupported hash algorithm');
        }
    }

    private function processWithCertificate(string $clientDataJSONHash, AttestationStatement $attestationStatement, AuthenticatorData $authenticatorData): bool
    {
        $trustPath = $attestationStatement->getTrustPath();
        Assertion::isInstanceOf($trustPath, CertificateTrustPath::class, 'Invalid trust path');

        $certificates = $trustPath->getCertificates();

        // Check certificate CA chain and returns the Attestation Certificate
        $this->checkCertificate($certificates[0], $authenticatorData);

        // Get the COSE algorithm identifier and the corresponding OpenSSL one
        $coseAlgorithmIdentifier = (int) $attestationStatement->get('alg');
        $opensslAlgorithmIdentifier = Algorithms::getOpensslAlgorithmFor($coseAlgorithmIdentifier);

        $result = openssl_verify($attestationStatement->get('certInfo'), $attestationStatement->get('sig'), $certificates[0], $opensslAlgorithmIdentifier);

        return 1 === $result;
    }

    private function checkCertificate(string $attestnCert, AuthenticatorData $authenticatorData): void
    {
        $parsed = openssl_x509_parse($attestnCert);
        Assertion::isArray($parsed, 'Invalid certificate');

        //Check version
        Assertion::false(!isset($parsed['version']) || 2 !== $parsed['version'], 'Invalid certificate version');

        //Check subject field is empty
        Assertion::false(!isset($parsed['subject']) || !is_array($parsed['subject']) || 0 !== count($parsed['subject']), 'Invalid certificate name. The Subject should be empty');

        // Check period of validity
        Assertion::keyExists($parsed, 'validFrom_time_t', 'Invalid certificate start date.');
        Assertion::integer($parsed['validFrom_time_t'], 'Invalid certificate start date.');
        $startDate = (new DateTimeImmutable())->setTimestamp($parsed['validFrom_time_t']);
        Assertion::true($startDate < new DateTimeImmutable(), 'Invalid certificate start date.');

        Assertion::keyExists($parsed, 'validTo_time_t', 'Invalid certificate end date.');
        Assertion::integer($parsed['validTo_time_t'], 'Invalid certificate end date.');
        $endDate = (new DateTimeImmutable())->setTimestamp($parsed['validTo_time_t']);
        Assertion::true($endDate > new DateTimeImmutable(), 'Invalid certificate end date.');

        //Check extensions
        Assertion::false(!isset($parsed['extensions']) || !is_array($parsed['extensions']), 'Certificate extensions are missing');

        //Check subjectAltName
        Assertion::false(!isset($parsed['extensions']['subjectAltName']), 'The "subjectAltName" is missing');

        //Check extendedKeyUsage
        Assertion::false(!isset($parsed['extensions']['extendedKeyUsage']), 'The "subjectAltName" is missing');
        Assertion::eq($parsed['extensions']['extendedKeyUsage'], '2.23.133.8.3', 'The "extendedKeyUsage" is invalid');

        // id-fido-gen-ce-aaguid OID check
        Assertion::false(in_array('1.3.6.1.4.1.45724.1.1.4', $parsed['extensions'], true) && !hash_equals($authenticatorData->getAttestedCredentialData()->getAaguid()->getBytes(), $parsed['extensions']['1.3.6.1.4.1.45724.1.1.4']), 'The value of the "aaguid" does not match with the certificate');
    }

    private function processWithECDAA(): bool
    {
        throw new RuntimeException('ECDAA not supported');
    }
}
