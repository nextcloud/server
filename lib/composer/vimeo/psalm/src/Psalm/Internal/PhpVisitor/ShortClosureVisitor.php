<?php
namespace Psalm\Internal\PhpVisitor;

use PhpParser;

/**
 * @internal
 */
class ShortClosureVisitor extends PhpParser\NodeVisitorAbstract
{
    /**
     * @var array<string, bool>
     */
    protected $used_variables = [];

    public function enterNode(PhpParser\Node $node): ?int
    {
        if ($node instanceof PhpParser\Node\Expr\Variable && \is_string($node->name)) {
            $this->used_variables['$' . $node->name] = true;
        }

        return null;
    }

    /**
     * @return array<string, bool>
     */
    public function getUsedVariables(): array
    {
        return $this->used_variables;
    }
}
