<?php
namespace Psalm\Internal\Provider\ReturnTypeProvider;

use function array_map;
use function count;
use function is_string;
use PhpParser;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\AssertionFinder;
use Psalm\Internal\Analyzer\Statements\Expression\ExpressionIdentifier;
use Psalm\Issue\InvalidReturnType;
use Psalm\IssueBuffer;
use Psalm\StatementsSource;
use Psalm\Type;
use Psalm\Type\Reconciler;
use Psalm\Internal\Analyzer\Statements\Expression\CallAnalyzer;

class ArrayFilterReturnTypeProvider implements \Psalm\Plugin\Hook\FunctionReturnTypeProviderInterface
{
    public static function getFunctionIds() : array
    {
        return ['array_filter'];
    }

    /**
     * @param  array<PhpParser\Node\Arg>    $call_args
     */
    public static function getFunctionReturnType(
        StatementsSource $statements_source,
        string $function_id,
        array $call_args,
        Context $context,
        CodeLocation $code_location
    ) : Type\Union {
        if (!$statements_source instanceof StatementsAnalyzer) {
            return Type::getMixed();
        }

        $array_arg = isset($call_args[0]->value) ? $call_args[0]->value : null;

        $first_arg_array = $array_arg
            && ($first_arg_type = $statements_source->node_data->getType($array_arg))
            && $first_arg_type->hasType('array')
            && ($array_atomic_type = $first_arg_type->getAtomicTypes()['array'])
            && ($array_atomic_type instanceof Type\Atomic\TArray
                || $array_atomic_type instanceof Type\Atomic\TKeyedArray
                || $array_atomic_type instanceof Type\Atomic\TList)
            ? $array_atomic_type
            : null;

        if (!$first_arg_array) {
            return Type::getArray();
        }

        if ($first_arg_array instanceof Type\Atomic\TArray) {
            $inner_type = $first_arg_array->type_params[1];
            $key_type = clone $first_arg_array->type_params[0];
        } elseif ($first_arg_array instanceof Type\Atomic\TList) {
            $inner_type = $first_arg_array->type_param;
            $key_type = Type::getInt();
        } else {
            $inner_type = $first_arg_array->getGenericValueType();
            $key_type = $first_arg_array->getGenericKeyType();

            if (!isset($call_args[1]) && !$first_arg_array->previous_value_type) {
                $had_one = count($first_arg_array->properties) === 1;

                $first_arg_array = clone $first_arg_array;

                $new_properties = \array_filter(
                    array_map(
                        function ($keyed_type) use ($statements_source, $context) {
                            $prev_keyed_type = $keyed_type;

                            $keyed_type = \Psalm\Internal\Type\AssertionReconciler::reconcile(
                                '!falsy',
                                clone $keyed_type,
                                '',
                                $statements_source,
                                $context->inside_loop,
                                [],
                                null,
                                $statements_source->getSuppressedIssues()
                            );

                            $keyed_type->possibly_undefined = ($prev_keyed_type->hasInt()
                                    && !$prev_keyed_type->hasLiteralInt())
                                || $prev_keyed_type->hasFloat()
                                || $prev_keyed_type->getId() !== $keyed_type->getId();

                            return $keyed_type;
                        },
                        $first_arg_array->properties
                    ),
                    function ($keyed_type) {
                        return !$keyed_type->isEmpty();
                    }
                );

                if (!$new_properties) {
                    return Type::getEmptyArray();
                }

                $first_arg_array->properties = $new_properties;

                $first_arg_array->is_list = $first_arg_array->is_list && $had_one;
                $first_arg_array->sealed = false;

                return new Type\Union([$first_arg_array]);
            }
        }

        if (!isset($call_args[1])) {
            $inner_type = \Psalm\Internal\Type\AssertionReconciler::reconcile(
                '!falsy',
                clone $inner_type,
                '',
                $statements_source,
                $context->inside_loop,
                [],
                null,
                $statements_source->getSuppressedIssues()
            );

            if ($first_arg_array instanceof Type\Atomic\TKeyedArray
                && $first_arg_array->is_list
                && $key_type->isSingleIntLiteral()
                && $key_type->getSingleIntLiteral()->value === 0
            ) {
                return new Type\Union([
                    new Type\Atomic\TList(
                        $inner_type
                    ),
                ]);
            }

            if ($key_type->getLiteralStrings()) {
                $key_type->addType(new Type\Atomic\TString);
            }

            if ($key_type->getLiteralInts()) {
                $key_type->addType(new Type\Atomic\TInt);
            }

            if (!$inner_type->getAtomicTypes()) {
                return Type::getEmptyArray();
            }

            return new Type\Union([
                new Type\Atomic\TArray([
                    $key_type,
                    $inner_type,
                ]),
            ]);
        }

        if (!isset($call_args[2])) {
            $function_call_arg = $call_args[1];

            if ($function_call_arg->value instanceof PhpParser\Node\Scalar\String_
                || $function_call_arg->value instanceof PhpParser\Node\Expr\Array_
                || $function_call_arg->value instanceof PhpParser\Node\Expr\BinaryOp\Concat
            ) {
                $mapping_function_ids = CallAnalyzer::getFunctionIdsFromCallableArg(
                    $statements_source,
                    $function_call_arg->value
                );

                if ($array_arg && $mapping_function_ids) {
                    $assertions = [];

                    ArrayMapReturnTypeProvider::getReturnTypeFromMappingIds(
                        $statements_source,
                        $mapping_function_ids,
                        $context,
                        $function_call_arg,
                        \array_slice($call_args, 0, 1),
                        $assertions
                    );

                    $array_var_id = ExpressionIdentifier::getArrayVarId(
                        $array_arg,
                        null,
                        $statements_source
                    );

                    if (isset($assertions[$array_var_id . '[$__fake_offset_var__]'])) {
                        $changed_var_ids = [];

                        $assertions = ['$inner_type' => $assertions[$array_var_id . '[$__fake_offset_var__]']];

                        $reconciled_types = Reconciler::reconcileKeyedTypes(
                            $assertions,
                            $assertions,
                            ['$inner_type' => $inner_type],
                            $changed_var_ids,
                            ['$inner_type' => true],
                            $statements_source,
                            $statements_source->getTemplateTypeMap() ?: [],
                            false,
                            new CodeLocation($statements_source, $function_call_arg->value)
                        );

                        if (isset($reconciled_types['$inner_type'])) {
                            $inner_type = $reconciled_types['$inner_type'];
                        }
                    }
                }
            } elseif (($function_call_arg->value instanceof PhpParser\Node\Expr\Closure
                    || $function_call_arg->value instanceof PhpParser\Node\Expr\ArrowFunction)
                && ($second_arg_type = $statements_source->node_data->getType($function_call_arg->value))
                && ($closure_types = $second_arg_type->getClosureTypes())
            ) {
                $closure_atomic_type = \reset($closure_types);
                $closure_return_type = $closure_atomic_type->return_type ?: Type::getMixed();

                if ($closure_return_type->isVoid()) {
                    IssueBuffer::accepts(
                        new InvalidReturnType(
                            'No return type could be found in the closure passed to array_filter',
                            $code_location
                        ),
                        $statements_source->getSuppressedIssues()
                    );

                    return Type::getArray();
                }

                if (count($function_call_arg->value->getStmts()) === 1 && count($function_call_arg->value->params)) {
                    $first_param = $function_call_arg->value->params[0];
                    $stmt = $function_call_arg->value->getStmts()[0];

                    if ($first_param->variadic === false
                        && $first_param->var instanceof PhpParser\Node\Expr\Variable
                        && is_string($first_param->var->name)
                        && $stmt instanceof PhpParser\Node\Stmt\Return_
                        && $stmt->expr
                    ) {
                        $codebase = $statements_source->getCodebase();

                        $assertions = AssertionFinder::scrapeAssertions(
                            $stmt->expr,
                            null,
                            $statements_source,
                            $codebase
                        );

                        if (isset($assertions['$' . $first_param->var->name])) {
                            $changed_var_ids = [];

                            $assertions = ['$inner_type' => $assertions['$' . $first_param->var->name]];

                            $reconciled_types = Reconciler::reconcileKeyedTypes(
                                $assertions,
                                $assertions,
                                ['$inner_type' => $inner_type],
                                $changed_var_ids,
                                ['$inner_type' => true],
                                $statements_source,
                                $statements_source->getTemplateTypeMap() ?: [],
                                false,
                                new CodeLocation($statements_source, $stmt)
                            );

                            if (isset($reconciled_types['$inner_type'])) {
                                $inner_type = $reconciled_types['$inner_type'];
                            }
                        }
                    }
                }
            }

            return new Type\Union([
                new Type\Atomic\TArray([
                    $key_type,
                    $inner_type,
                ]),
            ]);
        }

        if (!$inner_type->getAtomicTypes()) {
            return Type::getEmptyArray();
        }

        return new Type\Union([
            new Type\Atomic\TArray([
                $key_type,
                $inner_type,
            ]),
        ]);
    }
}
