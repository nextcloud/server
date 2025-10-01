<?php

declare(strict_types=1);

namespace Webauthn\Event;

use Psr\Http\Message\ServerRequestInterface;
use Throwable;
use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialSource;

class AuthenticatorAssertionResponseValidationFailedEvent
{
    public function __construct(
        public readonly string|PublicKeyCredentialSource $credentialId,
        public readonly AuthenticatorAssertionResponse $authenticatorAssertionResponse,
        public readonly PublicKeyCredentialRequestOptions $publicKeyCredentialRequestOptions,
        public readonly ServerRequestInterface|string $host,
        public readonly ?string $userHandle,
        public readonly Throwable $throwable
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
        if (! $this->credentialId instanceof PublicKeyCredentialSource) {
            trigger_deprecation(
                'web-auth/webauthn-lib',
                '4.6.0',
                'Passing a string for the argument "$credentialId" is deprecated since 4.6.0. Please set the PublicKeyCredentialSource instead.'
            );
        }
    }

    /**
     * @deprecated since 4.7.0 and will be removed in 5.0.0. Please use the `getCredential()` method instead
     * @infection-ignore-all
     */
    public function getCredentialId(): string
    {
        return $this->credentialId instanceof PublicKeyCredentialSource ? $this->credentialId->publicKeyCredentialId : $this->credentialId;
    }

    public function getCredential(): ?PublicKeyCredentialSource
    {
        return $this->credentialId instanceof PublicKeyCredentialSource ? $this->credentialId : null;
    }

    /**
     * @deprecated since 4.8.0. Will be removed in 5.0.0. Please use the property instead.
     */
    public function getAuthenticatorAssertionResponse(): AuthenticatorAssertionResponse
    {
        return $this->authenticatorAssertionResponse;
    }

    public function getPublicKeyCredentialRequestOptions(): PublicKeyCredentialRequestOptions
    {
        return $this->publicKeyCredentialRequestOptions;
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
    public function getUserHandle(): ?string
    {
        return $this->userHandle;
    }

    /**
     * @deprecated since 4.8.0. Will be removed in 5.0.0. Please use the property instead.
     */
    public function getThrowable(): Throwable
    {
        return $this->throwable;
    }
}
