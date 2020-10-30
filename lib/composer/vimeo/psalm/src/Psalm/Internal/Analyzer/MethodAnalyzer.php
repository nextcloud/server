<?php
namespace Psalm\Internal\Analyzer;

use PhpParser;
use Psalm\Codebase;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Issue\InvalidStaticInvocation;
use Psalm\Issue\MethodSignatureMustOmitReturnType;
use Psalm\Issue\NonStaticSelfCall;
use Psalm\Issue\UndefinedMethod;
use Psalm\IssueBuffer;
use Psalm\StatementsSource;
use Psalm\Storage\MethodStorage;

use function strtolower;
use function in_array;

/**
 * @internal
 */
class MethodAnalyzer extends FunctionLikeAnalyzer
{
    /**
     * @var PhpParser\Node\Stmt\ClassMethod
     */
    protected $function;

    public function __construct(
        PhpParser\Node\Stmt\ClassMethod $function,
        SourceAnalyzer $source,
        ?MethodStorage $storage = null
    ) {
        $codebase = $source->getCodebase();

        $method_name_lc = strtolower((string) $function->name);

        $source_fqcln = (string) $source->getFQCLN();

        $source_fqcln_lc = strtolower($source_fqcln);

        $method_id = new \Psalm\Internal\MethodIdentifier($source_fqcln, $method_name_lc);

        if (!$storage) {
            try {
                $storage = $codebase->methods->getStorage($method_id);
            } catch (\UnexpectedValueException $e) {
                $class_storage = $codebase->classlike_storage_provider->get($source_fqcln_lc);

                if (!$class_storage->parent_classes) {
                    throw $e;
                }

                $declaring_method_id = $codebase->methods->getDeclaringMethodId($method_id);

                if (!$declaring_method_id) {
                    throw $e;
                }

                // happens for fake constructors
                $storage = $codebase->methods->getStorage($declaring_method_id);
            }
        }

        parent::__construct($function, $source, $storage);
    }

