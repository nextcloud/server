<?php
namespace Psalm\Internal\Analyzer\FunctionLike;

use PhpParser;
use Psalm\Internal\Analyzer\Statements\Block\ForeachAnalyzer;
use Psalm\Type;
use Psalm\Type\Atomic;
use function array_merge;

/**
 * A class for analysing a given method call's effects in relation to $this/self and also looking at return types
 */
class ReturnTypeCollector
{
    /**
     * Gets the return types from a list of statements
     *
     * @param  array<PhpParser\Node>     $stmts
     * @param  list<Type\Union>         $yield_types
     *
     * @return list<Type\Union>    a list of return types
     */
    public static function getReturnTypes(
        \Psalm\Codebase $codebase,
        \Psalm\Internal\Provider\NodeDataProvider $nodes,
        array $stmts,
        array &$yield_types,
        bool $collapse_types = false
    ): array {
        $return_types = [];

        foreach ($stmts as $stmt) {
            if ($stmt instanceof PhpParser\Node\Stmt\Return_) {
                if (!$stmt->expr) {
                    $return_types[] = Type::getVoid();
                } elseif ($stmt_type = $nodes->getType($stmt)) {
                    $return_types[] = $stmt_type;

                    $yield_types = array_merge($yield_types, self::getYieldTypeFromExpression($stmt->expr, $nodes));
                } else {
                    $return_types[] = Type::getMixed();
                }

                break;
            }

            if ($stmt instanceof PhpParser\Node\Stmt\Throw_
                || $stmt instanceof PhpParser\Node\Stmt\Break_
                || $stmt instanceof PhpParser\Node\Stmt\Continue_
            ) {
                break;
            }

            if ($stmt instanceof PhpParser\Node\Stmt\Expression) {
                if ($stmt->expr instanceof PhpParser\Node\Expr\Assign) {
                    $return_types = array_merge(
                        $return_types,
                        self::getReturnTypes(
                            $codebase,
                            $nodes,
                            [$stmt->expr->expr],
                            $yield_types
                        )
                    );
                }

                $yield_types = array_merge($yield_types, self::getYieldTypeFromExpression($stmt->expr, $nodes));
            } elseif ($stmt instanceof PhpParser\Node\Stmt\If_) {
                $return_types = array_merge(
                    $return_types,
                    self::getReturnTypes(
                        $codebase,
                        $nodes,
                        $stmt->stmts,
                        $yield_types
                    )
                );

                foreach ($stmt->elseifs as $elseif) {
                    $return_types = array_merge(
                        $return_types,
                        self::getReturnTypes(
                            $codebase,
                            $nodes,
                            $elseif->stmts,
                            $yield_types
                        )
                    );
                }

                if ($stmt->else) {
                    $return_types = array_merge(
                        $return_types,
                        self::getReturnTypes(
                            $codebase,
                            $nodes,
                            $stmt->else->stmts,
                            $yield_types
                        )
                    );
                }
            } elseif ($stmt instanceof PhpParser\Node\Stmt\TryCatch) {
                $return_types = array_merge(
                    $return_types,
                    self::getReturnTypes(
                        $codebase,
                        $nodes,
                        $stmt->stmts,
                        $yield_types
                    )
                );

                foreach ($stmt->catches as $catch) {
                    $return_types = array_merge(
                        $return_types,
                        self::getReturnTypes(
                            $codebase,
                            $nodes,
                            $catch->stmts,
                            $yield_types
                        )
                    );
                }

                if ($stmt->finally) {
                    $return_types = array_merge(
                        $return_types,
                        self::getReturnTypes(
                            $codebase,
                            $nodes,
                            $stmt->finally->stmts,
                            $yield_types
                        )
                    );
                }
            } elseif ($stmt instanceof PhpParser\Node\Stmt\For_) {
                $return_types = array_merge(
                    $return_types,
                    self::getReturnTypes(
                        $codebase,
                        $nodes,
                        $stmt->stmts,
                        $yield_types
                    )
                );
            } elseif ($stmt instanceof PhpParser\Node\Stmt\Foreach_) {
                $return_types = array_merge(
                    $return_types,
                    self::getReturnTypes(
                        $codebase,
                        $nodes,
                        $stmt->stmts,
                        $yield_types
                    )
                );
            } elseif ($stmt instanceof PhpParser\Node\Stmt\While_) {
                $yield_types = array_merge($yield_types, self::getYieldTypeFromExpression($stmt->cond, $nodes));
                $return_types = array_merge(
                    $return_types,
                    self::getReturnTypes(
                        $codebase,
                        $nodes,
                        $stmt->stmts,
                        $yield_types
                    )
                );
            } elseif ($stmt instanceof PhpParser\Node\Stmt\Do_) {
                $return_types = array_merge(
                    $return_types,
                    self::getReturnTypes(
                        $codebase,
                        $nodes,
                        $stmt->stmts,
                        $yield_types
                    )
                );
            } elseif ($stmt instanceof PhpParser\Node\Stmt\Switch_) {
                foreach ($stmt->cases as $case) {
                    $return_types = array_merge(
                        $return_types,
                        self::getReturnTypes(
                            $codebase,
                            $nodes,
                            $case->stmts,
                            $yield_types
                        )
                    );
                }
            }
        }

        // if we're at the top level and we're not ending in a return, make sure to add possible null
        if ($collapse_types) {
            // if it's a generator, boil everything down to a single generator return type
            if ($yield_types) {
                $key_type = null;
                $value_type = null;

                $yield_type = Type::combineUnionTypeArray($yield_types, null);

                foreach ($yield_type->getAtomicTypes() as $type) {
                    if ($type instanceof Type\Atomic\TKeyedArray) {
                        $type = $type->getGenericArrayType();
                    }

                    if ($type instanceof Type\Atomic\TList) {
                        $type = new Type\Atomic\TArray([Type::getInt(), $type->type_param]);
                    }

                    if ($type instanceof Type\Atomic\TArray) {
                        [$key_type_param, $value_type_param] = $type->type_params;

                        if (!$key_type) {
                            $key_type = clone $key_type_param;
                        } else {
                            $key_type = Type::combineUnionTypes($key_type_param, $key_type);
                        }

                        if (!$value_type) {
                            $value_type = clone $value_type_param;
                        } else {
                            $value_type = Type::combineUnionTypes($value_type_param, $value_type);
                        }
                    } elseif ($type instanceof Type\Atomic\TIterable
                        || $type instanceof Type\Atomic\TNamedObject
                    ) {
                        ForeachAnalyzer::getKeyValueParamsForTraversableObject(
                            $type,
                            $codebase,
                            $key_type,
                            $value_type
                        );
                    }
                }

                $yield_types = [
                    new Type\Union([
                        new Atomic\TGenericObject(
                            'Generator',
                            [
                                $key_type ?: Type::getMixed(),
                                $value_type ?: Type::getMixed(),
                                Type::getMixed(),
                                $return_types ? Type::combineUnionTypeArray($return_types, null) : Type::getVoid()
                            ]
                        ),
                    ])
                ];
            }
        }

        return $return_types;
    }

