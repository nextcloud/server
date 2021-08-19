<?php declare(strict_types=1);

namespace PhpParser;

use PhpParser\Node\Expr;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\NullableType;
use PhpParser\Node\Scalar;
use PhpParser\Node\Stmt;
use PhpParser\Node\UnionType;

/**
 * This class defines helpers used in the implementation of builders. Don't use it directly.
 *
 * @internal
 */
final class BuilderHelpers
{
    /**
     * Normalizes a node: Converts builder objects to nodes.
     *
     * @param Node|Builder $node The node to normalize
     *
     * @return Node The normalized node
     */
    public static function normalizeNode($node) : Node {
        if ($node instanceof Builder) {
            return $node->getNode();
        }

        if ($node instanceof Node) {
            return $node;
        }

        throw new \LogicException('Expected node or builder object');
    }

    /**
     * Normalizes a node to a statement.
     *
     * Expressions are wrapped in a Stmt\Expression node.
     *
     * @param Node|Builder $node The node to normalize
     *
     * @return Stmt The normalized statement node
     */
    public static function normalizeStmt($node) : Stmt {
        $node = self::normalizeNode($node);
        if ($node instanceof Stmt) {
            return $node;
        }

        if ($node instanceof Expr) {
            return new Stmt\Expression($node);
        }

        throw new \LogicException('Expected statement or expression node');
    }

    /**
     * Normalizes strings to Identifier.
     *
     * @param string|Identifier $name The identifier to normalize
     *
     * @return Identifier The normalized identifier
     */
    public static function normalizeIdentifier($name) : Identifier {
        if ($name instanceof Identifier) {
            return $name;
        }

        if (\is_string($name)) {
            return new Identifier($name);
        }

        throw new \LogicException('Expected string or instance of Node\Identifier');
    }

    /**
     * Normalizes strings to Identifier, also allowing expressions.
     *
     * @param string|Identifier|Expr $name The identifier to normalize
     *
     * @return Identifier|Expr The normalized identifier or expression
     */
    public static function normalizeIdentifierOrExpr($name) {
        if ($name instanceof Identifier || $name instanceof Expr) {
            return $name;
        }

        if (\is_string($name)) {
            return new Identifier($name);
        }

        throw new \LogicException('Expected string or instance of Node\Identifier or Node\Expr');
    }

    /**
     * Normalizes a name: Converts string names to Name nodes.
     *
     * @param Name|string $name The name to normalize
     *
     * @return Name The normalized name
     */
    public static function normalizeName($name) : Name {
        return self::normalizeNameCommon($name, false);
    }

    /**
     * Normalizes a name: Converts string names to Name nodes, while also allowing expressions.
     *
     * @param Expr|Name|string $name The name to normalize
     *
     * @return Name|Expr The normalized name or expression
     */
    public static function normalizeNameOrExpr($name) {
        return self::normalizeNameCommon($name, true);
    }

    /**
     * Normalizes a name: Converts string names to Name nodes, optionally allowing expressions.
     *
     * @param Expr|Name|string $name      The name to normalize
     * @param bool             $allowExpr Whether to also allow expressions
     *
     * @return Name|Expr The normalized name, or expression (if allowed)
     */
    private static function normalizeNameCommon($name, bool $allowExpr) {
        if ($name instanceof Name) {
            return $name;
        }

        if (is_string($name)) {
            if (!$name) {
                throw new \LogicException('Name cannot be empty');
            }

            if ($name[0] === '\\') {
                return new Name\FullyQualified(substr($name, 1));
            }

            if (0 === strpos($name, 'namespace\\')) {
                return new Name\Relative(substr($name, strlen('namespace\\')));
            }

            return new Name($name);
        }

        if ($allowExpr) {
            if ($name instanceof Expr) {
                return $name;
            }
            throw new \LogicException(
                'Name must be a string or an instance of Node\Name or Node\Expr'
            );
        }

        throw new \LogicException('Name must be a string or an instance of Node\Name');
    }

