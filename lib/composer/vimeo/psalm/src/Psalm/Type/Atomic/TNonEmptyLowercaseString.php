<?php
namespace Psalm\Type\Atomic;

class TNonEmptyLowercaseString extends TNonEmptyString
{
    public function getKey(bool $include_extra = true): string
    {
        return 'string';
    }

    public function getId(bool $nested = false): string
    {
        return 'non-empty-lowercase-string';
    }

    /**
     * @return false
     */
    public function canBeFullyExpressedInPhp(): bool
    {
        return false;
    }
}
