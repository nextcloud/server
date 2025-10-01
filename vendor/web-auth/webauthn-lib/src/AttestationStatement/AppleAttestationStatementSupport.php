<?php

declare(strict_types=1);

namespace Webauthn\AttestationStatement;

use CBOR\Decoder;
use CBOR\Normalizable;
use Cose\Key\Ec2Key;
use Cose\Key\Key;
use Cose\Key\RsaKey;
use Psr\EventDispatcher\EventDispatcherInterface;
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

final class AppleAttestationStatementSupport implements AttestationStatementSupport, CanDispatchEvents
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
        return 'apple';
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
        array_key_exists('x5c', $attestation['attStmt']) || throw AttestationStatementLoadingException::create(
            $attestation,
            'The attestation statement value "x5c" is missing.'
        );
        $certificates = $attestation['attStmt']['x5c'];
        (is_countable($certificates) ? count(
            $certificates
        ) : 0) > 0 || throw AttestationStatementLoadingException::create(
            $attestation,
            'The attestation statement value "x5c" must be a list with at least one certificate.'
        );
        $certificates = CertificateToolbox::convertAllDERToPEM($certificates);

        $attestationStatement = AttestationStatement::createAnonymizationCA(
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
        $trustPath = $attestationStatement->trustPath;
        $trustPath instanceof CertificateTrustPath || throw InvalidAttestationStatementException::create(
            $attestationStatement,
            'Invalid trust path'
        );

        $certificates = $trustPath->certificates;

        //Decode leaf attestation certificate
        $leaf = $certificates[0];

        $this->checkCertificateAndGetPublicKey($leaf, $clientDataJSONHash, $authenticatorData);

        return true;
    }

    private function checkCertificateAndGetPublicKey(
        string $certificate,
        string $clientDataHash,
        AuthenticatorData $authenticatorData
    ): void {
        $resource = openssl_pkey_get_public($certificate);
        $details = openssl_pkey_get_details($resource);
        is_array($details) || throw AttestationStatementVerificationException::create(
            'Unable to read the certificate'
        );

        //Check that authData publicKey matches the public key in the attestation certificate
        $attestedCredentialData = $authenticatorData->attestedCredentialData;
        $attestedCredentialData !== null || throw AttestationStatementVerificationException::create(
            'No attested credential data found'
        );
        $publicKeyData = $attestedCredentialData->credentialPublicKey;
        $publicKeyData !== null || throw AttestationStatementVerificationException::create(
            'No attested public key found'
        );
        $publicDataStream = new StringStream($publicKeyData);
        $coseKey = $this->decoder->decode($publicDataStream);
        $coseKey instanceof Normalizable || throw AttestationStatementVerificationException::create(
            'Invalid attested public key found'
        );
        $publicDataStream->isEOF() || throw AttestationStatementVerificationException::create(
            'Invalid public key data. Presence of extra bytes.'
        );
        $publicDataStream->close();
        $publicKey = Key::createFromData($coseKey->normalize());

        ($publicKey instanceof Ec2Key) || ($publicKey instanceof RsaKey) || throw AttestationStatementVerificationException::create(
            'Unsupported key type'
        );

        //We check the attested key corresponds to the key in the certificate
        $publicKey->asPEM() === $details['key'] || throw AttestationStatementVerificationException::create(
            'Invalid key'
        );

        /*---------------------------*/
        $certDetails = openssl_x509_parse($certificate);

        //Find Apple Extension with OID "1.2.840.113635.100.8.2" in certificate extensions
        is_array(
            $certDetails
        ) || throw AttestationStatementVerificationException::create('The certificate is not valid');
        array_key_exists('extensions', $certDetails) || throw AttestationStatementVerificationException::create(
            'The certificate has no extension'
        );
        is_array($certDetails['extensions']) || throw AttestationStatementVerificationException::create(
            'The certificate has no extension'
        );
        array_key_exists(
            '1.2.840.113635.100.8.2',
            $certDetails['extensions']
        ) || throw AttestationStatementVerificationException::create(
            'The certificate extension "1.2.840.113635.100.8.2" is missing'
        );
        $extension = $certDetails['extensions']['1.2.840.113635.100.8.2'];

        $nonceToHash = $authenticatorData->authData . $clientDataHash;
        $nonce = hash('sha256', $nonceToHash);

        //'3024a1220420' corresponds to the Sequence+Explicitly Tagged Object + Octet Object
        '3024a1220420' . $nonce === bin2hex(
            (string) $extension
        ) || throw AttestationStatementVerificationException::create('The client data hash is not valid');
    }
}
