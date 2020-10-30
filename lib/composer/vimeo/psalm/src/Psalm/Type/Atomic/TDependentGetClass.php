<?php
namespace Psalm\Type\Atomic;

use Psalm\Type\Union;

/**
 * Represents a string whose value is a fully-qualified class found by get_class($var)
 */
class TDependentGetClass extends TString
{
    /**
     * Used to hold information as to what this refers to
     *
     * @var string
     */
    public $typeof;

    /**
     * @var Union
     */
    public $as_type;

    /**
     * @param string $typeof the variable id
     */
    public function __construct(string $typeof, Union $as_type)
    {
        $this->typeof = $typeof;
        $this->as_type = $as_type;
    }

    public function getId(bool $nested = false): string
    {
        return $this->as_type->isMixed()
            || $this->as_type->hasObject()
            ? 'class-string'
            : 'class-string<' . $this->as_type->getId() . '>';
    }

    public function canBeFullyExpressedInPhp(): bool
    {
        return false;
    }
}
