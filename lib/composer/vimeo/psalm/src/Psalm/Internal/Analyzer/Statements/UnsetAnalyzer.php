<?php
namespace Psalm\Internal\Analyzer\Statements;

use PhpParser;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\ExpressionIdentifier;
use Psalm\Context;
use Psalm\Type;

class UnsetAnalyzer
{
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Stmt\Unset_ $stmt,
        Context $context
    ): void {
        $context->inside_unset = true;

        foreach ($stmt->vars as $var) {
            $was_inside_use = $context->inside_use;
            $context->inside_use = true;

            ExpressionAnalyzer::analyze($statements_analyzer, $var, $context);

            $context->inside_use = $was_inside_use;

            $var_id = ExpressionIdentifier::getArrayVarId(
                $var,
                $statements_analyzer->getFQCLN(),
                $statements_analyzer
            );

            if ($var_id) {
                $context->remove($var_id);
            }

            if ($var instanceof PhpParser\Node\Expr\ArrayDimFetch && $var->dim) {
                $root_var_id = ExpressionIdentifier::getArrayVarId(
                    $var->var,
                    $statements_analyzer->getFQCLN(),
                    $statements_analyzer
                );

                if ($root_var_id && isset($context->vars_in_scope[$root_var_id])) {
                    $root_type = clone $context->vars_in_scope[$root_var_id];

                    foreach ($root_type->getAtomicTypes() as $atomic_root_type) {
                        if ($atomic_root_type instanceof Type\Atomic\TKeyedArray) {
                            if ($var->dim instanceof PhpParser\Node\Scalar\String_
                                || $var->dim instanceof PhpParser\Node\Scalar\LNumber
                            ) {
                                if (isset($atomic_root_type->properties[$var->dim->value])) {
                                    unset($atomic_root_type->properties[$var->dim->value]);
                                }

                                if (!$atomic_root_type->properties) {
                                    if ($atomic_root_type->previous_value_type) {
                                        $root_type->addType(
                                            new Type\Atomic\TArray([
                                                $atomic_root_type->previous_key_type
                                                    ? clone $atomic_root_type->previous_key_type
                                                    : new Type\Union([new Type\Atomic\TArrayKey]),
                                                clone $atomic_root_type->previous_value_type,
                                            ])
                                        );
                                    } else {
                                        $root_type->addType(
                                            new Type\Atomic\TArray([
                                                new Type\Union([new Type\Atomic\TEmpty]),
                                                new Type\Union([new Type\Atomic\TEmpty]),
                                            ])
                                        );
                                    }
                                }
                            } else {
                                foreach ($atomic_root_type->properties as $key => $type) {
                                    $atomic_root_type->properties[$key] = clone $type;
                                    $atomic_root_type->properties[$key]->possibly_undefined = true;
                                }

                                $atomic_root_type->sealed = false;

                                $root_type->addType(
                                    $atomic_root_type->getGenericArrayType()
                                );
                            }
                        } elseif ($atomic_root_type instanceof Type\Atomic\TNonEmptyArray) {
                            $root_type->addType(
                                new Type\Atomic\TArray($atomic_root_type->type_params)
                            );
                        } elseif ($atomic_root_type instanceof Type\Atomic\TNonEmptyMixed) {
                            $root_type->addType(
                                new Type\Atomic\TMixed()
                            );
                        } elseif ($atomic_root_type instanceof Type\Atomic\TList) {
                            $root_type->addType(
                                new Type\Atomic\TArray([
                                    Type::getInt(),
                                    $atomic_root_type->type_param
                                ])
                            );
                        }
                    }

                    $context->vars_in_scope[$root_var_id] = $root_type;

                    $context->removeVarFromConflictingClauses(
                        $root_var_id,
                        $context->vars_in_scope[$root_var_id],
                        $statements_analyzer
                    );
                }
            }
        }

        $context->inside_unset = false;
    }
}
