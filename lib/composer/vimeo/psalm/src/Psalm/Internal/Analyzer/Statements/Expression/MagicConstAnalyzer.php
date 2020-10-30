<?php
namespace Psalm\Internal\Analyzer\Statements\Expression;

use PhpParser;
use Psalm\Internal\Analyzer\FunctionLikeAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Issue\UndefinedConstant;
use Psalm\IssueBuffer;
use Psalm\Type;

class MagicConstAnalyzer
{
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Scalar\MagicConst $stmt,
        Context $context
    ) : void {
        if ($stmt instanceof PhpParser\Node\Scalar\MagicConst\Line) {
            $statements_analyzer->node_data->setType($stmt, Type::getInt());
        } elseif ($stmt instanceof PhpParser\Node\Scalar\MagicConst\Class_) {
            $codebase = $statements_analyzer->getCodebase();

            if (!$context->self) {
                if (IssueBuffer::accepts(
                    new UndefinedConstant(
                        'Cannot get __class__ outside a class',
                        new CodeLocation($statements_analyzer->getSource(), $stmt)
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                    // fall through
                }

                $statements_analyzer->node_data->setType($stmt, Type::getClassString());
            } else {
                if ($codebase->alter_code) {
                    $codebase->classlikes->handleClassLikeReferenceInMigration(
                        $codebase,
                        $statements_analyzer,
                        $stmt,
                        $context->self,
                        $context->calling_method_id
                    );
                }

                $statements_analyzer->node_data->setType($stmt, Type::getLiteralClassString($context->self));
            }
        } elseif ($stmt instanceof PhpParser\Node\Scalar\MagicConst\Namespace_) {
            $namespace = $statements_analyzer->getNamespace();
            if ($namespace === null
                && IssueBuffer::accepts(
                    new UndefinedConstant(
                        'Cannot get __namespace__ outside a namespace',
                        new CodeLocation($statements_analyzer->getSource(), $stmt)
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )
            ) {
                // fall through
            }

            $statements_analyzer->node_data->setType($stmt, Type::getString($namespace));
        } elseif ($stmt instanceof PhpParser\Node\Scalar\MagicConst\Method
            || $stmt instanceof PhpParser\Node\Scalar\MagicConst\Function_
        ) {
            $source = $statements_analyzer->getSource();
            if ($source instanceof FunctionLikeAnalyzer) {
                $statements_analyzer->node_data->setType($stmt, Type::getString($source->getId()));
            } else {
                $statements_analyzer->node_data->setType($stmt, new Type\Union([new Type\Atomic\TCallableString]));
            }
        } elseif ($stmt instanceof PhpParser\Node\Scalar\MagicConst\File
            || $stmt instanceof PhpParser\Node\Scalar\MagicConst\Dir
        ) {
            $statements_analyzer->node_data->setType($stmt, new Type\Union([new Type\Atomic\TNonEmptyString()]));
        } elseif ($stmt instanceof PhpParser\Node\Scalar\MagicConst\Trait_) {
            if ($statements_analyzer->getSource() instanceof \Psalm\Internal\Analyzer\TraitAnalyzer) {
                $statements_analyzer->node_data->setType($stmt, new Type\Union([new Type\Atomic\TNonEmptyString()]));
            } else {
                $statements_analyzer->node_data->setType($stmt, Type::getString());
            }
        }
    }
}
