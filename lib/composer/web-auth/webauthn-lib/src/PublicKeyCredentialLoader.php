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

namespace Webauthn;

use function array_key_exists;
use Assert\Assertion;
use Base64Url\Base64Url;
use CBOR\Decoder;
use CBOR\MapObject;
use CBOR\OtherObject\OtherObjectManager;
use CBOR\Tag\TagObjectManager;
use InvalidArgumentException;
use function ord;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Ramsey\Uuid\Uuid;
use function Safe\json_decode;
use function Safe\sprintf;
use function Safe\unpack;
use Throwable;
use Webauthn\AttestationStatement\AttestationObjectLoader;
use Webauthn\AuthenticationExtensions\AuthenticationExtensionsClientOutputsLoader;

class PublicKeyCredentialLoader
{
    private const FLAG_AT = 0b01000000;
    private const FLAG_ED = 0b10000000;

    /**
     * @var AttestationObjectLoader
     */
    private $attestationObjectLoader;

    /**
     * @var Decoder
     */
    private $decoder;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(AttestationObjectLoader $attestationObjectLoader, ?LoggerInterface $logger = null)
    {
        if (null !== $logger) {
            @trigger_error('The argument "logger" is deprecated since version 3.3 and will be removed in 4.0. Please use the method "setLogger".', E_USER_DEPRECATED);
        }
        $this->decoder = new Decoder(new TagObjectManager(), new OtherObjectManager());
        $this->attestationObjectLoader = $attestationObjectLoader;
        $this->logger = $logger ?? new NullLogger();
    }

    public static function create(AttestationObjectLoader $attestationObjectLoader): self
    {
        return new self($attestationObjectLoader);
    }

    public function setLogger(LoggerInterface $logger): self
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * @param mixed[] $json
     */
    public function loadArray(array $json): PublicKeyCredential
    {
        $this->logger->info('Trying to load data from an array', ['data' => $json]);
        try {
            foreach (['id', 'rawId', 'type'] as $key) {
                Assertion::keyExists($json, $key, sprintf('The parameter "%s" is missing', $key));
                Assertion::string($json[$key], sprintf('The parameter "%s" shall be a string', $key));
            }
            Assertion::keyExists($json, 'response', 'The parameter "response" is missing');
            Assertion::isArray($json['response'], 'The parameter "response" shall be an array');
            Assertion::eq($json['type'], 'public-key', sprintf('Unsupported type "%s"', $json['type']));

            $id = Base64Url::decode($json['id']);
            $rawId = Base64Url::decode($json['rawId']);
            Assertion::true(hash_equals($id, $rawId));

            $publicKeyCredential = new PublicKeyCredential(
                $json['id'],
                $json['type'],
                $rawId,
                $this->createResponse($json['response'])
            );
            $this->logger->info('The data has been loaded');
            $this->logger->debug('Public Key Credential', ['publicKeyCredential' => $publicKeyCredential]);

            return $publicKeyCredential;
        } catch (Throwable $throwable) {
            $this->logger->error('An error occurred', [
                'exception' => $throwable,
            ]);
            throw $throwable;
        }
    }

    public function load(string $data): PublicKeyCredential
    {
        $this->logger->info('Trying to load data from a string', ['data' => $data]);
        try {
            $json = json_decode($data, true);

            return $this->loadArray($json);
        } catch (Throwable $throwable) {
            $this->logger->error('An error occurred', [
                'exception' => $throwable,
            ]);
            throw $throwable;
        }
    }

    /**
     * @param mixed[] $response
     */
    private function createResponse(array $response): AuthenticatorResponse
    {
        Assertion::keyExists($response, 'clientDataJSON', 'Invalid data. The parameter "clientDataJSON" is missing');
        Assertion::string($response['clientDataJSON'], 'Invalid data. The parameter "clientDataJSON" is invalid');
        switch (true) {
            case array_key_exists('attestationObject', $response):
                Assertion::string($response['attestationObject'], 'Invalid data. The parameter "attestationObject   " is invalid');
                $attestationObject = $this->attestationObjectLoader->load($response['attestationObject']);

                return new AuthenticatorAttestationResponse(CollectedClientData::createFormJson($response['clientDataJSON']), $attestationObject);
            case array_key_exists('authenticatorData', $response) && array_key_exists('signature', $response):
                $authData = Base64Url::decode($response['authenticatorData']);

                $authDataStream = new StringStream($authData);
                $rp_id_hash = $authDataStream->read(32);
                $flags = $authDataStream->read(1);
                $signCount = $authDataStream->read(4);
                $signCount = unpack('N', $signCount)[1];

                $attestedCredentialData = null;
                if (0 !== (ord($flags) & self::FLAG_AT)) {
                    $aaguid = Uuid::fromBytes($authDataStream->read(16));
                    $credentialLength = $authDataStream->read(2);
                    $credentialLength = unpack('n', $credentialLength)[1];
                    $credentialId = $authDataStream->read($credentialLength);
                    $credentialPublicKey = $this->decoder->decode($authDataStream);
                    Assertion::isInstanceOf($credentialPublicKey, MapObject::class, 'The data does not contain a valid credential public key.');
                    $attestedCredentialData = new AttestedCredentialData($aaguid, $credentialId, (string) $credentialPublicKey);
                }

                $extension = null;
                if (0 !== (ord($flags) & self::FLAG_ED)) {
                    $extension = $this->decoder->decode($authDataStream);
                    $extension = AuthenticationExtensionsClientOutputsLoader::load($extension);
                }
                Assertion::true($authDataStream->isEOF(), 'Invalid authentication data. Presence of extra bytes.');
                $authDataStream->close();
                $authenticatorData = new AuthenticatorData($authData, $rp_id_hash, $flags, $signCount, $attestedCredentialData, $extension);

                return new AuthenticatorAssertionResponse(
                    CollectedClientData::createFormJson($response['clientDataJSON']),
                    $authenticatorData,
                    Base64Url::decode($response['signature']),
                    $response['userHandle'] ?? null
                );
            default:
                throw new InvalidArgumentException('Unable to create the response object');
        }
    }
}
