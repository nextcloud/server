<?php
namespace Psalm\Type\Atomic;

class TNonEmptyMixed extends TMixed
{
    public function getId(bool $nested = false): string
    {
        return 'non-empty-mixed';
    }
}
