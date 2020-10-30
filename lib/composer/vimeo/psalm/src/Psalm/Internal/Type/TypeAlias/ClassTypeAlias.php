<?php
namespace Psalm\Internal\Type\TypeAlias;

class ClassTypeAlias implements \Psalm\Internal\Type\TypeAlias
{
    /**
     * @var list<\Psalm\Type\Atomic>
     */
    public $replacement_atomic_types;

    /**
     * @param list<\Psalm\Type\Atomic> $replacement_atomic_types
     */
    public function __construct(array $replacement_atomic_types)
    {
        $this->replacement_atomic_types = $replacement_atomic_types;
    }
}
