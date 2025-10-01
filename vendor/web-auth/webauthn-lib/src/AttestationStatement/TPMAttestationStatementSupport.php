<?php

declare(strict_types=1);

namespace Webauthn\AttestationStatement;

use CBOR\Decoder;
use CBOR\MapObject;
use Cose\Algorithms;
use Cose\Key\Ec2Key;
use Cose\Key\Key;
use Cose\Key\OkpKey;
use Cose\Key\RsaKey;
use DateTimeImmutable;
use DateTimeZone;
use Lcobucci\Clock\Clock;
use Lcobucci\Clock\SystemClock;
use ParagonIE\ConstantTime\Base64UrlSafe;
use Psr\Clock\ClockInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Webauthn\AuthenticatorData;
use Webauthn\Event\AttestationStatementLoaded;
use Webauthn\Event\CanDispatchEvents;
use Webauthn\Event\NullEventDispatcher;
use Webauthn\Exception\AttestationStatementLoadingException;
use Webauthn\Exception\AttestationStatementVerificationException;
use Webauthn\Exception\InvalidAttestationStatementException;
use Webauthn\Exception\UnsupportedFeatureException;
use Webauthn\MetadataService\CertificateChain\CertificateToolbox;
use Webauthn\StringStream;
use Webauthn\TrustPath\CertificateTrustPath;
use Webauthn\TrustPath\EcdaaKeyIdTrustPath;
use function array_key_exists;
use function count;
use function in_array;
use function is_array;
use function is_int;
use function openssl_verify;
use function unpack;

final class TPMAttestationStatementSupport implements AttestationStatementSupport, CanDispatchEvents
{
    private readonly Clock|ClockInterface $clock;

    private EventDispatcherInterface $dispatcher;

