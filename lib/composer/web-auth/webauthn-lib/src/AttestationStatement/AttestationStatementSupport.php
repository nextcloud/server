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

interface AttestationStatementSupport
{
    public function name(): string;

    /**
     * @param mixed[] $attestation
     */
    public function load(array $attestation): AttestationStatement;

    public function isValid(string $clientDataJSONHash, AttestationStatement $attestationStatement, AuthenticatorData $authenticatorData): bool;
}
