<?php
namespace Psalm\Internal\Analyzer\Statements\Expression\Call\Method;

use Psalm\Codebase;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Internal\Analyzer\NamespaceAnalyzer;
use Psalm\Issue\DeprecatedMethod;
use Psalm\Issue\InternalMethod;
use Psalm\IssueBuffer;

class MethodCallProhibitionAnalyzer
{
    /**
     * @param  string[]     $suppressed_issues
     *
     * @return false|null
     */
    public static function analyze(
        Codebase $codebase,
        Context $context,
        \Psalm\Internal\MethodIdentifier $method_id,
        ?string $namespace,
        CodeLocation $code_location,
        array $suppressed_issues
    ): ?bool {
        $codebase_methods = $codebase->methods;

        $method_id = $codebase_methods->getDeclaringMethodId($method_id);

        if ($method_id === null) {
            return null;
        }

        $storage = $codebase_methods->getStorage($method_id);

        if ($storage->deprecated) {
            if (IssueBuffer::accepts(
                new DeprecatedMethod(
                    'The method ' . $codebase_methods->getCasedMethodId($method_id) .
                        ' has been marked as deprecated',
                    $code_location,
                    (string) $method_id
                ),
                $suppressed_issues
            )) {
                // continue
            }
        }

        if (!$context->collect_initializations
            && !$context->collect_mutations
        ) {
            if (!NamespaceAnalyzer::isWithin($namespace ?: '', $storage->internal)) {
                if (IssueBuffer::accepts(
                    new InternalMethod(
                        'The method ' . $codebase_methods->getCasedMethodId($method_id)
                            . ' is internal to ' . $storage->internal
                            . ' but called from ' . $context->self,
                        $code_location,
                        (string) $method_id
                    ),
                    $suppressed_issues
                )) {
                    // fall through
                }
            }
        }
        return null;
    }
}
