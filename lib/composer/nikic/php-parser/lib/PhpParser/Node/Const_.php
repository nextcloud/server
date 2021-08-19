<?php declare(strict_types=1);

namespace PhpParser\Node;

use PhpParser\NodeAbstract;

/**
 * @property Name $namespacedName Namespaced name (for global constants, if using NameResolver)
 */
class Const_ extends NodeAbstract
{
    /** @var Identifier Name */
    public $name;
    /** @var Expr Value */
    public $value;

    /**
     * Constructs a const node for use in class const and const statements.
     *
     * @param string|Identifier $name       Name
     * @param Expr              $value      Value
     * @param array             $attributes Additional attributes
     */
    public function __construct($name, Expr $value, array $attributes = []) {
        $this->attributes = $attributes;
        $this->name = \is_string($name) ? new Identifier($name) : $name;
        $this->value = $value;
    }

    public function getSubNodeNames() : array {
        return ['name', 'value'];
    }
    
    public function getType() : string {
        return 'Const';
    }
}
