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

namespace Webauthn;

use Webauthn\AttestationStatement\AttestationObject;

/**
 * @see https://www.w3.org/TR/webauthn/#authenticatorattestationresponse
 */
class AuthenticatorAttestationResponse extends AuthenticatorResponse
{
    /**
     * @var AttestationObject
     */
    private $attestationObject;

    public function __construct(CollectedClientData $clientDataJSON, AttestationObject $attestationObject)
    {
        parent::__construct($clientDataJSON);
        $this->attestationObject = $attestationObject;
    }

    public function getAttestationObject(): AttestationObject
    {
        return $this->attestationObject;
    }
}
