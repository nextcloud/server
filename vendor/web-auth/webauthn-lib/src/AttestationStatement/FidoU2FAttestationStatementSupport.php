<?php

declare(strict_types=1);

namespace Webauthn\AttestationStatement;

use CBOR\Decoder;
use CBOR\MapObject;
use Cose\Key\Ec2Key;
use Psr\EventDispatcher\EventDispatcherInterface;
use Throwable;
use Webauthn\AuthenticatorData;
use Webauthn\Event\AttestationStatementLoaded;
use Webauthn\Event\CanDispatchEvents;
use Webauthn\Event\NullEventDispatcher;
use Webauthn\Exception\AttestationStatementLoadingException;
use Webauthn\Exception\AttestationStatementVerificationException;
use Webauthn\Exception\InvalidAttestationStatementException;
use Webauthn\MetadataService\CertificateChain\CertificateToolbox;
use Webauthn\StringStream;
use Webauthn\TrustPath\CertificateTrustPath;
use function array_key_exists;
use function count;
use function is_array;
use function openssl_pkey_get_public;
use function openssl_verify;
use const OPENSSL_ALGO_SHA256;

final class FidoU2FAttestationStatementSupport implements AttestationStatementSupport, CanDispatchEvents
{
    private readonly Decoder $decoder;

    private EventDispatcherInterface $dispatcher;

    public function __construct()
    {
        $this->decoder = Decoder::create();
        $this->dispatcher = new NullEventDispatcher();
    }

    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher): void
    {
        $this->dispatcher = $eventDispatcher;
    }

    public static function create(): self
    {
        return new self();
    }

    public function name(): string
    {
        return 'fido-u2f';
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
        foreach (['sig', 'x5c'] as $key) {
            array_key_exists($key, $attestation['attStmt']) || throw AttestationStatementLoadingException::create(
                $attestation,
                sprintf('The attestation statement value "%s" is missing.', $key)
            );
        }
        $certificates = $attestation['attStmt']['x5c'];
        is_array($certificates) || throw AttestationStatementLoadingException::create(
            $attestation,
            'The attestation statement value "x5c" must be a list with one certificate.'
        );
        count($certificates) === 1 || throw AttestationStatementLoadingException::create(
            $attestation,
            'The attestation statement value "x5c" must be a list with one certificate.'
        );

        reset($certificates);
        $certificates = CertificateToolbox::convertAllDERToPEM($certificates);
        $this->checkCertificate($certificates[0]);

        $attestationStatement = AttestationStatement::createBasic(
            $attestation['fmt'],
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
        $authenticatorData->attestedCredentialData
            ?->aaguid
            ->__toString() === '00000000-0000-0000-0000-000000000000' || throw InvalidAttestationStatementException::create(
                $attestationStatement,
                'Invalid AAGUID for fido-u2f attestation statement. Shall be "00000000-0000-0000-0000-000000000000"'
            );
        $trustPath = $attestationStatement->trustPath;
        $trustPath instanceof CertificateTrustPath || throw InvalidAttestationStatementException::create(
            $attestationStatement,
            'Invalid trust path'
        );
        $dataToVerify = "\0";
        $dataToVerify .= $authenticatorData->rpIdHash;
        $dataToVerify .= $clientDataJSONHash;
        $dataToVerify .= $authenticatorData->attestedCredentialData
            ->credentialId;
        $dataToVerify .= $this->extractPublicKey($authenticatorData->attestedCredentialData ->credentialPublicKey);

        return openssl_verify(
            $dataToVerify,
            $attestationStatement->get('sig'),
            $trustPath->certificates[0],
            OPENSSL_ALGO_SHA256
        ) === 1;
    }

    private function extractPublicKey(?string $publicKey): string
    {
        $publicKey !== null || throw AttestationStatementVerificationException::create(
            'The attested credential data does not contain a valid public key.'
        );

        $publicKeyStream = new StringStream($publicKey);
        $coseKey = $this->decoder->decode($publicKeyStream);
        $publicKeyStream->isEOF() || throw AttestationStatementVerificationException::create(
            'Invalid public key. Presence of extra bytes.'
        );
        $publicKeyStream->close();
        $coseKey instanceof MapObject || throw AttestationStatementVerificationException::create(
            'The attested credential data does not contain a valid public key.'
        );

        $coseKey = $coseKey->normalize();
        $ec2Key = new Ec2Key($coseKey + [
            Ec2Key::TYPE => 2,
            Ec2Key::DATA_CURVE => Ec2Key::CURVE_P256,
        ]);

        return "\x04" . $ec2Key->x() . $ec2Key->y();
    }

    private function checkCertificate(string $publicKey): void
    {
        try {
            $resource = openssl_pkey_get_public($publicKey);
            $details = openssl_pkey_get_details($resource);
        } catch (Throwable $throwable) {
            throw AttestationStatementVerificationException::create(
                'Invalid certificate or certificate chain',
                $throwable
            );
        }
        is_array($details) || throw AttestationStatementVerificationException::create(
            'Invalid certificate or certificate chain'
        );
        array_key_exists('ec', $details) || throw AttestationStatementVerificationException::create(
            'Invalid certificate or certificate chain'
        );
        array_key_exists('curve_name', $details['ec']) || throw AttestationStatementVerificationException::create(
            'Invalid certificate or certificate chain'
        );
        $details['ec']['curve_name'] === 'prime256v1' || throw AttestationStatementVerificationException::create(
            'Invalid certificate or certificate chain'
        );
        array_key_exists('curve_oid', $details['ec']) || throw AttestationStatementVerificationException::create(
            'Invalid certificate or certificate chain'
        );
        $details['ec']['curve_oid'] === '1.2.840.10045.3.1.7' || throw AttestationStatementVerificationException::create(
            'Invalid certificate or certificate chain'
        );
    }
}
