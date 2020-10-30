<?php
namespace Psalm\Internal\PhpVisitor;

use PhpParser;
use Psalm\Internal\Analyzer\Statements\Expression\ExpressionIdentifier;

/**
 * @internal
 */
class AssignmentMapVisitor extends PhpParser\NodeVisitorAbstract
{
    /**
     * @var array<string, array<string, bool>>
     */
    protected $assignment_map = [];

    /**
     * @var string|null
     */
    protected $this_class_name;

    public function __construct(?string $this_class_name)
    {
        $this->this_class_name = $this_class_name;
    }

    public function enterNode(PhpParser\Node $node): ?int
    {
        if ($node instanceof PhpParser\Node\Expr\Assign) {
            $left_var_id = ExpressionIdentifier::getRootVarId($node->var, $this->this_class_name);
            $right_var_id = ExpressionIdentifier::getRootVarId($node->expr, $this->this_class_name);

            if ($left_var_id) {
                $this->assignment_map[$left_var_id][$right_var_id ?: 'isset'] = true;
            }

            return PhpParser\NodeTraverser::DONT_TRAVERSE_CHILDREN;
        } elseif ($node instanceof PhpParser\Node\Expr\PostInc
            || $node instanceof PhpParser\Node\Expr\PostDec
            || $node instanceof PhpParser\Node\Expr\PreInc
            || $node instanceof PhpParser\Node\Expr\PreDec
            || $node instanceof PhpParser\Node\Expr\AssignOp
        ) {
            $var_id = ExpressionIdentifier::getRootVarId($node->var, $this->this_class_name);

            if ($var_id) {
                $this->assignment_map[$var_id][$var_id] = true;
            }

            return PhpParser\NodeTraverser::DONT_TRAVERSE_CHILDREN;
        } elseif ($node instanceof PhpParser\Node\Expr\FuncCall) {
            foreach ($node->args as $arg) {
                $arg_var_id = ExpressionIdentifier::getRootVarId($arg->value, $this->this_class_name);

                if ($arg_var_id) {
                    $this->assignment_map[$arg_var_id][$arg_var_id] = true;
                }
            }
        } elseif ($node instanceof PhpParser\Node\Stmt\Unset_) {
            foreach ($node->vars as $arg) {
                $arg_var_id = ExpressionIdentifier::getRootVarId($arg, $this->this_class_name);

                if ($arg_var_id) {
                    $this->assignment_map[$arg_var_id][$arg_var_id] = true;
                }
            }
        }

        return null;
    }

    /**
     * @return array<string, array<string, bool>>
     */
    public function getAssignmentMap(): array
    {
        return $this->assignment_map;
    }
}
