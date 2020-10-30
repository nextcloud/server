<?php
namespace Psalm\Internal\Analyzer;

use PhpParser;
use function count;
use function in_array;
use function end;
use function strtolower;
use function array_merge;
use function array_intersect;
use function array_unique;
use function array_filter;

/**
 * @internal
 */
class ScopeAnalyzer
{
    public const ACTION_END = 'END';
    public const ACTION_BREAK = 'BREAK';
    public const ACTION_CONTINUE = 'CONTINUE';
    public const ACTION_LEAVE_SWITCH = 'LEAVE_SWITCH';
    public const ACTION_NONE = 'NONE';
    public const ACTION_RETURN = 'RETURN';

    private const ACTIONS = [
        self::ACTION_END,
        self::ACTION_BREAK,
        self::ACTION_CONTINUE,
        self::ACTION_LEAVE_SWITCH,
        self::ACTION_NONE,
        self::ACTION_RETURN
    ];

    /**
     * @param   array<PhpParser\Node\Stmt>   $stmts
     *
     */
    public static function doesEverBreak(array $stmts): bool
    {
        if (empty($stmts)) {
            return false;
        }

        for ($i = count($stmts) - 1; $i >= 0; --$i) {
            $stmt = $stmts[$i];

            if ($stmt instanceof PhpParser\Node\Stmt\Break_) {
                return true;
            }

            if ($stmt instanceof PhpParser\Node\Stmt\If_) {
                if (self::doesEverBreak($stmt->stmts)) {
                    return true;
                }

                if ($stmt->else && self::doesEverBreak($stmt->else->stmts)) {
                    return true;
                }

                foreach ($stmt->elseifs as $elseif) {
                    if (self::doesEverBreak($elseif->stmts)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * @param   array<PhpParser\Node> $stmts
     * @param   bool $return_is_exit Exit and Throw statements are treated differently from return if this is false
     * @param   list<'loop'|'switch'> $break_types
     *
     * @return  list<value-of<self::ACTIONS>>
     */
    public static function getControlActions(
        array $stmts,
        ?\Psalm\Internal\Provider\NodeDataProvider $nodes,
        array $exit_functions,
        array $break_types = [],
        bool $return_is_exit = true
    ): array {
        if (empty($stmts)) {
            return [self::ACTION_NONE];
        }

        $control_actions = [];

        for ($i = 0, $c = count($stmts); $i < $c; ++$i) {
            $stmt = $stmts[$i];

            if ($stmt instanceof PhpParser\Node\Stmt\Return_ ||
                $stmt instanceof PhpParser\Node\Stmt\Throw_ ||
                ($stmt instanceof PhpParser\Node\Stmt\Expression && $stmt->expr instanceof PhpParser\Node\Expr\Exit_)
            ) {
                if (!$return_is_exit && $stmt instanceof PhpParser\Node\Stmt\Return_) {
                    return array_merge($control_actions, [self::ACTION_RETURN]);
                }

                return [self::ACTION_END];
            }

            if ($stmt instanceof PhpParser\Node\Stmt\Expression) {
                if ($stmt->expr instanceof PhpParser\Node\Expr\FuncCall
                    && $stmt->expr->name instanceof PhpParser\Node\Name
                    && $stmt->expr->name->parts === ['trigger_error']
                    && isset($stmt->expr->args[1])
                    && $stmt->expr->args[1]->value instanceof PhpParser\Node\Expr\ConstFetch
                    && in_array(
                        end($stmt->expr->args[1]->value->name->parts),
                        ['E_ERROR', 'E_PARSE', 'E_CORE_ERROR', 'E_COMPILE_ERROR', 'E_USER_ERROR']
                    )
                ) {
                    return [self::ACTION_END];
                }

                // This allows calls to functions that always exit to act as exit statements themselves
                if ($nodes
                    && ($stmt_expr_type = $nodes->getType($stmt->expr))
                    && $stmt_expr_type->isNever()
                ) {
                    return [self::ACTION_END];
                }

                if ($exit_functions) {
                    if ($stmt->expr instanceof PhpParser\Node\Expr\FuncCall
                        || $stmt->expr instanceof PhpParser\Node\Expr\StaticCall
                    ) {
                        if ($stmt->expr instanceof PhpParser\Node\Expr\FuncCall) {
                            /** @var string|null */
                            $resolved_name = $stmt->expr->name->getAttribute('resolvedName');

                            if ($resolved_name && isset($exit_functions[strtolower($resolved_name)])) {
                                return [self::ACTION_END];
                            }
                        } elseif ($stmt->expr->class instanceof PhpParser\Node\Name
                            && $stmt->expr->name instanceof PhpParser\Node\Identifier
                        ) {
                            /** @var string|null */
                            $resolved_class_name = $stmt->expr->class->getAttribute('resolvedName');

                            if ($resolved_class_name
                                && isset($exit_functions[strtolower($resolved_class_name . '::' . $stmt->expr->name)])
                            ) {
                                return [self::ACTION_END];
                            }
                        }
                    }
                }

                continue;
            }

            if ($stmt instanceof PhpParser\Node\Stmt\Continue_) {
                if ($break_types
                    && end($break_types) === 'switch'
                    && (!$stmt->num || !$stmt->num instanceof PhpParser\Node\Scalar\LNumber || $stmt->num->value < 2)
                ) {
                    return array_merge($control_actions, [self::ACTION_LEAVE_SWITCH]);
                }

                return \array_values(array_unique(array_merge($control_actions, [self::ACTION_CONTINUE])));
            }

            if ($stmt instanceof PhpParser\Node\Stmt\Break_) {
                if ($break_types
                    && end($break_types) === 'switch'
                    && (!$stmt->num || !$stmt->num instanceof PhpParser\Node\Scalar\LNumber || $stmt->num->value < 2)
                ) {
                    return [self::ACTION_LEAVE_SWITCH];
                }

                return \array_values(array_unique(array_merge($control_actions, [self::ACTION_BREAK])));
            }

            if ($stmt instanceof PhpParser\Node\Stmt\If_) {
                $if_statement_actions = self::getControlActions(
                    $stmt->stmts,
                    $nodes,
                    $exit_functions,
                    $break_types
                );

                $else_statement_actions = $stmt->else
                    ? self::getControlActions($stmt->else->stmts, $nodes, $exit_functions, $break_types)
                    : [];

                $all_same = count($if_statement_actions) === 1
                    && $if_statement_actions == $else_statement_actions
                    && $if_statement_actions !== [self::ACTION_NONE];

                $all_elseif_actions = [];

                if ($stmt->elseifs) {
                    foreach ($stmt->elseifs as $elseif) {
                        $elseif_control_actions = self::getControlActions(
                            $elseif->stmts,
                            $nodes,
                            $exit_functions,
                            $break_types
                        );

                        $all_same = $all_same && $elseif_control_actions == $if_statement_actions;

                        if (!$all_same) {
                            $all_elseif_actions = array_merge($elseif_control_actions, $all_elseif_actions);
                        }
                    }
                }

                if ($all_same) {
                    return $if_statement_actions;
                }

                $control_actions = array_filter(
                    array_merge(
                        $control_actions,
                        $if_statement_actions,
                        $else_statement_actions,
                        $all_elseif_actions
                    ),
                    function ($action) {
                        return $action !== self::ACTION_NONE;
                    }
                );
            }

            if ($stmt instanceof PhpParser\Node\Stmt\Switch_) {
                $has_ended = false;
                $has_non_breaking_default = false;
                $has_default_terminator = false;

                // iterate backwards in a case statement
                for ($d = count($stmt->cases) - 1; $d >= 0; --$d) {
                    $case = $stmt->cases[$d];

                    $case_actions = self::getControlActions($case->stmts, $nodes, $exit_functions, ['switch']);

                    if (array_intersect([
                        self::ACTION_LEAVE_SWITCH,
                        self::ACTION_BREAK,
                        self::ACTION_CONTINUE
                    ], $case_actions)
                    ) {
                        continue 2;
                    }

                    if (!$case->cond) {
                        $has_non_breaking_default = true;
                    }

                    $case_does_end = $case_actions == [self::ACTION_END];

                    if ($case_does_end) {
                        $has_ended = true;
                    }

                    if (!$case_does_end && !$has_ended) {
                        continue 2;
                    }

                    if ($has_non_breaking_default && $case_does_end) {
                        $has_default_terminator = true;
                    }
                }

                if ($has_default_terminator || isset($stmt->allMatched)) {
                    return \array_values(array_unique(array_merge($control_actions, [self::ACTION_END])));
                }
            }

            if ($stmt instanceof PhpParser\Node\Stmt\Do_
                || $stmt instanceof PhpParser\Node\Stmt\While_
                || $stmt instanceof PhpParser\Node\Stmt\Foreach_
                || $stmt instanceof PhpParser\Node\Stmt\For_
            ) {
                $do_actions = self::getControlActions(
                    $stmt->stmts,
                    $nodes,
                    $exit_functions,
                    array_merge($break_types, ['loop'])
                );

                $control_actions = array_filter(
                    array_merge($control_actions, $do_actions),
                    function ($action) use ($break_types) {
                        return $action !== self::ACTION_NONE
                            && ($break_types
                                || ($action !== self::ACTION_CONTINUE
                                    && $action !== self::ACTION_BREAK));
                    }
                );
            }

            if ($stmt instanceof PhpParser\Node\Stmt\TryCatch) {
                $try_statement_actions = self::getControlActions(
                    $stmt->stmts,
                    $nodes,
                    $exit_functions,
                    $break_types
                );

                if ($stmt->catches) {
                    $all_same = count($try_statement_actions) === 1;

                    foreach ($stmt->catches as $catch) {
                        $catch_actions = self::getControlActions(
                            $catch->stmts,
                            $nodes,
                            $exit_functions,
                            $break_types
                        );

                        $all_same = $all_same && $try_statement_actions == $catch_actions;

                        if (!$all_same) {
                            $control_actions = array_merge($control_actions, $catch_actions);
                        }
                    }

                    if ($all_same && $try_statement_actions !== [self::ACTION_NONE]) {
                        return \array_values(array_unique(array_merge($control_actions, $try_statement_actions)));
                    }
                } elseif (!in_array(self::ACTION_NONE, $try_statement_actions, true)) {
                    return \array_values(array_unique(array_merge($control_actions, $try_statement_actions)));
                }

                if ($stmt->finally) {
                    if ($stmt->finally->stmts) {
                        $finally_statement_actions = self::getControlActions(
                            $stmt->finally->stmts,
                            $nodes,
                            $exit_functions,
                            $break_types
                        );

                        if (!in_array(self::ACTION_NONE, $finally_statement_actions, true)) {
                            return array_merge(
                                array_filter(
                                    $control_actions,
                                    function ($action) {
                                        return $action !== self::ACTION_NONE;
                                    }
                                ),
                                $finally_statement_actions
                            );
                        }
                    }

                    if (!$stmt->catches && !in_array(self::ACTION_NONE, $try_statement_actions, true)) {
                        return array_merge(
                            array_filter(
                                $control_actions,
                                function ($action) {
                                    return $action !== self::ACTION_NONE;
                                }
                            ),
                            $try_statement_actions
                        );
                    }
                }

                $control_actions = array_filter(
                    \array_merge($control_actions, $try_statement_actions),
                    function ($action) {
                        return $action !== self::ACTION_NONE;
                    }
                );
            }
        }

        $control_actions[] = self::ACTION_NONE;

        return \array_values(array_unique($control_actions));
    }

    /**
     * @param   array<PhpParser\Node> $stmts
     *
     */
    public static function onlyThrowsOrExits(\Psalm\NodeTypeProvider $type_provider, array $stmts): bool
    {
        if (empty($stmts)) {
            return false;
        }

        for ($i = count($stmts) - 1; $i >= 0; --$i) {
            $stmt = $stmts[$i];

            if ($stmt instanceof PhpParser\Node\Stmt\Throw_
                || ($stmt instanceof PhpParser\Node\Stmt\Expression
                    && $stmt->expr instanceof PhpParser\Node\Expr\Exit_)
            ) {
                return true;
            }

            if ($stmt instanceof PhpParser\Node\Stmt\Expression) {
                $stmt_type = $type_provider->getType($stmt->expr);

                if ($stmt_type && $stmt_type->isNever()) {
                    return true;
                }
            }
        }

        return false;
    }
}