    /**
     * Normalizes a type: Converts plain-text type names into proper AST representation.
     *
     * In particular, builtin types become Identifiers, custom types become Names and nullables
     * are wrapped in NullableType nodes.
     *
     * @param string|Name|Identifier|NullableType|UnionType $type The type to normalize
     *
     * @return Name|Identifier|NullableType|UnionType The normalized type
     */
    public static function normalizeType($type) {
        if (!is_string($type)) {
            if (
                !$type instanceof Name && !$type instanceof Identifier &&
                !$type instanceof NullableType && !$type instanceof UnionType
            ) {
                throw new \LogicException(
                    'Type must be a string, or an instance of Name, Identifier, NullableType or UnionType'
                );
            }
            return $type;
        }

        $nullable = false;
        if (strlen($type) > 0 && $type[0] === '?') {
            $nullable = true;
            $type = substr($type, 1);
        }

        $builtinTypes = [
            'array', 'callable', 'string', 'int', 'float', 'bool', 'iterable', 'void', 'object', 'mixed', 'never',
        ];

        $lowerType = strtolower($type);
        if (in_array($lowerType, $builtinTypes)) {
            $type = new Identifier($lowerType);
        } else {
            $type = self::normalizeName($type);
        }

        $notNullableTypes = [
            'void', 'mixed', 'never',
        ];
        if ($nullable && in_array((string) $type, $notNullableTypes)) {
            throw new \LogicException(sprintf('%s type cannot be nullable', $type));
        }

        return $nullable ? new NullableType($type) : $type;
    }

    /**
     * Normalizes a value: Converts nulls, booleans, integers,
     * floats, strings and arrays into their respective nodes
     *
     * @param Node\Expr|bool|null|int|float|string|array $value The value to normalize
     *
     * @return Expr The normalized value
     */
    public static function normalizeValue($value) : Expr {
        if ($value instanceof Node\Expr) {
            return $value;
        }

        if (is_null($value)) {
            return new Expr\ConstFetch(
                new Name('null')
            );
        }

        if (is_bool($value)) {
            return new Expr\ConstFetch(
                new Name($value ? 'true' : 'false')
            );
        }

        if (is_int($value)) {
            return new Scalar\LNumber($value);
        }

        if (is_float($value)) {
            return new Scalar\DNumber($value);
        }

        if (is_string($value)) {
            return new Scalar\String_($value);
        }

        if (is_array($value)) {
            $items = [];
            $lastKey = -1;
            foreach ($value as $itemKey => $itemValue) {
                // for consecutive, numeric keys don't generate keys
                if (null !== $lastKey && ++$lastKey === $itemKey) {
                    $items[] = new Expr\ArrayItem(
                        self::normalizeValue($itemValue)
                    );
                } else {
                    $lastKey = null;
                    $items[] = new Expr\ArrayItem(
                        self::normalizeValue($itemValue),
                        self::normalizeValue($itemKey)
                    );
                }
            }

            return new Expr\Array_($items);
        }

        throw new \LogicException('Invalid value');
    }

    /**
     * Normalizes a doc comment: Converts plain strings to PhpParser\Comment\Doc.
     *
     * @param Comment\Doc|string $docComment The doc comment to normalize
     *
     * @return Comment\Doc The normalized doc comment
     */
    public static function normalizeDocComment($docComment) : Comment\Doc {
        if ($docComment instanceof Comment\Doc) {
            return $docComment;
        }

        if (is_string($docComment)) {
            return new Comment\Doc($docComment);
        }

        throw new \LogicException('Doc comment must be a string or an instance of PhpParser\Comment\Doc');
    }

    /**
     * Normalizes a attribute: Converts attribute to the Attribute Group if needed.
     *
     * @param Node\Attribute|Node\AttributeGroup $attribute
     *
     * @return Node\AttributeGroup The Attribute Group
     */
    public static function normalizeAttribute($attribute) : Node\AttributeGroup
    {
        if ($attribute instanceof Node\AttributeGroup) {
            return $attribute;
        }

        if (!($attribute instanceof Node\Attribute)) {
            throw new \LogicException('Attribute must be an instance of PhpParser\Node\Attribute or PhpParser\Node\AttributeGroup');
        }

        return new Node\AttributeGroup([$attribute]);
    }

    /**
     * Adds a modifier and returns new modifier bitmask.
     *
     * @param int $modifiers Existing modifiers
     * @param int $modifier  Modifier to set
     *
     * @return int New modifiers
     */
    public static function addModifier(int $modifiers, int $modifier) : int {
        Stmt\Class_::verifyModifier($modifiers, $modifier);
        return $modifiers | $modifier;
    }
}
