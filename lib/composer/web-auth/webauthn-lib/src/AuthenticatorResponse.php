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

/**
 * @see https://www.w3.org/TR/webauthn/#authenticatorresponse
 */
abstract class AuthenticatorResponse
{
    /**
     * @var CollectedClientData
     */
    private $clientDataJSON;

    public function __construct(CollectedClientData $clientDataJSON)
    {
        $this->clientDataJSON = $clientDataJSON;
    }

    public function getClientDataJSON(): CollectedClientData
    {
        return $this->clientDataJSON;
    }
}
