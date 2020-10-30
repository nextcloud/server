<?php
namespace Psalm\Internal\Analyzer\Statements;

use PhpParser;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Type\Comparator\UnionTypeComparator;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Issue\InvalidThrow;
use Psalm\IssueBuffer;
use Psalm\Type;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Union;

/**
 * @internal
 */
class ThrowAnalyzer
{
    /**
     * @param PhpParser\Node\Stmt\Throw_|PhpParser\Node\Expr\Throw_ $stmt
     */
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node $stmt,
        Context $context
    ) : bool {
        $context->inside_throw = true;
        if (ExpressionAnalyzer::analyze($statements_analyzer, $stmt->expr, $context) === false) {
            return false;
        }
        $context->inside_throw = false;

        if ($context->finally_scope) {
            foreach ($context->vars_in_scope as $var_id => $type) {
                if (isset($context->finally_scope->vars_in_scope[$var_id])) {
                    if ($context->finally_scope->vars_in_scope[$var_id] !== $type) {
                        $context->finally_scope->vars_in_scope[$var_id] = Type::combineUnionTypes(
                            $context->finally_scope->vars_in_scope[$var_id],
                            $type,
                            $statements_analyzer->getCodebase()
                        );
                    }
                } else {
                    $context->finally_scope->vars_in_scope[$var_id] = $type;
                }
            }
        }

        if ($context->check_classes
            && ($throw_type = $statements_analyzer->node_data->getType($stmt->expr))
            && !$throw_type->hasMixed()
        ) {
            $exception_type = new Union([new TNamedObject('Exception'), new TNamedObject('Throwable')]);

            $file_analyzer = $statements_analyzer->getFileAnalyzer();
            $codebase = $statements_analyzer->getCodebase();

            foreach ($throw_type->getAtomicTypes() as $throw_type_part) {
                $throw_type_candidate = new Union([$throw_type_part]);

                if (!UnionTypeComparator::isContainedBy($codebase, $throw_type_candidate, $exception_type)) {
                    if (IssueBuffer::accepts(
                        new InvalidThrow(
                            'Cannot throw ' . $throw_type_part
                                . ' as it does not extend Exception or implement Throwable',
                            new CodeLocation($file_analyzer, $stmt),
                            (string) $throw_type_part
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        return false;
                    }
                } elseif (!$context->isSuppressingExceptions($statements_analyzer)) {
                    $codelocation = new CodeLocation($file_analyzer, $stmt);
                    $hash = $codelocation->getHash();
                    foreach ($throw_type->getAtomicTypes() as $throw_atomic_type) {
                        if ($throw_atomic_type instanceof TNamedObject) {
                            $context->possibly_thrown_exceptions[$throw_atomic_type->value][$hash] = $codelocation;
                        }
                    }
                }
            }
        }

        if ($stmt instanceof PhpParser\Node\Expr\Throw_) {
            $statements_analyzer->node_data->setType($stmt, \Psalm\Type::getEmpty());
        }

        return true;
    }
}
