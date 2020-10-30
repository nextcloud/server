<?php
namespace Psalm\Internal\Analyzer\Statements\Expression;

use PhpParser;
use Psalm\Internal\Analyzer\ClassLikeAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\FileSource;
use function is_string;
use function in_array;
use function strtolower;
use function count;
use function implode;

class ExpressionIdentifier
{
    public static function getVarId(
        PhpParser\Node\Expr $stmt,
        ?string $this_class_name,
        ?FileSource $source = null,
        ?int &$nesting = null
    ): ?string {
        if ($stmt instanceof PhpParser\Node\Expr\Variable && is_string($stmt->name)) {
            return '$' . $stmt->name;
        }

        if ($stmt instanceof PhpParser\Node\Expr\StaticPropertyFetch
            && $stmt->name instanceof PhpParser\Node\Identifier
            && $stmt->class instanceof PhpParser\Node\Name
        ) {
            if (count($stmt->class->parts) === 1
                && in_array(strtolower($stmt->class->parts[0]), ['self', 'static', 'parent'], true)
            ) {
                if (!$this_class_name) {
                    $fq_class_name = $stmt->class->parts[0];
                } else {
                    $fq_class_name = $this_class_name;
                }
            } else {
                $fq_class_name = $source
                    ? ClassLikeAnalyzer::getFQCLNFromNameObject(
                        $stmt->class,
                        $source->getAliases()
                    )
                    : implode('\\', $stmt->class->parts);
            }

            return $fq_class_name . '::$' . $stmt->name->name;
        }

        if ($stmt instanceof PhpParser\Node\Expr\PropertyFetch && $stmt->name instanceof PhpParser\Node\Identifier) {
            $object_id = self::getVarId($stmt->var, $this_class_name, $source);

            if (!$object_id) {
                return null;
            }

            return $object_id . '->' . $stmt->name->name;
        }

        if ($stmt instanceof PhpParser\Node\Expr\ArrayDimFetch && $nesting !== null) {
            ++$nesting;

            return self::getVarId($stmt->var, $this_class_name, $source, $nesting);
        }

        return null;
    }

    public static function getRootVarId(
        PhpParser\Node\Expr $stmt,
        ?string $this_class_name,
        ?FileSource $source = null
    ): ?string {
        if ($stmt instanceof PhpParser\Node\Expr\Variable
            || $stmt instanceof PhpParser\Node\Expr\StaticPropertyFetch
        ) {
            return self::getVarId($stmt, $this_class_name, $source);
        }

        if ($stmt instanceof PhpParser\Node\Expr\PropertyFetch && $stmt->name instanceof PhpParser\Node\Identifier) {
            $property_root = self::getRootVarId($stmt->var, $this_class_name, $source);

            if ($property_root) {
                return $property_root . '->' . $stmt->name->name;
            }
        }

        if ($stmt instanceof PhpParser\Node\Expr\ArrayDimFetch) {
            return self::getRootVarId($stmt->var, $this_class_name, $source);
        }

        return null;
    }

