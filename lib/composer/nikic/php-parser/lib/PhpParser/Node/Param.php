<?php declare(strict_types=1);

namespace PhpParser\Node;

use PhpParser\NodeAbstract;

class Param extends NodeAbstract
{
    /** @var null|Identifier|Name|NullableType|UnionType Type declaration */
    public $type;
    /** @var bool Whether parameter is passed by reference */
    public $byRef;
    /** @var bool Whether this is a variadic argument */
    public $variadic;
    /** @var Expr\Variable|Expr\Error Parameter variable */
    public $var;
    /** @var null|Expr Default value */
    public $default;
    /** @var int */
    public $flags;
    /** @var AttributeGroup[] PHP attribute groups */
    public $attrGroups;

    /**
     * Constructs a parameter node.
     *
     * @param Expr\Variable|Expr\Error                           $var        Parameter variable
     * @param null|Expr                                          $default    Default value
     * @param null|string|Identifier|Name|NullableType|UnionType $type       Type declaration
     * @param bool                                               $byRef      Whether is passed by reference
     * @param bool                                               $variadic   Whether this is a variadic argument
     * @param array                                              $attributes Additional attributes
     * @param int                                                $flags      Optional visibility flags
     * @param AttributeGroup[]                                   $attrGroups PHP attribute groups
     */
    public function __construct(
        $var, Expr $default = null, $type = null,
        bool $byRef = false, bool $variadic = false,
        array $attributes = [],
        int $flags = 0,
        array $attrGroups = []
    ) {
        $this->attributes = $attributes;
        $this->type = \is_string($type) ? new Identifier($type) : $type;
        $this->byRef = $byRef;
        $this->variadic = $variadic;
        $this->var = $var;
        $this->default = $default;
        $this->flags = $flags;
        $this->attrGroups = $attrGroups;
    }

    public function getSubNodeNames() : array {
        return ['attrGroups', 'flags', 'type', 'byRef', 'variadic', 'var', 'default'];
    }

    public function getType() : string {
        return 'Param';
    }
}
