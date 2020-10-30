<?php
namespace Psalm\Type\Atomic;

class TCallableString extends TString
{

    public function getKey(bool $include_extra = true): string
    {
        return 'callable-string';
    }

    public function getId(bool $nested = false): string
    {
        return $this->getKey();
    }

    public function canBeFullyExpressedInPhp(): bool
    {
        return false;
    }

    public function getAssertionString(): string
    {
        return 'string';
    }
}
