<?php

declare(strict_types=1);

namespace Webauthn\AttestationStatement;

use CBOR\Decoder;
use CBOR\Normalizable;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Throwable;
use Webauthn\AuthenticatorDataLoader;
use Webauthn\Event\AttestationObjectLoaded;
use Webauthn\Event\CanDispatchEvents;
use Webauthn\Event\NullEventDispatcher;
use Webauthn\Exception\InvalidDataException;
use Webauthn\MetadataService\CanLogData;
use Webauthn\StringStream;
use Webauthn\Util\Base64;
use function array_key_exists;
use function is_array;

class AttestationObjectLoader implements CanDispatchEvents, CanLogData
{
    private LoggerInterface $logger;

    private EventDispatcherInterface $dispatcher;

    public function __construct(
        private readonly AttestationStatementSupportManager $attestationStatementSupportManager
    ) {
        $this->logger = new NullLogger();
        $this->dispatcher = new NullEventDispatcher();
    }

    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher): void
    {
        $this->dispatcher = $eventDispatcher;
    }

    public static function create(AttestationStatementSupportManager $attestationStatementSupportManager): self
    {
        return new self($attestationStatementSupportManager);
    }

    public function load(string $data): AttestationObject
    {
        try {
            $this->logger->info('Trying to load the data', [
                'data' => $data,
            ]);
            $decodedData = Base64::decode($data);
            $stream = new StringStream($decodedData);
            $parsed = Decoder::create()->decode($stream);

            $this->logger->info('Loading the Attestation Statement');
            $parsed instanceof Normalizable || throw InvalidDataException::create(
                $parsed,
                'Invalid attestation object. Unexpected object.'
            );
            $attestationObject = $parsed->normalize();
            $stream->isEOF() || throw InvalidDataException::create(
                null,
                'Invalid attestation object. Presence of extra bytes.'
            );
            $stream->close();
            is_array($attestationObject) || throw InvalidDataException::create(
                $attestationObject,
                'Invalid attestation object'
            );
            array_key_exists('authData', $attestationObject) || throw InvalidDataException::create(
                $attestationObject,
                'Invalid attestation object'
            );
            array_key_exists('fmt', $attestationObject) || throw InvalidDataException::create(
                $attestationObject,
                'Invalid attestation object'
            );
            array_key_exists('attStmt', $attestationObject) || throw InvalidDataException::create(
                $attestationObject,
                'Invalid attestation object'
            );

            $attestationStatementSupport = $this->attestationStatementSupportManager->get($attestationObject['fmt']);
            $attestationStatement = $attestationStatementSupport->load($attestationObject);
            $this->logger->info('Attestation Statement loaded');
            $this->logger->debug('Attestation Statement loaded', [
                'attestationStatement' => $attestationStatement,
            ]);
            $authData = $attestationObject['authData'];
            $authDataLoader = AuthenticatorDataLoader::create();
            $authenticatorData = $authDataLoader->load($authData);

            $attestationObject = AttestationObject::create($data, $attestationStatement, $authenticatorData);
            $this->logger->info('Attestation Object loaded');
            $this->logger->debug('Attestation Object', [
                'ed' => $attestationObject,
            ]);
            $this->dispatcher->dispatch(AttestationObjectLoaded::create($attestationObject));

            return $attestationObject;
        } catch (Throwable $throwable) {
            $this->logger->error('An error occurred', [
                'exception' => $throwable,
            ]);
            throw $throwable;
        }
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }
}
