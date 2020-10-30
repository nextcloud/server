<?php
namespace Psalm\Internal\Analyzer\Statements\Expression;

use PhpParser;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\Call\Method\MethodCallReturnTypeFetcher;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Issue\InvalidCast;
use Psalm\Issue\PossiblyInvalidCast;
use Psalm\Issue\UnrecognizedExpression;
use Psalm\IssueBuffer;
use Psalm\Type;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\Scalar;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TFloat;
use Psalm\Type\Atomic\TInt;
use Psalm\Type\Atomic\TList;
use Psalm\Type\Atomic\TMixed;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TNull;
use Psalm\Type\Atomic\TString;
use Psalm\Internal\Type\TypeCombination;
use function get_class;
use function count;
use function array_merge;
use function array_values;
use function current;

class CastAnalyzer
{
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\Cast $stmt,
        Context $context
    ) : bool {
        if ($stmt instanceof PhpParser\Node\Expr\Cast\Int_) {
            if (ExpressionAnalyzer::analyze($statements_analyzer, $stmt->expr, $context) === false) {
                return false;
            }

            $as_int = true;
            $maybe_type = $statements_analyzer->node_data->getType($stmt->expr);

            if ($maybe_type) {
                $maybe = $maybe_type->getAtomicTypes();

                if (count($maybe) === 1 && current($maybe) instanceof Type\Atomic\TBool) {
                    $as_int = false;
                    $statements_analyzer->node_data->setType($stmt, new Type\Union([
                        new Type\Atomic\TLiteralInt(0),
                        new Type\Atomic\TLiteralInt(1),
                    ]));
                }
            }

            if ($as_int) {
                $type = Type::getInt();

                if ($statements_analyzer->data_flow_graph
                    && $statements_analyzer->data_flow_graph instanceof \Psalm\Internal\Codebase\VariableUseGraph
                ) {
                    $type->parent_nodes = $maybe_type ? $maybe_type->parent_nodes : [];
                }

                $statements_analyzer->node_data->setType($stmt, $type);
            }

            return true;
        }

        if ($stmt instanceof PhpParser\Node\Expr\Cast\Double) {
            if (ExpressionAnalyzer::analyze($statements_analyzer, $stmt->expr, $context) === false) {
                return false;
            }

            $statements_analyzer->node_data->setType($stmt, Type::getFloat());

            return true;
        }

        if ($stmt instanceof PhpParser\Node\Expr\Cast\Bool_) {
            if (ExpressionAnalyzer::analyze($statements_analyzer, $stmt->expr, $context) === false) {
                return false;
            }

            $statements_analyzer->node_data->setType($stmt, Type::getBool());

            return true;
        }

        if ($stmt instanceof PhpParser\Node\Expr\Cast\String_) {
            if (ExpressionAnalyzer::analyze($statements_analyzer, $stmt->expr, $context) === false) {
                return false;
            }

            $stmt_expr_type = $statements_analyzer->node_data->getType($stmt->expr);

            if ($stmt_expr_type) {
                $stmt_type = self::castStringAttempt(
                    $statements_analyzer,
                    $context,
                    $stmt_expr_type,
                    $stmt->expr,
                    true
                );
            } else {
                $stmt_type = Type::getString();
            }

            $statements_analyzer->node_data->setType($stmt, $stmt_type);

            return true;
        }

        if ($stmt instanceof PhpParser\Node\Expr\Cast\Object_) {
            $was_inside_use = $context->inside_use;
            $context->inside_use = true;
            if (ExpressionAnalyzer::analyze($statements_analyzer, $stmt->expr, $context) === false) {
                return false;
            }
            $context->inside_use = $was_inside_use;

            $statements_analyzer->node_data->setType($stmt, new Type\Union([new TNamedObject('stdClass')]));

            return true;
        }

        if ($stmt instanceof PhpParser\Node\Expr\Cast\Array_) {
            $was_inside_use = $context->inside_use;
            $context->inside_use = true;
            if (ExpressionAnalyzer::analyze($statements_analyzer, $stmt->expr, $context) === false) {
                return false;
            }
            $context->inside_use = $was_inside_use;

            $permissible_atomic_types = [];
            $all_permissible = false;

            if ($stmt_expr_type = $statements_analyzer->node_data->getType($stmt->expr)) {
                $all_permissible = true;

                foreach ($stmt_expr_type->getAtomicTypes() as $type) {
                    if ($type instanceof Scalar) {
                        $permissible_atomic_types[] = new TKeyedArray([new Type\Union([$type])]);
                    } elseif ($type instanceof TNull) {
                        $permissible_atomic_types[] = new TArray([Type::getEmpty(), Type::getEmpty()]);
                    } elseif ($type instanceof TArray
                        || $type instanceof TList
                        || $type instanceof TKeyedArray
                    ) {
                        $permissible_atomic_types[] = clone $type;
                    } else {
                        $all_permissible = false;
                        break;
                    }
                }
            }

            if ($permissible_atomic_types && $all_permissible) {
                $statements_analyzer->node_data->setType(
                    $stmt,
                    TypeCombination::combineTypes($permissible_atomic_types)
                );
            } else {
                $statements_analyzer->node_data->setType($stmt, Type::getArray());
            }

            return true;
        }

        if ($stmt instanceof PhpParser\Node\Expr\Cast\Unset_
            && $statements_analyzer->getCodebase()->php_major_version < 8
        ) {
            if (ExpressionAnalyzer::analyze($statements_analyzer, $stmt->expr, $context) === false) {
                return false;
            }

            $statements_analyzer->node_data->setType($stmt, Type::getNull());

            return true;
        }

        if (IssueBuffer::accepts(
            new UnrecognizedExpression(
                'Psalm does not understand the cast ' . get_class($stmt),
                new CodeLocation($statements_analyzer->getSource(), $stmt)
            ),
            $statements_analyzer->getSuppressedIssues()
        )) {
            // fall through
        }

        return false;
    }

    public static function castStringAttempt(
        StatementsAnalyzer $statements_analyzer,
        Context $context,
        Type\Union $stmt_type,
        PhpParser\Node\Expr $stmt,
        bool $explicit_cast = false
    ) : Type\Union {
        $codebase = $statements_analyzer->getCodebase();

        $invalid_casts = [];
        $valid_strings = [];
        $castable_types = [];

        $atomic_types = $stmt_type->getAtomicTypes();

        $parent_nodes = [];

        if ($statements_analyzer->data_flow_graph) {
            $parent_nodes = $stmt_type->parent_nodes;
        }

        while ($atomic_types) {
            $atomic_type = \array_pop($atomic_types);

            if ($atomic_type instanceof TFloat
                || $atomic_type instanceof TInt
                || $atomic_type instanceof Type\Atomic\TNumeric
            ) {
                $castable_types[] = new Type\Atomic\TNumericString();
                continue;
            }

            if ($atomic_type instanceof TString) {
                $valid_strings[] = $atomic_type;

                continue;
            }

            if ($atomic_type instanceof TNull
                || $atomic_type instanceof Type\Atomic\TFalse
            ) {
                $valid_strings[] = new Type\Atomic\TLiteralString('');
                continue;
            }

            if ($atomic_type instanceof TMixed
                || $atomic_type instanceof Type\Atomic\TResource
                || $atomic_type instanceof Type\Atomic\Scalar
            ) {
                $castable_types[] = new TString();

                continue;
            }

            if ($atomic_type instanceof TNamedObject
                || $atomic_type instanceof Type\Atomic\TObjectWithProperties
            ) {
                $intersection_types = [$atomic_type];

                if ($atomic_type->extra_types) {
                    $intersection_types = array_merge($intersection_types, $atomic_type->extra_types);
                }

                foreach ($intersection_types as $intersection_type) {
                    if ($intersection_type instanceof TNamedObject) {
                        $intersection_method_id = new \Psalm\Internal\MethodIdentifier(
                            $intersection_type->value,
                            '__tostring'
                        );

                        if ($codebase->methods->methodExists(
                            $intersection_method_id,
                            $context->calling_method_id,
                            new CodeLocation($statements_analyzer->getSource(), $stmt)
                        )) {
                            $return_type = $codebase->methods->getMethodReturnType(
                                $intersection_method_id,
                                $self_class
                            ) ?: Type::getString();

                            $declaring_method_id = $codebase->methods->getDeclaringMethodId($intersection_method_id);

                            MethodCallReturnTypeFetcher::taintMethodCallResult(
                                $statements_analyzer,
                                $return_type,
                                $stmt,
                                $stmt,
                                $intersection_method_id,
                                $declaring_method_id,
                                $intersection_type->value . '::__toString',
                                $context
                            );

                            if ($statements_analyzer->data_flow_graph) {
                                $parent_nodes = array_merge($return_type->parent_nodes, $parent_nodes);
                            }

                            $castable_types = array_merge(
                                $castable_types,
                                array_values($return_type->getAtomicTypes())
                            );

                            continue 2;
                        }
                    }

                    if ($intersection_type instanceof Type\Atomic\TObjectWithProperties
                        && isset($intersection_type->methods['__toString'])
                    ) {
                        $castable_types[] = new TString();

                        continue 2;
                    }
                }
            }

            if ($atomic_type instanceof Type\Atomic\TTemplateParam) {
                $atomic_types = array_merge($atomic_types, $atomic_type->as->getAtomicTypes());

                continue;
            }

            $invalid_casts[] = $atomic_type->getId();
        }

        if ($invalid_casts) {
            if ($valid_strings || $castable_types) {
                if (IssueBuffer::accepts(
                    new PossiblyInvalidCast(
                        $invalid_casts[0] . ' cannot be cast to string',
                        new CodeLocation($statements_analyzer->getSource(), $stmt)
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                    // fall through
                }
            } else {
                if (IssueBuffer::accepts(
                    new InvalidCast(
                        $invalid_casts[0] . ' cannot be cast to string',
                        new CodeLocation($statements_analyzer->getSource(), $stmt)
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                    // fall through
                }
            }
        } elseif ($explicit_cast && !$castable_types) {
            // todo: emit error here
        }

        $valid_types = array_merge($valid_strings, $castable_types);

        if (!$valid_types) {
            $str_type = Type::getString();
        } else {
            $str_type = \Psalm\Internal\Type\TypeCombination::combineTypes(
                $valid_types,
                $codebase
            );
        }

        if ($statements_analyzer->data_flow_graph) {
            $str_type->parent_nodes = $parent_nodes;
        }

        return $str_type;
    }
}
