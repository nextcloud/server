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

use Webauthn\AuthenticatorData;
use Webauthn\MetadataService\MetadataStatement;

class AttestationObject
{
    /**
     * @var string
     */
    private $rawAttestationObject;
    /**
     * @var AttestationStatement
     */
    private $attStmt;
    /**
     * @var AuthenticatorData
     */
    private $authData;

    /**
     * @var MetadataStatement|null
     */
    private $metadataStatement;

    public function __construct(string $rawAttestationObject, AttestationStatement $attStmt, AuthenticatorData $authData, ?MetadataStatement $metadataStatement = null)
    {
        if (null !== $metadataStatement) {
            @trigger_error('The argument "metadataStatement" is deprecated since version 3.3 and will be removed in 4.0. Please use the method "setMetadataStatement".', E_USER_DEPRECATED);
        }
        $this->rawAttestationObject = $rawAttestationObject;
        $this->attStmt = $attStmt;
        $this->authData = $authData;
        $this->metadataStatement = $metadataStatement;
    }

    public function getRawAttestationObject(): string
    {
        return $this->rawAttestationObject;
    }

    public function getAttStmt(): AttestationStatement
    {
        return $this->attStmt;
    }

    public function setAttStmt(AttestationStatement $attStmt): void
    {
        $this->attStmt = $attStmt;
    }

    public function getAuthData(): AuthenticatorData
    {
        return $this->authData;
    }

    public function getMetadataStatement(): ?MetadataStatement
    {
        return $this->metadataStatement;
    }

    public function setMetadataStatement(MetadataStatement $metadataStatement): self
    {
        $this->metadataStatement = $metadataStatement;

        return $this;
    }
}
