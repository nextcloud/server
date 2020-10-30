<?php
namespace Psalm\Type\Atomic;

class TFalse extends TBool
{
    public function __toString(): string
    {
        return 'false';
    }
    
    public function getKey(bool $include_extra = true): string
    {
        return 'false';
    }

    public function canBeFullyExpressedInPhp(): bool
    {
        return false;
    }
}
