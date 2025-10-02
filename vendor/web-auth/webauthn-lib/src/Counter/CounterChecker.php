<?php

declare(strict_types=1);

namespace Webauthn\Counter;

use Webauthn\PublicKeyCredentialSource;

interface CounterChecker
{
    public function check(PublicKeyCredentialSource $publicKeyCredentialSource, int $currentCounter): void;
}
