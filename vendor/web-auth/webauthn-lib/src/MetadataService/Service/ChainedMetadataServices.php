<?php

declare(strict_types=1);

namespace Webauthn\MetadataService\Service;

use Webauthn\Exception\MissingMetadataStatementException;
use Webauthn\MetadataService\Statement\MetadataStatement;

final class ChainedMetadataServices implements MetadataService
{
    /**
     * @var MetadataService[]
     */
    private array $services = [];

    public function __construct(MetadataService ...$services)
    {
        foreach ($services as $service) {
            $this->addServices($service);
        }
    }

    public static function create(MetadataService ...$services): self
    {
        return new self(...$services);
    }

    public function addServices(MetadataService ...$services): self
    {
        foreach ($services as $service) {
            $this->services[] = $service;
        }

        return $this;
    }

    public function list(): iterable
    {
        foreach ($this->services as $service) {
            yield from $service->list();
        }
    }

    public function has(string $aaguid): bool
    {
        foreach ($this->services as $service) {
            if ($service->has($aaguid)) {
                return true;
            }
        }

        return false;
    }

    public function get(string $aaguid): MetadataStatement
    {
        foreach ($this->services as $service) {
            if ($service->has($aaguid)) {
                return $service->get($aaguid);
            }
        }

        throw MissingMetadataStatementException::create($aaguid);
    }
}
