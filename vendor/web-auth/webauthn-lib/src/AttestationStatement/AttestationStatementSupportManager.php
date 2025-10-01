<?php

declare(strict_types=1);

namespace Webauthn\AttestationStatement;

use Webauthn\Exception\InvalidDataException;
use function array_key_exists;

class AttestationStatementSupportManager
{
    /**
     * @param AttestationStatementSupport[] $attestationStatementSupports
     */
    public function __construct(
        private array $attestationStatementSupports = []
    ) {
        $this->add(new NoneAttestationStatementSupport());
        foreach ($attestationStatementSupports as $attestationStatementSupport) {
            $this->add($attestationStatementSupport);
        }
    }

    /**
     * @param AttestationStatementSupport[] $attestationStatementSupports
     */
    public static function create(array $attestationStatementSupports = []): self
    {
        return new self($attestationStatementSupports);
    }

    public function add(AttestationStatementSupport $attestationStatementSupport): void
    {
        $this->attestationStatementSupports[$attestationStatementSupport->name()] = $attestationStatementSupport;
    }

    public function has(string $name): bool
    {
        return array_key_exists($name, $this->attestationStatementSupports);
    }

    public function get(string $name): AttestationStatementSupport
    {
        $this->has($name) || throw InvalidDataException::create($name, sprintf(
            'The attestation statement format "%s" is not supported.',
            $name
        ));

        return $this->attestationStatementSupports[$name];
    }
}
