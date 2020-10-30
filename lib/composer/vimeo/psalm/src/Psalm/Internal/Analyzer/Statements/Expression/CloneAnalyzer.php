<?php
namespace Psalm\Internal\Analyzer\Statements\Expression;

use PhpParser;
use Psalm\Internal\Analyzer\MethodAnalyzer;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Issue\InvalidClone;
use Psalm\Issue\MixedClone;
use Psalm\Issue\PossiblyInvalidClone;
use Psalm\IssueBuffer;
use Psalm\Type;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Atomic\TMixed;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TObject;

class CloneAnalyzer
{
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\Clone_ $stmt,
        Context $context
    ) : bool {
        $codebase = $statements_analyzer->getCodebase();
        $codebase_methods = $codebase->methods;
        if (ExpressionAnalyzer::analyze($statements_analyzer, $stmt->expr, $context) === false) {
            return false;
        }

        $stmt_expr_type = $statements_analyzer->node_data->getType($stmt->expr);

        if ($stmt_expr_type) {
            $clone_type = $stmt_expr_type;

            $immutable_cloned = false;

            $invalid_clones = [];
            $mixed_clone = false;

            $possibly_valid = false;
            $atomic_types = $clone_type->getAtomicTypes();

            while ($atomic_types) {
                $clone_type_part = \array_pop($atomic_types);

                if ($clone_type_part instanceof TMixed) {
                    $mixed_clone = true;
                } elseif ($clone_type_part instanceof TObject) {
                    $possibly_valid = true;
                } elseif ($clone_type_part instanceof TNamedObject) {
                    if (!$codebase->classlikes->classOrInterfaceExists($clone_type_part->value)) {
                        $invalid_clones[] = $clone_type_part->getId();
                    } else {
                        $clone_method_id = new \Psalm\Internal\MethodIdentifier(
                            $clone_type_part->value,
                            '__clone'
                        );

                        $does_method_exist = $codebase_methods->methodExists(
                            $clone_method_id,
                            $context->calling_method_id,
                            new CodeLocation($statements_analyzer->getSource(), $stmt)
                        );
                        $is_method_visible = MethodAnalyzer::isMethodVisible(
                            $clone_method_id,
                            $context,
                            $statements_analyzer->getSource()
                        );
                        if ($does_method_exist && !$is_method_visible) {
                            $invalid_clones[] = $clone_type_part->getId();
                        } else {
                            $possibly_valid = true;
                            $immutable_cloned = true;
                        }
                    }
                } elseif ($clone_type_part instanceof TTemplateParam) {
                    $atomic_types = \array_merge($atomic_types, $clone_type_part->as->getAtomicTypes());
                } else {
                    if ($clone_type_part instanceof Type\Atomic\TFalse
                        && $clone_type->ignore_falsable_issues
                    ) {
                        continue;
                    }

                    if ($clone_type_part instanceof Type\Atomic\TNull
                        && $clone_type->ignore_nullable_issues
                    ) {
                        continue;
                    }

                    $invalid_clones[] = $clone_type_part->getId();
                }
            }

            if ($mixed_clone) {
                if (IssueBuffer::accepts(
                    new MixedClone(
                        'Cannot clone mixed',
                        new CodeLocation($statements_analyzer->getSource(), $stmt)
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                    // fall through
                }
            }

            if ($invalid_clones) {
                if ($possibly_valid) {
                    if (IssueBuffer::accepts(
                        new PossiblyInvalidClone(
                            'Cannot clone ' . $invalid_clones[0],
                            new CodeLocation($statements_analyzer->getSource(), $stmt)
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                } else {
                    if (IssueBuffer::accepts(
                        new InvalidClone(
                            'Cannot clone ' . $invalid_clones[0],
                            new CodeLocation($statements_analyzer->getSource(), $stmt)
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                }

                return true;
            }

            $statements_analyzer->node_data->setType($stmt, $stmt_expr_type);

            if ($immutable_cloned) {
                $stmt_expr_type = clone $stmt_expr_type;
                $statements_analyzer->node_data->setType($stmt, $stmt_expr_type);
                $stmt_expr_type->reference_free = true;
                $stmt_expr_type->allow_mutations = true;
            }
        }

        return true;
    }
}