    /**
     * Determines whether a given method is static or not
     * @param  array<string>   $suppressed_issues
     */
    public static function checkStatic(
        \Psalm\Internal\MethodIdentifier $method_id,
        bool $self_call,
        bool $is_context_dynamic,
        Codebase $codebase,
        CodeLocation $code_location,
        array $suppressed_issues,
        ?bool &$is_dynamic_this_method = false
    ): bool {
        $codebase_methods = $codebase->methods;

        if ($method_id->fq_class_name === 'Closure'
            && $method_id->method_name === 'fromcallable'
        ) {
            return true;
        }

        $original_method_id = $method_id;

        $method_id = $codebase_methods->getDeclaringMethodId($method_id);

        if (!$method_id) {
            throw new \LogicException('Declaring method for ' . $original_method_id . ' should not be null');
        }

        $storage = $codebase_methods->getStorage($method_id);

        if (!$storage->is_static) {
            if ($self_call) {
                if (!$is_context_dynamic) {
                    if (IssueBuffer::accepts(
                        new NonStaticSelfCall(
                            'Method ' . $codebase_methods->getCasedMethodId($method_id) .
                                ' is not static, but is called ' .
                                'using self::',
                            $code_location
                        ),
                        $suppressed_issues
                    )) {
                        return false;
                    }
                } else {
                    $is_dynamic_this_method = true;
                }
            } else {
                if (IssueBuffer::accepts(
                    new InvalidStaticInvocation(
                        'Method ' . $codebase_methods->getCasedMethodId($method_id) .
                            ' is not static, but is called ' .
                            'statically',
                        $code_location
                    ),
                    $suppressed_issues
                )) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * @param  string[]     $suppressed_issues
     * @param  lowercase-string|null  $calling_method_id
     *
     */
    public static function checkMethodExists(
        Codebase $codebase,
        \Psalm\Internal\MethodIdentifier $method_id,
        CodeLocation $code_location,
        array $suppressed_issues,
        ?string $calling_method_id = null
    ): ?bool {
        if ($codebase->methods->methodExists(
            $method_id,
            $calling_method_id,
            !$calling_method_id
                || $calling_method_id !== strtolower((string) $method_id)
                ? $code_location
                : null,
            null,
            $code_location->file_path
        )) {
            return true;
        }

        if (IssueBuffer::accepts(
            new UndefinedMethod('Method ' . $method_id . ' does not exist', $code_location, (string) $method_id),
            $suppressed_issues
        )) {
            return false;
        }

        return null;
    }
    
    public static function isMethodVisible(
        \Psalm\Internal\MethodIdentifier $method_id,
        Context $context,
        StatementsSource $source
    ): bool {
        $codebase = $source->getCodebase();

        $fq_classlike_name = $method_id->fq_class_name;
        $method_name = $method_id->method_name;

        if ($codebase->methods->visibility_provider->has($fq_classlike_name)) {
            $method_visible = $codebase->methods->visibility_provider->isMethodVisible(
                $source,
                $fq_classlike_name,
                $method_name,
                $context,
                null
            );

            if ($method_visible !== null) {
                return $method_visible;
            }
        }

        $declaring_method_id = $codebase->methods->getDeclaringMethodId($method_id);

        if (!$declaring_method_id) {
            // this can happen for methods in the callmap that were not reflected
            return true;
        }

        $appearing_method_id = $codebase->methods->getAppearingMethodId($method_id);

        $appearing_method_class = null;

        if ($appearing_method_id) {
            $appearing_method_class = $appearing_method_id->fq_class_name;

            // if the calling class is the same, we know the method exists, so it must be visible
            if ($appearing_method_class === $context->self) {
                return true;
            }
        }

        $declaring_method_class = $declaring_method_id->fq_class_name;

        if ($source->getSource() instanceof TraitAnalyzer
            && strtolower($declaring_method_class) === strtolower((string) $source->getFQCLN())
        ) {
            return true;
        }

        $storage = $codebase->methods->getStorage($declaring_method_id);

        switch ($storage->visibility) {
            case ClassLikeAnalyzer::VISIBILITY_PUBLIC:
                return true;

            case ClassLikeAnalyzer::VISIBILITY_PRIVATE:
                return $context->self && $appearing_method_class === $context->self;

            case ClassLikeAnalyzer::VISIBILITY_PROTECTED:
                if (!$context->self) {
                    return false;
                }

                if ($appearing_method_class
                    && $codebase->classExtends($appearing_method_class, $context->self)
                ) {
                    return true;
                }

                if ($appearing_method_class
                    && !$codebase->classExtends($context->self, $appearing_method_class)
                ) {
                    return false;
                }
        }

        return true;
    }

    /**
     * Check that __clone, __construct, and __destruct do not have a return type
     * hint in their signature.
     *
     * @return false|null
     */
    public static function checkMethodSignatureMustOmitReturnType(
        MethodStorage $method_storage,
        CodeLocation $code_location
    ): ?bool {
        if ($method_storage->signature_return_type === null) {
            return null;
        }

        $cased_method_name = $method_storage->cased_name;
        $methodsOfInterest = ['__clone', '__construct', '__destruct'];
        if (in_array($cased_method_name, $methodsOfInterest)) {
            if (IssueBuffer::accepts(
                new MethodSignatureMustOmitReturnType(
                    'Method ' . $cased_method_name . ' must not declare a return type',
                    $code_location
                )
            )) {
                return false;
            }
        }

        return null;
    }

    public function getMethodId(?string $context_self = null): \Psalm\Internal\MethodIdentifier
    {
        $function_name = (string)$this->function->name;

        return new \Psalm\Internal\MethodIdentifier(
            $context_self ?: (string) $this->source->getFQCLN(),
            strtolower($function_name)
        );
    }
}
