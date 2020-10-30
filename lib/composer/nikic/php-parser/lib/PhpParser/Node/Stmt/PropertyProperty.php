<?php declare(strict_types=1);

namespace PhpParser\Node\Stmt;

use PhpParser\Node;

class PropertyProperty extends Node\Stmt
{
    /** @var Node\VarLikeIdentifier Name */
    public $name;
    /** @var null|Node\Expr Default */
    public $default;

    /**
     * Constructs a class property node.
     *
     * @param string|Node\VarLikeIdentifier $name       Name
     * @param null|Node\Expr                $default    Default value
     * @param array                         $attributes Additional attributes
     */
    public function __construct($name, Node\Expr $default = null, array $attributes = []) {
        $this->attributes = $attributes;
        $this->name = \is_string($name) ? new Node\VarLikeIdentifier($name) : $name;
        $this->default = $default;
    }

    public function getSubNodeNames() : array {
        return ['name', 'default'];
    }
    
    public function getType() : string {
        return 'Stmt_PropertyProperty';
    }
}
