<?php

declare(strict_types=1);

namespace Webauthn\AttestationStatement;

use Webauthn\AuthenticatorData;
use Webauthn\MetadataService\Statement\MetadataStatement;

class AttestationObject
{
    public ?MetadataStatement $metadataStatement = null;

    public function __construct(
        public readonly string $rawAttestationObject,
        public AttestationStatement $attStmt,
        public readonly AuthenticatorData $authData
    ) {
    }

    public static function create(
        string $rawAttestationObject,
        AttestationStatement $attStmt,
        AuthenticatorData $authData
    ): self {
        return new self($rawAttestationObject, $attStmt, $authData);
    }

    /**
     * @deprecated since 4.7.0. Please use the property directly.
     * @infection-ignore-all
     */
    public function getRawAttestationObject(): string
    {
        return $this->rawAttestationObject;
    }

    /**
     * @deprecated since 4.7.0. Please use the property directly.
     * @infection-ignore-all
     */
    public function getAttStmt(): AttestationStatement
    {
        return $this->attStmt;
    }

    /**
     * @deprecated since 4.7.0. Please use the property directly.
     * @infection-ignore-all
     */
    public function setAttStmt(AttestationStatement $attStmt): void
    {
        $this->attStmt = $attStmt;
    }

    /**
     * @deprecated since 4.7.0. Please use the property directly.
     * @infection-ignore-all
     */
    public function getAuthData(): AuthenticatorData
    {
        return $this->authData;
    }

    /**
     * @deprecated since 4.7.0. Please use the property directly.
     * @infection-ignore-all
     */
    public function getMetadataStatement(): ?MetadataStatement
    {
        return $this->metadataStatement;
    }

    /**
     * @deprecated since 4.7.0. Please use the property directly.
     * @infection-ignore-all
     */
    public function setMetadataStatement(MetadataStatement $metadataStatement): self
    {
        $this->metadataStatement = $metadataStatement;

        return $this;
    }
}
