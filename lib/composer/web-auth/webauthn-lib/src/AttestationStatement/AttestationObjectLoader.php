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
use function ord;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Ramsey\Uuid\Uuid;
use function Safe\sprintf;
use function Safe\unpack;
use Throwable;
use Webauthn\AttestedCredentialData;
use Webauthn\AuthenticationExtensions\AuthenticationExtensionsClientOutputsLoader;
use Webauthn\AuthenticatorData;
use Webauthn\MetadataService\MetadataStatementRepository;
use Webauthn\StringStream;

class AttestationObjectLoader
{
    private const FLAG_AT = 0b01000000;
    private const FLAG_ED = 0b10000000;

    /**
     * @var Decoder
     */
    private $decoder;

    /**
     * @var AttestationStatementSupportManager
     */
    private $attestationStatementSupportManager;

    /**
     * @var LoggerInterface|null
     */
    private $logger;

    public function __construct(AttestationStatementSupportManager $attestationStatementSupportManager, ?MetadataStatementRepository $metadataStatementRepository = null, ?LoggerInterface $logger = null)
    {
        if (null !== $metadataStatementRepository) {
            @trigger_error('The argument "metadataStatementRepository" is deprecated since version 3.2 and will be removed in 4.0. Please set `null` instead.', E_USER_DEPRECATED);
        }
        if (null !== $logger) {
            @trigger_error('The argument "logger" is deprecated since version 3.3 and will be removed in 4.0. Please use the method "setLogger" instead.', E_USER_DEPRECATED);
        }
        $this->decoder = new Decoder(new TagObjectManager(), new OtherObjectManager());
        $this->attestationStatementSupportManager = $attestationStatementSupportManager;
        $this->logger = $logger ?? new NullLogger();
    }

    public static function create(AttestationStatementSupportManager $attestationStatementSupportManager): self
    {
        return new self($attestationStatementSupportManager);
    }

    public function load(string $data): AttestationObject
    {
        try {
            $this->logger->info('Trying to load the data', ['data' => $data]);
            $decodedData = Base64Url::decode($data);
            $stream = new StringStream($decodedData);
            $parsed = $this->decoder->decode($stream);

            $this->logger->info('Loading the Attestation Statement');
            $attestationObject = $parsed->getNormalizedData();
            Assertion::true($stream->isEOF(), 'Invalid attestation object. Presence of extra bytes.');
            $stream->close();
            Assertion::isArray($attestationObject, 'Invalid attestation object');
            Assertion::keyExists($attestationObject, 'authData', 'Invalid attestation object');
            Assertion::keyExists($attestationObject, 'fmt', 'Invalid attestation object');
            Assertion::keyExists($attestationObject, 'attStmt', 'Invalid attestation object');
            $authData = $attestationObject['authData'];

            $attestationStatementSupport = $this->attestationStatementSupportManager->get($attestationObject['fmt']);
            $attestationStatement = $attestationStatementSupport->load($attestationObject);
            $this->logger->info('Attestation Statement loaded');
            $this->logger->debug('Attestation Statement loaded', ['attestationStatement' => $attestationStatement]);

            $authDataStream = new StringStream($authData);
            $rp_id_hash = $authDataStream->read(32);
            $flags = $authDataStream->read(1);
            $signCount = $authDataStream->read(4);
            $signCount = unpack('N', $signCount)[1];
            $this->logger->debug(sprintf('Signature counter: %d', $signCount));

            $attestedCredentialData = null;
            if (0 !== (ord($flags) & self::FLAG_AT)) {
                $this->logger->info('Attested Credential Data is present');
                $aaguid = Uuid::fromBytes($authDataStream->read(16));
                $credentialLength = $authDataStream->read(2);
                $credentialLength = unpack('n', $credentialLength)[1];
                $credentialId = $authDataStream->read($credentialLength);
                $credentialPublicKey = $this->decoder->decode($authDataStream);
                Assertion::isInstanceOf($credentialPublicKey, MapObject::class, 'The data does not contain a valid credential public key.');
                $attestedCredentialData = new AttestedCredentialData($aaguid, $credentialId, (string) $credentialPublicKey);
                $this->logger->info('Attested Credential Data loaded');
                $this->logger->debug('Attested Credential Data loaded', ['at' => $attestedCredentialData]);
            }

            $extension = null;
            if (0 !== (ord($flags) & self::FLAG_ED)) {
                $this->logger->info('Extension Data loaded');
                $extension = $this->decoder->decode($authDataStream);
                $extension = AuthenticationExtensionsClientOutputsLoader::load($extension);
                $this->logger->info('Extension Data loaded');
                $this->logger->debug('Extension Data loaded', ['ed' => $extension]);
            }
            Assertion::true($authDataStream->isEOF(), 'Invalid authentication data. Presence of extra bytes.');
            $authDataStream->close();

            $authenticatorData = new AuthenticatorData($authData, $rp_id_hash, $flags, $signCount, $attestedCredentialData, $extension);
            $attestationObject = new AttestationObject($data, $attestationStatement, $authenticatorData);
            $this->logger->info('Attestation Object loaded');
            $this->logger->debug('Attestation Object', ['ed' => $attestationObject]);

            return $attestationObject;
        } catch (Throwable $throwable) {
            $this->logger->error('An error occurred', [
                'exception' => $throwable,
            ]);
            throw $throwable;
        }
    }

    public function setLogger(LoggerInterface $logger): self
    {
        $this->logger = $logger;

        return $this;
    }
}
