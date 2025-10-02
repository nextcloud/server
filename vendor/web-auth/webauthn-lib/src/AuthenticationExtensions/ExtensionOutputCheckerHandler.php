<?php

declare(strict_types=1);

namespace Webauthn\AuthenticationExtensions;

class ExtensionOutputCheckerHandler
{
    /**
     * @var ExtensionOutputChecker[]
     */
    private array $checkers = [];

    public static function create(): self
    {
        return new self();
    }

    public function add(ExtensionOutputChecker $checker): void
    {
        $this->checkers[] = $checker;
    }

    public function check(AuthenticationExtensions $inputs, AuthenticationExtensions $outputs): void
    {
        foreach ($this->checkers as $checker) {
            $checker->check($inputs, $outputs);
        }
    }
}
