<?php

declare(strict_types=1);

namespace Webauthn;

use Webauthn\AttestationStatement\AttestationObject;

/**
 * @see https://www.w3.org/TR/webauthn/#authenticatorattestationresponse
 */
class AuthenticatorAttestationResponse extends AuthenticatorResponse
{
    /**
     * @param string[] $transports
     */
    public function __construct(
        CollectedClientData $clientDataJSON,
        public readonly AttestationObject $attestationObject,
        public readonly array $transports = []
    ) {
        parent::__construct($clientDataJSON);
    }

    /**
     * @param string[] $transports
     */
    public static function create(
        CollectedClientData $clientDataJSON,
        AttestationObject $attestationObject,
        array $transports = []
    ): self {
        return new self($clientDataJSON, $attestationObject, $transports);
    }

    /**
     * @deprecated since 4.7.0. Please use the property directly.
     * @infection-ignore-all
     */
    public function getAttestationObject(): AttestationObject
    {
        return $this->attestationObject;
    }

    /**
     * @deprecated since 4.7.0. Please use the property directly.
     * @infection-ignore-all
     *
     * @return string[]
     */
    public function getTransports(): array
    {
        return $this->transports;
    }
}
