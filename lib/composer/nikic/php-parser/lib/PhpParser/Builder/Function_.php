<?php declare(strict_types=1);

namespace PhpParser\Builder;

use PhpParser;
use PhpParser\BuilderHelpers;
use PhpParser\Node;
use PhpParser\Node\Stmt;

class Function_ extends FunctionLike
{
    protected $name;
    protected $stmts = [];

    /** @var Node\AttributeGroup[] */
    protected $attributeGroups = [];

    /**
     * Creates a function builder.
     *
     * @param string $name Name of the function
     */
    public function __construct(string $name) {
        $this->name = $name;
    }

    /**
     * Adds a statement.
     *
     * @param Node|PhpParser\Builder $stmt The statement to add
     *
     * @return $this The builder instance (for fluid interface)
     */
    public function addStmt($stmt) {
        $this->stmts[] = BuilderHelpers::normalizeStmt($stmt);

        return $this;
    }

    /**
     * Adds an attribute group.
     *
     * @param Node\Attribute|Node\AttributeGroup $attribute
     *
     * @return $this The builder instance (for fluid interface)
     */
    public function addAttribute($attribute) {
        $this->attributeGroups[] = BuilderHelpers::normalizeAttribute($attribute);

        return $this;
    }

    /**
     * Returns the built function node.
     *
     * @return Stmt\Function_ The built function node
     */
    public function getNode() : Node {
        return new Stmt\Function_($this->name, [
            'byRef'      => $this->returnByRef,
            'params'     => $this->params,
            'returnType' => $this->returnType,
            'stmts'      => $this->stmts,
            'attrGroups' => $this->attributeGroups,
        ], $this->attributes);
    }
}
