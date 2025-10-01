<?php

declare(strict_types=1);

namespace Webauthn\Event;

use Psr\Http\Message\ServerRequestInterface;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialSource;

class AuthenticatorAttestationResponseValidationSucceededEvent
{
    public function __construct(
        public readonly AuthenticatorAttestationResponse $authenticatorAttestationResponse,
        public readonly PublicKeyCredentialCreationOptions $publicKeyCredentialCreationOptions,
        public readonly ServerRequestInterface|string $host,
        public readonly PublicKeyCredentialSource $publicKeyCredentialSource
    ) {
        if ($host instanceof ServerRequestInterface) {
            trigger_deprecation(
                'web-auth/webauthn-lib',
                '4.5.0',
                sprintf(
                    'Passing a %s to the class "%s" is deprecated since 4.5.0 and will be removed in 5.0.0. Please inject the host as a string instead.',
                    ServerRequestInterface::class,
                    self::class
                )
            );
        }
    }

    /**
     * @deprecated since 4.8.0. Will be removed in 5.0.0. Please use the property instead.
     */
    public function getAuthenticatorAttestationResponse(): AuthenticatorAttestationResponse
    {
        return $this->authenticatorAttestationResponse;
    }

    /**
     * @deprecated since 4.8.0. Will be removed in 5.0.0. Please use the property instead.
     */
    public function getPublicKeyCredentialCreationOptions(): PublicKeyCredentialCreationOptions
    {
        return $this->publicKeyCredentialCreationOptions;
    }

    /**
     * @deprecated since 4.5.0 and will be removed in 5.0.0. Please use the `host` property instead
     * @infection-ignore-all
     */
    public function getRequest(): ServerRequestInterface|string
    {
        return $this->host;
    }

    /**
     * @deprecated since 4.8.0. Will be removed in 5.0.0. Please use the property instead.
     */
    public function getPublicKeyCredentialSource(): PublicKeyCredentialSource
    {
        return $this->publicKeyCredentialSource;
    }
}