    public static function getArrayVarId(
        PhpParser\Node\Expr $stmt,
        ?string $this_class_name,
        ?FileSource $source = null
    ): ?string {
        if ($stmt instanceof PhpParser\Node\Expr\Assign) {
            return self::getArrayVarId($stmt->var, $this_class_name, $source);
        }

        if ($stmt instanceof PhpParser\Node\Expr\ArrayDimFetch) {
            $root_var_id = self::getArrayVarId($stmt->var, $this_class_name, $source);

            $offset = null;

            if ($root_var_id) {
                if ($stmt->dim instanceof PhpParser\Node\Scalar\String_
                    || $stmt->dim instanceof PhpParser\Node\Scalar\LNumber
                ) {
                    $offset = $stmt->dim instanceof PhpParser\Node\Scalar\String_
                        ? '\'' . $stmt->dim->value . '\''
                        : $stmt->dim->value;
                } elseif ($stmt->dim instanceof PhpParser\Node\Expr\Variable
                    && is_string($stmt->dim->name)
                ) {
                    $offset = '$' . $stmt->dim->name;
                } elseif ($stmt->dim instanceof PhpParser\Node\Expr\ConstFetch) {
                    $offset = implode('\\', $stmt->dim->name->parts);
                } elseif ($stmt->dim instanceof PhpParser\Node\Expr\PropertyFetch) {
                    $object_id = self::getArrayVarId($stmt->dim->var, $this_class_name, $source);

                    if ($object_id && $stmt->dim->name instanceof PhpParser\Node\Identifier) {
                        $offset = $object_id . '->' . $stmt->dim->name;
                    }
                } elseif ($stmt->dim instanceof PhpParser\Node\Expr\ClassConstFetch
                    && $stmt->dim->name instanceof PhpParser\Node\Identifier
                    && $stmt->dim->class instanceof PhpParser\Node\Name
                    && $stmt->dim->class->parts[0] === 'static'
                ) {
                    $offset = 'static::' . $stmt->dim->name;
                } elseif ($stmt->dim
                    && $source instanceof StatementsAnalyzer
                    && ($stmt_dim_type = $source->node_data->getType($stmt->dim))
                    && (!$stmt->dim instanceof PhpParser\Node\Expr\ClassConstFetch
                        || !$stmt->dim->name instanceof PhpParser\Node\Identifier
                        || $stmt->dim->name->name !== 'class'
                    )
                ) {
                    if ($stmt_dim_type->isSingleStringLiteral()) {
                        $offset = '\'' . $stmt_dim_type->getSingleStringLiteral()->value . '\'';
                    } elseif ($stmt_dim_type->isSingleIntLiteral()) {
                        $offset = $stmt_dim_type->getSingleIntLiteral()->value;
                    }
                } elseif ($stmt->dim instanceof PhpParser\Node\Expr\ClassConstFetch
                    && $stmt->dim->name instanceof PhpParser\Node\Identifier
                ) {
                    /** @var string|null */
                    $resolved_name = $stmt->dim->class->getAttribute('resolvedName');

                    if ($resolved_name) {
                        $offset = $resolved_name . '::' . $stmt->dim->name;
                    }
                }

                return $offset !== null ? $root_var_id . '[' . $offset . ']' : null;
            }
        }

        if ($stmt instanceof PhpParser\Node\Expr\PropertyFetch) {
            $object_id = self::getArrayVarId($stmt->var, $this_class_name, $source);

            if (!$object_id) {
                return null;
            }

            if ($stmt->name instanceof PhpParser\Node\Identifier) {
                return $object_id . '->' . $stmt->name;
            } elseif ($source instanceof StatementsAnalyzer
                && ($stmt_name_type = $source->node_data->getType($stmt->name))
                && $stmt_name_type->isSingleStringLiteral()
            ) {
                return $object_id . '->' . $stmt_name_type->getSingleStringLiteral()->value;
            } else {
                return null;
            }
        }

        if ($stmt instanceof PhpParser\Node\Expr\ClassConstFetch
            && $stmt->name instanceof PhpParser\Node\Identifier
        ) {
            /** @var string|null */
            $resolved_name = $stmt->class->getAttribute('resolvedName');

            if ($resolved_name) {
                if (($resolved_name === 'self' || $resolved_name === 'static') && $this_class_name) {
                    $resolved_name = $this_class_name;
                }

                return $resolved_name . '::' . $stmt->name;
            }
        }

        if ($stmt instanceof PhpParser\Node\Expr\MethodCall
            && $stmt->name instanceof PhpParser\Node\Identifier
            && !$stmt->args
        ) {
            $config = \Psalm\Config::getInstance();

            if ($config->memoize_method_calls || isset($stmt->pure)) {
                $lhs_var_name = self::getArrayVarId(
                    $stmt->var,
                    $this_class_name,
                    $source
                );

                if (!$lhs_var_name) {
                    return null;
                }

                return $lhs_var_name . '->' . strtolower($stmt->name->name) . '()';
            }
        }

        return self::getVarId($stmt, $this_class_name, $source);
    }
}
