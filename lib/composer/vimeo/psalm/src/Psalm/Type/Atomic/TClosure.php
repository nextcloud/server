<?php
namespace Psalm\Type\Atomic;

/**
 * Represents a closure where we know the return type and params
 */
class TClosure extends TNamedObject
{
    use CallableTrait;

    /** @var array<string, bool> */
    public $byref_uses = [];

    public function canBeFullyExpressedInPhp(): bool
    {
        return false;
    }
}
