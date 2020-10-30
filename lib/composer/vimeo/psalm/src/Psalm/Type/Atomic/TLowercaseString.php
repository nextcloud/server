<?php
namespace Psalm\Type\Atomic;

class TLowercaseString extends TString
{
    public function getKey(bool $include_extra = true): string
    {
        return 'string';
    }

    public function getId(bool $nested = false): string
    {
        return 'lowercase-string';
    }

    public function canBeFullyExpressedInPhp(): bool
    {
        return false;
    }
}
