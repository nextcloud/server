<?php declare(strict_types=1);

namespace PhpParser\Node;

use PhpParser\Node;
use PhpParser\NodeAbstract;

class Attribute extends NodeAbstract
{
    /** @var Name Attribute name */
    public $name;

    /** @var Arg[] Attribute arguments */
    public $args;

    /**
     * @param Node\Name $name       Attribute name
     * @param Arg[]     $args       Attribute arguments
     * @param array     $attributes Additional node attributes
     */
    public function __construct(Name $name, array $args = [], array $attributes = []) {
        $this->attributes = $attributes;
        $this->name = $name;
        $this->args = $args;
    }

    public function getSubNodeNames() : array {
        return ['name', 'args'];
    }

    public function getType() : string {
        return 'Attribute';
    }
}
