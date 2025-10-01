<?php

declare(strict_types=1);

namespace Webauthn;

use Symfony\Component\HttpFoundation\Request;

interface FakeCredentialGenerator
{
    /**
     * @return PublicKeyCredentialDescriptor[]
     */
    public function generate(Request $request, string $username): array;
}
