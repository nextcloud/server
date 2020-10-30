<?php
namespace Psalm\Internal\Analyzer;

use PhpParser;
use Psalm\Context;
use function strtolower;
use function is_string;

/**
 * @internal
 */
class FunctionAnalyzer extends FunctionLikeAnalyzer
{
    /**
     * @var PhpParser\Node\Stmt\Function_
     */
    protected $function;

    public function __construct(PhpParser\Node\Stmt\Function_ $function, SourceAnalyzer $source)
    {
        $codebase = $source->getCodebase();

        $file_storage_provider = $codebase->file_storage_provider;

        $file_storage = $file_storage_provider->get($source->getFilePath());

        $namespace = $source->getNamespace();

        $function_id = ($namespace ? strtolower($namespace) . '\\' : '') . strtolower($function->name->name);

        if (!isset($file_storage->functions[$function_id])) {
            throw new \UnexpectedValueException(
                'Function ' . $function_id . ' should be defined in ' . $source->getFilePath()
            );
        }

        $storage = $file_storage->functions[$function_id];

        parent::__construct($function, $source, $storage);
    }

    /**
     * @return non-empty-lowercase-string
     */
    public function getFunctionId(): string
    {
        $namespace = $this->source->getNamespace();

        /** @var non-empty-lowercase-string */
        return ($namespace ? strtolower($namespace) . '\\' : '') . strtolower($this->function->name->name);
    }

    public static function analyzeStatement(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Stmt\Function_ $stmt,
        Context $context
    ) : void {
        foreach ($stmt->stmts as $function_stmt) {
            if ($function_stmt instanceof PhpParser\Node\Stmt\Global_) {
                foreach ($function_stmt->vars as $var) {
                    if ($var instanceof PhpParser\Node\Expr\Variable) {
                        if (is_string($var->name)) {
                            $var_id = '$' . $var->name;

                            // registers variable in global context
                            $context->hasVariable($var_id);
                        }
                    }
                }
            } elseif (!$function_stmt instanceof PhpParser\Node\Stmt\Nop) {
                break;
            }
        }

        $codebase = $statements_analyzer->getCodebase();

        if (!$codebase->register_stub_files
            && !$codebase->register_autoload_files
        ) {
            $function_name = strtolower($stmt->name->name);

            if ($ns = $statements_analyzer->getNamespace()) {
                $fq_function_name = strtolower($ns) . '\\' . $function_name;
            } else {
                $fq_function_name = $function_name;
            }

            $function_context = new Context($context->self);
            $function_context->strict_types = $context->strict_types;
            $config = \Psalm\Config::getInstance();
            $function_context->collect_exceptions = $config->check_for_throws_docblock;

            if ($function_analyzer = $statements_analyzer->getFunctionAnalyzer($fq_function_name)) {
                $function_analyzer->analyze(
                    $function_context,
                    $statements_analyzer->node_data,
                    $context
                );

                if ($config->reportIssueInFile('InvalidReturnType', $statements_analyzer->getFilePath())) {
                    $method_id = $function_analyzer->getId();

                    $function_storage = $codebase->functions->getStorage(
                        $statements_analyzer,
                        strtolower($method_id)
                    );

                    $return_type = $function_storage->return_type;
                    $return_type_location = $function_storage->return_type_location;

                    $function_analyzer->verifyReturnType(
                        $stmt->getStmts(),
                        $statements_analyzer,
                        $return_type,
                        $statements_analyzer->getFQCLN(),
                        $return_type_location,
                        $function_context->has_returned
                    );
                }
            }
        }
    }
}
