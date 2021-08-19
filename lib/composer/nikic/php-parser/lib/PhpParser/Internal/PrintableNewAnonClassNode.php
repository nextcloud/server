<?php declare(strict_types=1);

namespace PhpParser\Internal;

use PhpParser\Node;
use PhpParser\Node\Expr;

/**
 * This node is used internally by the format-preserving pretty printer to print anonymous classes.
 *
 * The normal anonymous class structure violates assumptions about the order of token offsets.
 * Namely, the constructor arguments are part of the Expr\New_ node and follow the class node, even
 * though they are actually interleaved with them. This special node type is used temporarily to
 * restore a sane token offset order.
 *
 * @internal
 */
class PrintableNewAnonClassNode extends Expr
{
    /** @var Node\AttributeGroup[] PHP attribute groups */
    public $attrGroups;
    /** @var Node\Arg[] Arguments */
    public $args;
    /** @var null|Node\Name Name of extended class */
    public $extends;
    /** @var Node\Name[] Names of implemented interfaces */
    public $implements;
    /** @var Node\Stmt[] Statements */
    public $stmts;

    public function __construct(
        array $attrGroups, array $args, Node\Name $extends = null, array $implements,
        array $stmts, array $attributes
    ) {
        parent::__construct($attributes);
        $this->attrGroups = $attrGroups;
        $this->args = $args;
        $this->extends = $extends;
        $this->implements = $implements;
        $this->stmts = $stmts;
    }

    public static function fromNewNode(Expr\New_ $newNode) {
        $class = $newNode->class;
        assert($class instanceof Node\Stmt\Class_);
        // We don't assert that $class->name is null here, to allow consumers to assign unique names
        // to anonymous classes for their own purposes. We simplify ignore the name here.
        return new self(
            $class->attrGroups, $newNode->args, $class->extends, $class->implements,
            $class->stmts, $newNode->getAttributes()
        );
    }

    public function getType() : string {
        return 'Expr_PrintableNewAnonClass';
    }

    public function getSubNodeNames() : array {
        return ['attrGroups', 'args', 'extends', 'implements', 'stmts'];
    }
}
