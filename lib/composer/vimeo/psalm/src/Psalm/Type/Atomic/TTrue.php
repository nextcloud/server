<?php
namespace Psalm\Type\Atomic;

class TTrue extends TBool
{
    public function __toString(): string
    {
        return 'true';
    }

    public function getKey(bool $include_extra = true): string
    {
        return 'true';
    }

    public function canBeFullyExpressedInPhp(): bool
    {
        return false;
    }
}
