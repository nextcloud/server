<?php

declare(strict_types=1);

namespace Webauthn\AttestationStatement;

use Jose\Component\Core\Algorithm as AlgorithmInterface;
use Jose\Component\Core\AlgorithmManager;
use Jose\Component\KeyManagement\JWKFactory;
use Jose\Component\Signature\Algorithm\EdDSA;
use Jose\Component\Signature\Algorithm\ES256;
use Jose\Component\Signature\Algorithm\ES384;
use Jose\Component\Signature\Algorithm\ES512;
use Jose\Component\Signature\Algorithm\PS256;
use Jose\Component\Signature\Algorithm\PS384;
use Jose\Component\Signature\Algorithm\PS512;
use Jose\Component\Signature\Algorithm\RS256;
use Jose\Component\Signature\Algorithm\RS384;
use Jose\Component\Signature\Algorithm\RS512;
use Jose\Component\Signature\JWS;
use Jose\Component\Signature\JWSVerifier;
use Jose\Component\Signature\Serializer\CompactSerializer;
use Psr\Clock\ClockInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Webauthn\AuthenticatorData;
use Webauthn\Event\AttestationStatementLoaded;
use Webauthn\Event\CanDispatchEvents;
use Webauthn\Event\NullEventDispatcher;
use Webauthn\Exception\AttestationStatementLoadingException;
use Webauthn\Exception\AttestationStatementVerificationException;
use Webauthn\Exception\InvalidAttestationStatementException;
use Webauthn\Exception\UnsupportedFeatureException;
use Webauthn\MetadataService\CertificateChain\CertificateToolbox;
use Webauthn\TrustPath\CertificateTrustPath;
use function array_key_exists;
use function count;
use function is_array;
use function is_int;
use function is_string;
use const JSON_THROW_ON_ERROR;

/**
 * @deprecated since 4.9.0 and will be removed in 5.0.0. Android SafetyNet is now deprecated.
 */
final class AndroidSafetyNetAttestationStatementSupport implements AttestationStatementSupport, CanDispatchEvents
{
    private ?string $apiKey = null;

    private null|ClientInterface|HttpClientInterface $client = null;

    private readonly CompactSerializer $jwsSerializer;

    private ?JWSVerifier $jwsVerifier = null;

    private ?RequestFactoryInterface $requestFactory = null;

    private int $leeway = 0;

    private int $maxAge = 60000;

    private EventDispatcherInterface $dispatcher;

