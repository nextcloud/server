<?php

declare(strict_types=1);

namespace Webauthn\MetadataService\Service;

use ParagonIE\ConstantTime\Base64;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Webauthn\AttestationStatement\AttestationStatementSupportManager;
use Webauthn\Denormalizer\WebauthnSerializerFactory;
use Webauthn\Event\CanDispatchEvents;
use Webauthn\Event\MetadataStatementFound;
use Webauthn\Event\NullEventDispatcher;
use Webauthn\Exception\MetadataStatementLoadingException;
use Webauthn\Exception\MissingMetadataStatementException;
use Webauthn\MetadataService\Statement\MetadataStatement;
use function file_get_contents;

final class LocalResourceMetadataService implements MetadataService, CanDispatchEvents
{
    private ?MetadataStatement $statement = null;

    private EventDispatcherInterface $dispatcher;

    private readonly ?SerializerInterface $serializer;

    public function __construct(
        private readonly string $filename,
        private readonly bool $isBase64Encoded = false,
        ?SerializerInterface $serializer = null,
    ) {
        $this->serializer = $serializer ?? (new WebauthnSerializerFactory(
            AttestationStatementSupportManager::create()
        ))->create();
        $this->dispatcher = new NullEventDispatcher();
    }

    public static function create(
        string $filename,
        bool $isBase64Encoded = false,
        ?SerializerInterface $serializer = null
    ): self {
        return new self($filename, $isBase64Encoded, $serializer);
    }

    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher): void
    {
        $this->dispatcher = $eventDispatcher;
    }

    public function list(): iterable
    {
        $this->loadData();
        $this->statement !== null || throw MetadataStatementLoadingException::create();
        $aaguid = $this->statement->aaguid;
        if ($aaguid === null) {
            yield from [];
        } else {
            yield from [$aaguid];
        }
    }

    public function has(string $aaguid): bool
    {
        $this->loadData();
        $this->statement !== null || throw MetadataStatementLoadingException::create();

        return $aaguid === $this->statement->aaguid;
    }

    public function get(string $aaguid): MetadataStatement
    {
        $this->loadData();
        $this->statement !== null || throw MetadataStatementLoadingException::create();

        if ($aaguid === $this->statement->aaguid) {
            $this->dispatcher->dispatch(MetadataStatementFound::create($this->statement));

            return $this->statement;
        }

        throw MissingMetadataStatementException::create($aaguid);
    }

    private function loadData(): void
    {
        if ($this->statement !== null) {
            return;
        }

        $content = file_get_contents($this->filename);
        if ($this->isBase64Encoded) {
            $content = Base64::decode($content, true);
        }
        if ($this->serializer !== null) {
            $this->statement = $this->serializer->deserialize($content, MetadataStatement::class, 'json');
        } else {
            $this->statement = MetadataStatement::createFromString($content);
        }
    }
}
