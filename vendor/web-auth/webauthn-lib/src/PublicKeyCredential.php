<?php

declare(strict_types=1);

namespace Webauthn;

use Stringable;
use const E_USER_DEPRECATED;
use const JSON_THROW_ON_ERROR;

/**
 * @see https://www.w3.org/TR/webauthn/#iface-pkcredential
 */
class PublicKeyCredential extends Credential implements Stringable
{
    public function __construct(
        null|string $id,
        string $type,
        string $rawId,
        public readonly AuthenticatorResponse $response
    ) {
        parent::__construct($id, $type, $rawId);
    }

    /**
     * @deprecated since 4.8.0.
     * @infection-ignore-all
     */
    public function __toString(): string
    {
        return json_encode($this->getPublicKeyCredentialDescriptor(), JSON_THROW_ON_ERROR);
    }

    public static function create(null|string $id, string $type, string $rawId, AuthenticatorResponse $response): self
    {
        return new self($id, $type, $rawId, $response);
    }

    /**
     * @deprecated since 4.7.0. Please use the property directly.
     * @infection-ignore-all
     */
    public function getRawId(): string
    {
        return $this->rawId;
    }

    /**
     * @deprecated since 4.7.0. Please use the property directly.
     * @infection-ignore-all
     */
    public function getResponse(): AuthenticatorResponse
    {
        return $this->response;
    }

    /**
     * @param string[] $transport
     */
    public function getPublicKeyCredentialDescriptor(null|array $transport = null): PublicKeyCredentialDescriptor
    {
        if ($transport !== null) {
            trigger_deprecation(
                'web-auth/webauthn-lib',
                '4.8.0',
                'The parameter "$transport" is deprecated and will be removed in 5.0.0.'
            );
            @trigger_error(
                sprintf(
                    'The $transport argument of %s() is deprecated since 4.8.0 and will be removed in 5.0.0.',
                    __METHOD__
                ),
                E_USER_DEPRECATED
            );
        }
        $transport ??= $this->response instanceof AuthenticatorAttestationResponse ? $this->response->transports : [];

        return PublicKeyCredentialDescriptor::create($this->type, $this->rawId, $transport);
    }
}