    /**
     * @return  list<Type\Union>
     */
    protected static function getYieldTypeFromExpression(
        PhpParser\Node\Expr $stmt,
        \Psalm\Internal\Provider\NodeDataProvider $nodes
    ): array {
        if ($stmt instanceof PhpParser\Node\Expr\Yield_) {
            $key_type = null;

            if ($stmt->key && ($stmt_key_type = $nodes->getType($stmt->key))) {
                $key_type = $stmt_key_type;
            }

            if ($stmt->value
                && $value_type = $nodes->getType($stmt->value)
            ) {
                $generator_type = new Atomic\TGenericObject(
                    'Generator',
                    [
                        $key_type ? clone $key_type : Type::getInt(),
                        clone $value_type,
                        Type::getMixed(),
                        Type::getMixed()
                    ]
                );

                return [new Type\Union([$generator_type])];
            }

            return [Type::getMixed()];
        } elseif ($stmt instanceof PhpParser\Node\Expr\YieldFrom) {
            if ($stmt_expr_type = $nodes->getType($stmt->expr)) {
                return [$stmt_expr_type];
            }

            return [Type::getMixed()];
        } elseif ($stmt instanceof PhpParser\Node\Expr\BinaryOp) {
            return array_merge(
                self::getYieldTypeFromExpression($stmt->left, $nodes),
                self::getYieldTypeFromExpression($stmt->right, $nodes)
            );
        } elseif ($stmt instanceof PhpParser\Node\Expr\Assign) {
            return self::getYieldTypeFromExpression($stmt->expr, $nodes);
        } elseif ($stmt instanceof PhpParser\Node\Expr\MethodCall
            || $stmt instanceof PhpParser\Node\Expr\FuncCall
            || $stmt instanceof PhpParser\Node\Expr\StaticCall
        ) {
            $yield_types = [];

            foreach ($stmt->args as $arg) {
                $yield_types = array_merge($yield_types, self::getYieldTypeFromExpression($arg->value, $nodes));
            }

            return $yield_types;
        }

        return [];
    }
}
