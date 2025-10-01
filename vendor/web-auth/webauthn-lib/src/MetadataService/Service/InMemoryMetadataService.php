<?php

declare(strict_types=1);

namespace Webauthn\MetadataService\Service;

use Psr\EventDispatcher\EventDispatcherInterface;
use Webauthn\Event\CanDispatchEvents;
use Webauthn\Event\MetadataStatementFound;
use Webauthn\Event\NullEventDispatcher;
use Webauthn\Exception\MissingMetadataStatementException;
use Webauthn\MetadataService\Statement\MetadataStatement;
use function array_key_exists;

final class InMemoryMetadataService implements MetadataService, CanDispatchEvents
{
    /**
     * @var MetadataStatement[]
     */
    private array $statements = [];

    private EventDispatcherInterface $dispatcher;

    public function __construct(MetadataStatement ...$statements)
    {
        foreach ($statements as $statement) {
            $this->addStatements($statement);
        }
        $this->dispatcher = new NullEventDispatcher();
    }

    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher): void
    {
        $this->dispatcher = $eventDispatcher;
    }

    public static function create(MetadataStatement ...$statements): self
    {
        return new self(...$statements);
    }

    public function addStatements(MetadataStatement ...$statements): self
    {
        foreach ($statements as $statement) {
            $aaguid = $statement->aaguid;
            if ($aaguid === null) {
                continue;
            }
            $this->statements[$aaguid] = $statement;
        }

        return $this;
    }

    public function list(): iterable
    {
        yield from array_keys($this->statements);
    }

    public function has(string $aaguid): bool
    {
        return array_key_exists($aaguid, $this->statements);
    }

    public function get(string $aaguid): MetadataStatement
    {
        array_key_exists($aaguid, $this->statements) || throw MissingMetadataStatementException::create($aaguid);
        $mds = $this->statements[$aaguid];
        $this->dispatcher->dispatch(MetadataStatementFound::create($mds));

        return $mds;
    }
}
