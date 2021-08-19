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
use function count;
use Webauthn\AuthenticatorData;
use Webauthn\TrustPath\EmptyTrustPath;

final class NoneAttestationStatementSupport implements AttestationStatementSupport
{
    public function name(): string
    {
        return 'none';
    }

    /**
     * @param mixed[] $attestation
     */
    public function load(array $attestation): AttestationStatement
    {
        Assertion::noContent($attestation['attStmt'], 'Invalid attestation object');

        return AttestationStatement::createNone($attestation['fmt'], $attestation['attStmt'], new EmptyTrustPath());
    }

    public function isValid(string $clientDataJSONHash, AttestationStatement $attestationStatement, AuthenticatorData $authenticatorData): bool
    {
        return 0 === count($attestationStatement->getAttStmt());
    }
}
