<?php

declare(strict_types=1);

namespace Webauthn\CeremonyStep;

interface TopOriginValidator
{
    public function validate(string $topOrigin): void;
}
