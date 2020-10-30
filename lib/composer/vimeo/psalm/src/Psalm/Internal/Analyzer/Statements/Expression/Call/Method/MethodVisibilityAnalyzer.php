<?php
namespace Psalm\Internal\Analyzer\Statements\Expression\Call\Method;

use Psalm\Internal\Analyzer\ClassLikeAnalyzer;
use Psalm\Internal\Analyzer\TraitAnalyzer;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Issue\InaccessibleMethod;
use Psalm\IssueBuffer;
use Psalm\StatementsSource;
use function strtolower;

class MethodVisibilityAnalyzer
{
    /**
     * @param  string[]         $suppressed_issues
     *
     * @return false|null
     */
    public static function analyze(
        \Psalm\Internal\MethodIdentifier $method_id,
        Context $context,
        StatementsSource $source,
        CodeLocation $code_location,
        array $suppressed_issues
    ): ?bool {
        $codebase = $source->getCodebase();
        $codebase_methods = $codebase->methods;
        $codebase_classlikes = $codebase->classlikes;

        $fq_classlike_name = $method_id->fq_class_name;
        $method_name = $method_id->method_name;

        if ($codebase_methods->visibility_provider->has($fq_classlike_name)) {
            $method_visible = $codebase_methods->visibility_provider->isMethodVisible(
                $source,
                $fq_classlike_name,
                $method_name,
                $context,
                $code_location
            );

            if ($method_visible === false) {
                if (IssueBuffer::accepts(
                    new InaccessibleMethod(
                        'Cannot access method ' . $codebase_methods->getCasedMethodId($method_id) .
                            ' from context ' . $context->self,
                        $code_location
                    ),
                    $suppressed_issues
                )) {
                    return false;
                }
            } elseif ($method_visible === true) {
                return false;
            }
        }

        $declaring_method_id = $codebase_methods->getDeclaringMethodId($method_id);

        if (!$declaring_method_id) {
            if ($method_name === '__construct'
                || ($method_id->fq_class_name === 'Closure'
                    && ($method_id->method_name === 'fromcallable'
                        || $method_id->method_name === '__invoke'))
            ) {
                return null;
            }

            throw new \UnexpectedValueException('$declaring_method_id not expected to be null here');
        }

        $appearing_method_id = $codebase_methods->getAppearingMethodId($method_id);

        $appearing_method_class = null;
        $appearing_class_storage = null;
        $appearing_method_name = null;

        if ($appearing_method_id) {
            $appearing_method_class = $appearing_method_id->fq_class_name;
            $appearing_method_name = $appearing_method_id->method_name;

            // if the calling class is the same, we know the method exists, so it must be visible
            if ($appearing_method_class === $context->self) {
                return null;
            }

            $appearing_class_storage = $codebase->classlike_storage_provider->get($appearing_method_class);
        }

        $declaring_method_class = $declaring_method_id->fq_class_name;

        if ($source->getSource() instanceof TraitAnalyzer
            && strtolower($declaring_method_class) === strtolower((string) $source->getFQCLN())
        ) {
            return null;
        }

        $storage = $codebase->methods->getStorage($declaring_method_id);
        $visibility = $storage->visibility;

        if ($appearing_method_name
            && isset($appearing_class_storage->trait_visibility_map[$appearing_method_name])
        ) {
            $visibility = $appearing_class_storage->trait_visibility_map[$appearing_method_name];
        }

        switch ($visibility) {
            case ClassLikeAnalyzer::VISIBILITY_PUBLIC:
                return null;

            case ClassLikeAnalyzer::VISIBILITY_PRIVATE:
                if (!$context->self || $appearing_method_class !== $context->self) {
                    if (IssueBuffer::accepts(
                        new InaccessibleMethod(
                            'Cannot access private method ' . $codebase_methods->getCasedMethodId($method_id) .
                                ' from context ' . $context->self,
                            $code_location
                        ),
                        $suppressed_issues
                    )) {
                        return false;
                    }
                }

                return null;

            case ClassLikeAnalyzer::VISIBILITY_PROTECTED:
                if (!$context->self) {
                    if (IssueBuffer::accepts(
                        new InaccessibleMethod(
                            'Cannot access protected method ' . $method_id,
                            $code_location
                        ),
                        $suppressed_issues
                    )) {
                        return false;
                    }

                    return null;
                }

                if ($appearing_method_class
                    && $codebase_classlikes->classExtends($appearing_method_class, $context->self)
                ) {
                    return null;
                }

                if ($appearing_method_class
                    && !$codebase_classlikes->classExtends($context->self, $appearing_method_class)
                ) {
                    if (IssueBuffer::accepts(
                        new InaccessibleMethod(
                            'Cannot access protected method ' . $codebase_methods->getCasedMethodId($method_id) .
                                ' from context ' . $context->self,
                            $code_location
                        ),
                        $suppressed_issues
                    )) {
                        return false;
                    }
                }
        }

        return null;
    }
}
