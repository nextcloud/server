<?php
namespace Psalm\Type\Atomic;

class TEmptyNumeric extends TNumeric
{
    public function getId(bool $nested = false): string
    {
        return 'empty-numeric';
    }
}
