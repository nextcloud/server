<?php
namespace Psalm\Type\Atomic;

class TEmptyScalar extends TScalar
{
    public function getId(bool $nested = false): string
    {
        return 'empty-scalar';
    }
}