    public function __construct(
        private readonly null|ClockInterface $clock = null
    ) {
        if ($this->clock === null) {
            trigger_deprecation(
                'web-auth/webauthn-lib',
                '4.8.0',
                'The parameter "$clock" will be required in 5.0.0. Please set a clock instance.'
            );
        }
        if (! class_exists(RS256::class) || ! class_exists(JWKFactory::class)) {
            throw UnsupportedFeatureException::create(
                'The algorithm RS256 is missing. Did you forget to install the package web-token/jwt-library?'
            );
        }
        $this->jwsSerializer = new CompactSerializer();
        $this->initJwsVerifier();
        $this->dispatcher = new NullEventDispatcher();
    }

    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher): void
    {
        $this->dispatcher = $eventDispatcher;
    }

    public static function create(null|ClockInterface $clock = null): self
    {
        return new self($clock);
    }

    public function enableApiVerification(
        ClientInterface|HttpClientInterface $client,
        string $apiKey,
        ?RequestFactoryInterface $requestFactory = null
    ): self {
        $this->apiKey = $apiKey;
        $this->client = $client;
        $this->requestFactory = $requestFactory;
        if ($requestFactory !== null && ! $client instanceof HttpClientInterface) {
            trigger_deprecation(
                'web-auth/metadata-service',
                '4.7.0',
                'The parameter "$requestFactory" will be removed in 5.0.0. Please set it to null and set an Symfony\Contracts\HttpClient\HttpClientInterface as "$client" argument.'
            );
        }

        return $this;
    }

    public function setMaxAge(int $maxAge): self
    {
        $this->maxAge = $maxAge;

        return $this;
    }

    public function setLeeway(int $leeway): self
    {
        $this->leeway = $leeway;

        return $this;
    }

    public function name(): string
    {
        return 'android-safetynet';
    }

    /**
     * @param array<string, mixed> $attestation
     */
    public function load(array $attestation): AttestationStatement
    {
        array_key_exists('attStmt', $attestation) || throw AttestationStatementLoadingException::create(
            $attestation
        );
        foreach (['ver', 'response'] as $key) {
            array_key_exists($key, $attestation['attStmt']) || throw AttestationStatementLoadingException::create(
                $attestation,
                sprintf('The attestation statement value "%s" is missing.', $key)
            );
            $attestation['attStmt'][$key] !== '' || throw AttestationStatementLoadingException::create(
                $attestation,
                sprintf('The attestation statement value "%s" is empty.', $key)
            );
        }
        $jws = $this->jwsSerializer->unserialize($attestation['attStmt']['response']);
        $jwsHeader = $jws->getSignature(0)
            ->getProtectedHeader();
        array_key_exists('x5c', $jwsHeader) || throw AttestationStatementLoadingException::create(
            $attestation,
            'The response in the attestation statement must contain a "x5c" header.'
        );
        (is_countable($jwsHeader['x5c']) ? count(
            $jwsHeader['x5c']
        ) : 0) > 0 || throw AttestationStatementLoadingException::create(
            $attestation,
            'The "x5c" parameter in the attestation statement response must contain at least one certificate.'
        );
        $certificates = $this->convertCertificatesToPem($jwsHeader['x5c']);
        $attestation['attStmt']['jws'] = $jws;

        $attestationStatement = AttestationStatement::createBasic(
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
        $trustPath = $attestationStatement->trustPath;
        $trustPath instanceof CertificateTrustPath || throw InvalidAttestationStatementException::create(
            $attestationStatement,
            'Invalid trust path'
        );
        $certificates = $trustPath->certificates;
        $firstCertificate = current($certificates);
        is_string($firstCertificate) || throw InvalidAttestationStatementException::create(
            $attestationStatement,
            'No certificate'
        );

        $parsedCertificate = openssl_x509_parse($firstCertificate);
        is_array($parsedCertificate) || throw InvalidAttestationStatementException::create(
            $attestationStatement,
            'Invalid attestation object'
        );
        array_key_exists('subject', $parsedCertificate) || throw InvalidAttestationStatementException::create(
            $attestationStatement,
            'Invalid attestation object'
        );
        array_key_exists('CN', $parsedCertificate['subject']) || throw InvalidAttestationStatementException::create(
            $attestationStatement,
            'Invalid attestation object'
        );
        $parsedCertificate['subject']['CN'] === 'attest.android.com' || throw InvalidAttestationStatementException::create(
            $attestationStatement,
            'Invalid attestation object'
        );

        /** @var JWS $jws */
        $jws = $attestationStatement->get('jws');
        $payload = $jws->getPayload();
        $this->validatePayload($payload, $clientDataJSONHash, $authenticatorData);

        //Check the signature
        $this->validateSignature($jws, $trustPath);

        //Check against Google service
        $this->validateUsingGoogleApi($attestationStatement);

        return true;
    }

    private function validatePayload(
        ?string $payload,
        string $clientDataJSONHash,
        AuthenticatorData $authenticatorData
    ): void {
        $payload !== null || throw AttestationStatementVerificationException::create('Invalid attestation object');
        $payload = json_decode($payload, true, flags: JSON_THROW_ON_ERROR);
        array_key_exists('nonce', $payload) || throw AttestationStatementVerificationException::create(
            'Invalid attestation object. "nonce" is missing.'
        );
        $payload['nonce'] === base64_encode(
            hash('sha256', $authenticatorData->authData . $clientDataJSONHash, true)
        ) || throw AttestationStatementVerificationException::create('Invalid attestation object. Invalid nonce');
        array_key_exists('ctsProfileMatch', $payload) || throw AttestationStatementVerificationException::create(
            'Invalid attestation object. "ctsProfileMatch" is missing.'
        );
        $payload['ctsProfileMatch'] || throw AttestationStatementVerificationException::create(
            'Invalid attestation object. "ctsProfileMatch" value is false.'
        );
        array_key_exists('timestampMs', $payload) || throw AttestationStatementVerificationException::create(
            'Invalid attestation object. Timestamp is missing.'
        );
        is_int($payload['timestampMs']) || throw AttestationStatementVerificationException::create(
            'Invalid attestation object. Timestamp shall be an integer.'
        );

        $currentTime = ($this->clock?->now()->getTimestamp() ?? time()) * 1000;
        $payload['timestampMs'] <= $currentTime + $this->leeway || throw AttestationStatementVerificationException::create(
            sprintf(
                'Invalid attestation object. Issued in the future. Current time: %d. Response time: %d',
                $currentTime,
                $payload['timestampMs']
            )
        );
        $currentTime - $payload['timestampMs'] <= $this->maxAge || throw AttestationStatementVerificationException::create(
            sprintf(
                'Invalid attestation object. Too old. Current time: %d. Response time: %d',
                $currentTime,
                $payload['timestampMs']
            )
        );
    }

    private function validateSignature(JWS $jws, CertificateTrustPath $trustPath): void
    {
        $jwk = JWKFactory::createFromCertificate($trustPath->certificates[0]);
        $isValid = $this->jwsVerifier?->verifyWithKey($jws, $jwk, 0);
        $isValid === true || throw AttestationStatementVerificationException::create('Invalid response signature');
    }

    private function validateUsingGoogleApi(AttestationStatement $attestationStatement): void
    {
        if ($this->client === null || $this->apiKey === null) {
            return;
        }
        $uri = sprintf(
            'https://www.googleapis.com/androidcheck/v1/attestations/verify?key=%s',
            urlencode($this->apiKey)
        );
        $requestBody = sprintf('{"signedAttestation":"%s"}', $attestationStatement->get('response'));
        if ($this->client instanceof HttpClientInterface) {
            $responseBody = $this->validateUsingGoogleApiWithSymfonyClient($requestBody, $uri);
        } else {
            $responseBody = $this->validateUsingGoogleApiWithPsrClient($requestBody, $uri);
        }
        $responseBodyJson = json_decode($responseBody, true, flags: JSON_THROW_ON_ERROR);
        array_key_exists(
            'isValidSignature',
            $responseBodyJson
        ) || throw AttestationStatementVerificationException::create('Invalid response.');
        $responseBodyJson['isValidSignature'] === true || throw AttestationStatementVerificationException::create(
            'Invalid response.'
        );
    }

    private function getResponseBody(ResponseInterface $response): string
    {
        $responseBody = '';
        $response->getBody()
            ->rewind();
        do {
            $tmp = $response->getBody()
                ->read(1024);
            if ($tmp === '') {
                break;
            }
            $responseBody .= $tmp;
        } while (true);

        return $responseBody;
    }

    /**
     * @param string[] $certificates
     *
     * @return string[]
     */
    private function convertCertificatesToPem(array $certificates): array
    {
        foreach ($certificates as $k => $v) {
            $certificates[$k] = CertificateToolbox::fixPEMStructure($v);
        }

        return $certificates;
    }

    private function initJwsVerifier(): void
    {
        $algorithmClasses = [
            RS256::class, RS384::class, RS512::class,
            PS256::class, PS384::class, PS512::class,
            ES256::class, ES384::class, ES512::class,
            EdDSA::class,
        ];
        /** @var AlgorithmInterface[] $algorithms */
        $algorithms = [];
        foreach ($algorithmClasses as $algorithm) {
            if (class_exists($algorithm)) {
                $algorithms[] = new $algorithm();
            }
        }
        $algorithmManager = new AlgorithmManager($algorithms);
        $this->jwsVerifier = new JWSVerifier($algorithmManager);
    }

    private function validateUsingGoogleApiWithSymfonyClient(string $requestBody, string $uri): string
    {
        $response = $this->client->request('POST', $uri, [
            'headers' => [
                'content-type' => 'application/json',
            ],
            'body' => $requestBody,
        ]);
        $response->getStatusCode() === 200 || throw AttestationStatementVerificationException::create(
            'Request did not succeeded'
        );

        return $response->getContent();
    }

    private function validateUsingGoogleApiWithPsrClient(string $requestBody, string $uri): string
    {
        $request = $this->requestFactory->createRequest('POST', $uri);
        $request = $request->withHeader('content-type', 'application/json');
        $request->getBody()
            ->write($requestBody);

        $response = $this->client->sendRequest($request);
        $response->getStatusCode() === 200 || throw AttestationStatementVerificationException::create(
            'Request did not succeeded'
        );

        return $this->getResponseBody($response);
    }
}