    public function __construct(null|Clock|ClockInterface $clock = null)
    {
        if ($clock === null) {
            trigger_deprecation(
                'web-auth/metadata-service',
                '4.5.0',
                'The parameter "$clock" will become mandatory in 5.0.0. Please set a valid PSR Clock implementation instead of "null".'
            );
            $clock = new SystemClock(new DateTimeZone('UTC'));
        }
        $this->clock = $clock;
        $this->dispatcher = new NullEventDispatcher();
    }

    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher): void
    {
        $this->dispatcher = $eventDispatcher;
    }

    public static function create(null|Clock|ClockInterface $clock = null): self
    {
        return new self($clock);
    }

    public function name(): string
    {
        return 'tpm';
    }

    /**
     * @param array<string, mixed> $attestation
     */
    public function load(array $attestation): AttestationStatement
    {
        array_key_exists('attStmt', $attestation) || throw AttestationStatementLoadingException::create(
            $attestation,
            'Invalid attestation object'
        );
        ! array_key_exists(
            'ecdaaKeyId',
            $attestation['attStmt']
        ) || throw AttestationStatementLoadingException::create($attestation, 'ECDAA not supported');
        foreach (['ver', 'ver', 'sig', 'alg', 'certInfo', 'pubArea'] as $key) {
            array_key_exists($key, $attestation['attStmt']) || throw AttestationStatementLoadingException::create(
                $attestation,
                sprintf('The attestation statement value "%s" is missing.', $key)
            );
        }
        $attestation['attStmt']['ver'] === '2.0' || throw AttestationStatementLoadingException::create(
            $attestation,
            'Invalid attestation object'
        );

        $certInfo = $this->checkCertInfo($attestation['attStmt']['certInfo']);
        bin2hex((string) $certInfo['type']) === '8017' || throw AttestationStatementLoadingException::create(
            $attestation,
            'Invalid attestation object'
        );

        $pubArea = $this->checkPubArea($attestation['attStmt']['pubArea']);
        $pubAreaAttestedNameAlg = mb_substr((string) $certInfo['attestedName'], 0, 2, '8bit');
        $pubAreaHash = hash(
            $this->getTPMHash($pubAreaAttestedNameAlg),
            (string) $attestation['attStmt']['pubArea'],
            true
        );
        $attestedName = $pubAreaAttestedNameAlg . $pubAreaHash;
        $attestedName === $certInfo['attestedName'] || throw AttestationStatementLoadingException::create(
            $attestation,
            'Invalid attested name'
        );

        $attestation['attStmt']['parsedCertInfo'] = $certInfo;
        $attestation['attStmt']['parsedPubArea'] = $pubArea;

        $certificates = CertificateToolbox::convertAllDERToPEM($attestation['attStmt']['x5c']);
        count($certificates) > 0 || throw AttestationStatementLoadingException::create(
            $attestation,
            'The attestation statement value "x5c" must be a list with at least one certificate.'
        );

        $attestationStatement = AttestationStatement::createAttCA(
            $this->name(),
            $attestation['attStmt'],
            CertificateTrustPath::create($certificates)
        );
        $this->dispatcher->dispatch(AttestationStatementLoaded::create($attestationStatement));

        return $attestationStatement;
    }

    public function isValid(
        string $clientDataJSONHash,
        AttestationStatement $attestationStatement,
        AuthenticatorData $authenticatorData
    ): bool {
        $attToBeSigned = $authenticatorData->authData . $clientDataJSONHash;
        $attToBeSignedHash = hash(
            Algorithms::getHashAlgorithmFor((int) $attestationStatement->get('alg')),
            $attToBeSigned,
            true
        );
        $attestationStatement->get(
            'parsedCertInfo'
        )['extraData'] === $attToBeSignedHash || throw InvalidAttestationStatementException::create(
            $attestationStatement,
            'Invalid attestation hash'
        );
        $credentialPublicKey = $authenticatorData->attestedCredentialData?->credentialPublicKey;
        $credentialPublicKey !== null || throw InvalidAttestationStatementException::create(
            $attestationStatement,
            'Not credential public key available in the attested credential data'
        );
        $this->checkUniquePublicKey($attestationStatement->get('parsedPubArea')['unique'], $credentialPublicKey);

        return match (true) {
            $attestationStatement->trustPath instanceof CertificateTrustPath => $this->processWithCertificate(
                $attestationStatement,
                $authenticatorData
            ),
            $attestationStatement->trustPath instanceof EcdaaKeyIdTrustPath => $this->processWithECDAA(),
            default => throw InvalidAttestationStatementException::create(
                $attestationStatement,
                'Unsupported attestation statement'
            ),
        };
    }

    private function checkUniquePublicKey(string $unique, string $cborPublicKey): void
    {
        $cborDecoder = Decoder::create();
        $publicKey = $cborDecoder->decode(new StringStream($cborPublicKey));
        $publicKey instanceof MapObject || throw AttestationStatementVerificationException::create(
            'Invalid public key'
        );
        $key = Key::create($publicKey->normalize());

        switch ($key->type()) {
            case Key::TYPE_OKP:
                $uniqueFromKey = (new OkpKey($key->getData()))->x();
                break;
            case Key::TYPE_EC2:
                $ec2Key = new Ec2Key($key->getData());
                $uniqueFromKey = "\x04" . $ec2Key->x() . $ec2Key->y();
                break;
            case Key::TYPE_RSA:
                $uniqueFromKey = (new RsaKey($key->getData()))->n();
                break;
            default:
                throw AttestationStatementVerificationException::create('Invalid or unsupported key type.');
        }

        $unique === $uniqueFromKey || throw AttestationStatementVerificationException::create(
            'Invalid pubArea.unique value'
        );
    }

    /**
     * @return mixed[]
     */
    private function checkCertInfo(string $data): array
    {
        $certInfo = new StringStream($data);

        $magic = $certInfo->read(4);
        bin2hex($magic) === 'ff544347' || throw AttestationStatementVerificationException::create(
            'Invalid attestation object'
        );

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
        $certInfo->isEOF() || throw AttestationStatementVerificationException::create(
            'Invalid certificate information. Presence of extra bytes.'
        );
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

        $unique = $this->getUnique($type, $pubArea);
        $pubArea->isEOF() || throw AttestationStatementVerificationException::create(
            'Invalid public area. Presence of extra bytes.'
        );
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
        return match (bin2hex($type)) {
            '0001' => [
                'symmetric' => $stream->read(2),
                'scheme' => $stream->read(2),
                'keyBits' => unpack('n', $stream->read(2))[1],
                'exponent' => $this->getExponent($stream->read(4)),
            ],
            '0023' => [
                'symmetric' => $stream->read(2),
                'scheme' => $stream->read(2),
                'curveId' => $stream->read(2),
                'kdf' => $stream->read(2),
            ],
            default => throw AttestationStatementVerificationException::create('Unsupported type'),
        };
    }

    private function getUnique(string $type, StringStream $stream): string
    {
        switch (bin2hex($type)) {
            case '0001':
                $uniqueLength = unpack('n', $stream->read(2))[1];
                return $stream->read($uniqueLength);
            case '0023':
                $xLen = unpack('n', $stream->read(2))[1];
                $x = $stream->read($xLen);
                $yLen = unpack('n', $stream->read(2))[1];
                $y = $stream->read($yLen);
                return "\04" . $x . $y;
            default:
                throw AttestationStatementVerificationException::create('Unsupported type');
        }
    }

    private function getExponent(string $exponent): string
    {
        return bin2hex($exponent) === '00000000' ? Base64UrlSafe::decodeNoPadding('AQAB') : $exponent;
    }

    private function getTPMHash(string $nameAlg): string
    {
        return match (bin2hex($nameAlg)) {
            '0004' => 'sha1',
            '000b' => 'sha256',
            '000c' => 'sha384',
            '000d' => 'sha512',
            default => throw AttestationStatementVerificationException::create('Unsupported hash algorithm'),
        };
    }

    private function processWithCertificate(
        AttestationStatement $attestationStatement,
        AuthenticatorData $authenticatorData
    ): bool {
        $trustPath = $attestationStatement->trustPath;
        $trustPath instanceof CertificateTrustPath || throw AttestationStatementVerificationException::create(
            'Invalid trust path'
        );

        $certificates = $trustPath->certificates;

        // Check certificate CA chain and returns the Attestation Certificate
        $this->checkCertificate($certificates[0], $authenticatorData);

        // Get the COSE algorithm identifier and the corresponding OpenSSL one
        $coseAlgorithmIdentifier = (int) $attestationStatement->get('alg');
        $opensslAlgorithmIdentifier = Algorithms::getOpensslAlgorithmFor($coseAlgorithmIdentifier);

        $result = openssl_verify(
            $attestationStatement->get('certInfo'),
            $attestationStatement->get('sig'),
            $certificates[0],
            $opensslAlgorithmIdentifier
        );

        return $result === 1;
    }

    private function checkCertificate(string $attestnCert, AuthenticatorData $authenticatorData): void
    {
        $parsed = openssl_x509_parse($attestnCert);
        is_array($parsed) || throw AttestationStatementVerificationException::create('Invalid certificate');

        //Check version
        (isset($parsed['version']) && $parsed['version'] === 2) || throw AttestationStatementVerificationException::create(
            'Invalid certificate version'
        );

        //Check subject field is empty
        isset($parsed['subject']) || throw AttestationStatementVerificationException::create(
            'Invalid certificate name. The Subject should be empty'
        );
        is_array($parsed['subject']) || throw AttestationStatementVerificationException::create(
            'Invalid certificate name. The Subject should be empty'
        );
        count($parsed['subject']) === 0 || throw AttestationStatementVerificationException::create(
            'Invalid certificate name. The Subject should be empty'
        );

        // Check period of validity
        array_key_exists(
            'validFrom_time_t',
            $parsed
        ) || throw AttestationStatementVerificationException::create('Invalid certificate start date.');
        is_int($parsed['validFrom_time_t']) || throw AttestationStatementVerificationException::create(
            'Invalid certificate start date.'
        );
        $startDate = (new DateTimeImmutable())->setTimestamp($parsed['validFrom_time_t']);
        $startDate < $this->clock->now() || throw AttestationStatementVerificationException::create(
            'Invalid certificate start date.'
        );

        array_key_exists('validTo_time_t', $parsed) || throw AttestationStatementVerificationException::create(
            'Invalid certificate end date.'
        );
        is_int($parsed['validTo_time_t']) || throw AttestationStatementVerificationException::create(
            'Invalid certificate end date.'
        );
        $endDate = (new DateTimeImmutable())->setTimestamp($parsed['validTo_time_t']);
        $endDate > $this->clock->now() || throw AttestationStatementVerificationException::create(
            'Invalid certificate end date.'
        );

        //Check extensions
        (isset($parsed['extensions']) && is_array(
            $parsed['extensions']
        )) || throw AttestationStatementVerificationException::create('Certificate extensions are missing');

        //Check subjectAltName
        isset($parsed['extensions']['subjectAltName']) || throw AttestationStatementVerificationException::create(
            'The "subjectAltName" is missing'
        );

        //Check extendedKeyUsage
        isset($parsed['extensions']['extendedKeyUsage']) || throw AttestationStatementVerificationException::create(
            'The "subjectAltName" is missing'
        );
        $parsed['extensions']['extendedKeyUsage'] === '2.23.133.8.3' || throw AttestationStatementVerificationException::create(
            'The "extendedKeyUsage" is invalid'
        );

        // id-fido-gen-ce-aaguid OID check
        in_array('1.3.6.1.4.1.45724.1.1.4', $parsed['extensions'], true) && ! hash_equals(
            $authenticatorData->attestedCredentialData
                ?->aaguid
                ->toBinary() ?? '',
            $parsed['extensions']['1.3.6.1.4.1.45724.1.1.4']
        ) && throw AttestationStatementVerificationException::create(
            'The value of the "aaguid" does not match with the certificate'
        );
    }

    private function processWithECDAA(): never
    {
        throw UnsupportedFeatureException::create('ECDAA not supported');
    }
}
