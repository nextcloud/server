<?php
namespace Psalm\Type\Atomic;

abstract class Scalar extends \Psalm\Type\Atomic
{
    public function canBeFullyExpressedInPhp(): bool
    {
        return true;
    }
}
