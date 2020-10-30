<?php
namespace Psalm\Type\Atomic;

class TEmptyMixed extends TMixed
{
    public function getId(bool $nested = false): string
    {
        return 'empty-mixed';
    }
}
