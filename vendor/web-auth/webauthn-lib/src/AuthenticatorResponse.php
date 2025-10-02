<?php

declare(strict_types=1);

namespace Webauthn;

/**
 * @see https://www.w3.org/TR/webauthn/#authenticatorresponse
 */
abstract class AuthenticatorResponse
{
    public function __construct(
        public readonly CollectedClientData $clientDataJSON
    ) {
    }

    /**
     * @deprecated since 4.7.0. Please use the property directly.
     * @infection-ignore-all
     */
    public function getClientDataJSON(): CollectedClientData
    {
        return $this->clientDataJSON;
    }
}
