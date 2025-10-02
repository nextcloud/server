<?php

declare(strict_types=1);

namespace Webauthn\CeremonyStep;

use Webauthn\Exception\AuthenticatorResponseVerificationException;

final class HostTopOriginValidator implements TopOriginValidator
{
    public function __construct(
        private readonly string $host
    ) {
    }

    public function validate(string $topOrigin): void
    {
        $topOrigin === $this->host || throw AuthenticatorResponseVerificationException::create(
            'The top origin does not correspond to the host.'
        );
    }
}
