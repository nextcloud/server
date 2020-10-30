<?php
namespace Psalm\Internal\Analyzer\Statements\Expression;

use PhpParser;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\DataFlow\TaintSink;
use Psalm\Internal\Codebase\TaintFlowGraph;
use Psalm\CodeLocation;
use Psalm\Config;
use Psalm\Context;
use Psalm\Exception\FileIncludeException;
use Psalm\Issue\MissingFile;
use Psalm\Issue\UnresolvableInclude;
use Psalm\IssueBuffer;
use function str_replace;
use const DIRECTORY_SEPARATOR;
use function dirname;
use function preg_match;
use function in_array;
use function realpath;
use function get_included_files;
use function str_repeat;
use const PHP_EOL;
use function is_string;
use function implode;
use function defined;
use function constant;
use const PATH_SEPARATOR;
use function preg_split;
use function get_include_path;
use function explode;
use function substr;
use function file_exists;
use function preg_replace;

/**
 * @internal
 */
class IncludeAnalyzer
{
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\Include_ $stmt,
        Context $context,
        ?Context $global_context = null
    ) : bool {
        $codebase = $statements_analyzer->getCodebase();
        $config = $codebase->config;

        if (!$config->allow_includes) {
            throw new FileIncludeException(
                'File includes are not allowed per your Psalm config - check the allowFileIncludes flag.'
            );
        }

        $was_inside_call = $context->inside_call;

        $context->inside_call = true;

        if (ExpressionAnalyzer::analyze($statements_analyzer, $stmt->expr, $context) === false) {
            return false;
        }

        if (!$was_inside_call) {
            $context->inside_call = false;
        }

        $stmt_expr_type = $statements_analyzer->node_data->getType($stmt->expr);

        if ($stmt->expr instanceof PhpParser\Node\Scalar\String_
            || ($stmt_expr_type && $stmt_expr_type->isSingleStringLiteral())
        ) {
            if ($stmt->expr instanceof PhpParser\Node\Scalar\String_) {
                $path_to_file = $stmt->expr->value;
            } else {
                $path_to_file = $stmt_expr_type->getSingleStringLiteral()->value;
            }

            $path_to_file = str_replace('/', DIRECTORY_SEPARATOR, $path_to_file);

            // attempts to resolve using get_include_path dirs
            $include_path = self::resolveIncludePath($path_to_file, dirname($statements_analyzer->getFilePath()));
            $path_to_file = $include_path ? $include_path : $path_to_file;

            if (DIRECTORY_SEPARATOR === '/') {
                $is_path_relative = $path_to_file[0] !== DIRECTORY_SEPARATOR;
            } else {
                $is_path_relative = !preg_match('~^[A-Z]:\\\\~i', $path_to_file);
            }

            if ($is_path_relative) {
                $path_to_file = $config->base_dir . DIRECTORY_SEPARATOR . $path_to_file;
            }
        } else {
            $path_to_file = self::getPathTo(
                $stmt->expr,
                $statements_analyzer->node_data,
                $statements_analyzer,
                $statements_analyzer->getFileName(),
                $config
            );
        }

        if ($stmt_expr_type
            && $statements_analyzer->data_flow_graph instanceof TaintFlowGraph
            && $stmt_expr_type->parent_nodes
            && !\in_array('TaintedInput', $statements_analyzer->getSuppressedIssues())
        ) {
            $arg_location = new CodeLocation($statements_analyzer->getSource(), $stmt->expr);

            $include_param_sink = TaintSink::getForMethodArgument(
                'include',
                'include',
                0,
                $arg_location
            );

            $include_param_sink->taints = [\Psalm\Type\TaintKind::INPUT_TEXT];

            $statements_analyzer->data_flow_graph->addSink($include_param_sink);

            foreach ($stmt_expr_type->parent_nodes as $parent_node) {
                $statements_analyzer->data_flow_graph->addPath($parent_node, $include_param_sink, 'arg');
            }
        }

        if ($path_to_file) {
            $path_to_file = self::normalizeFilePath($path_to_file);

            // if the file is already included, we can't check much more
            if (in_array(realpath($path_to_file), get_included_files(), true)) {
                return true;
            }

            $current_file_analyzer = $statements_analyzer->getFileAnalyzer();

            if ($current_file_analyzer->project_analyzer->fileExists($path_to_file)) {
                if ($statements_analyzer->hasParentFilePath($path_to_file)
                    || !$codebase->file_storage_provider->has($path_to_file)
                    || ($statements_analyzer->hasAlreadyRequiredFilePath($path_to_file)
                        && !$codebase->file_storage_provider->get($path_to_file)->has_extra_statements)
                ) {
                    return true;
                }

                $current_file_analyzer->addRequiredFilePath($path_to_file);

                $file_name = $config->shortenFileName($path_to_file);

                $nesting = $statements_analyzer->getRequireNesting() + 1;
                $current_file_analyzer->project_analyzer->progress->debug(
                    str_repeat('  ', $nesting) . 'checking ' . $file_name . PHP_EOL
                );

                $include_file_analyzer = new \Psalm\Internal\Analyzer\FileAnalyzer(
                    $current_file_analyzer->project_analyzer,
                    $path_to_file,
                    $file_name
                );

                $include_file_analyzer->setRootFilePath(
                    $current_file_analyzer->getRootFilePath(),
                    $current_file_analyzer->getRootFileName()
                );

                $include_file_analyzer->addParentFilePath($current_file_analyzer->getFilePath());
                $include_file_analyzer->addRequiredFilePath($current_file_analyzer->getFilePath());

                foreach ($current_file_analyzer->getRequiredFilePaths() as $required_file_path) {
                    $include_file_analyzer->addRequiredFilePath($required_file_path);
                }

                foreach ($current_file_analyzer->getParentFilePaths() as $parent_file_path) {
                    $include_file_analyzer->addParentFilePath($parent_file_path);
                }

                try {
                    $include_file_analyzer->analyze(
                        $context,
                        false,
                        $global_context
                    );
                } catch (\Psalm\Exception\UnpreparedAnalysisException $e) {
                    if ($config->skip_checks_on_unresolvable_includes) {
                        $context->check_classes = false;
                        $context->check_variables = false;
                        $context->check_functions = false;
                    }
                }

                $included_return_type = $include_file_analyzer->getReturnType();

                if ($included_return_type) {
                    $statements_analyzer->node_data->setType($stmt, $included_return_type);
                }

                $context->has_returned = false;

                foreach ($include_file_analyzer->getRequiredFilePaths() as $required_file_path) {
                    $current_file_analyzer->addRequiredFilePath($required_file_path);
                }

                $include_file_analyzer->clearSourceBeforeDestruction();

                return true;
            }

            $source = $statements_analyzer->getSource();

            if (IssueBuffer::accepts(
                new MissingFile(
                    'Cannot find file ' . $path_to_file . ' to include',
                    new CodeLocation($source, $stmt)
                ),
                $source->getSuppressedIssues()
            )) {
                // fall through
            }
        } else {
            $var_id = ExpressionIdentifier::getArrayVarId($stmt->expr, null);

            if (!$var_id || !isset($context->phantom_files[$var_id])) {
                $source = $statements_analyzer->getSource();

                if (IssueBuffer::accepts(
                    new UnresolvableInclude(
                        'Cannot resolve the given expression to a file path',
                        new CodeLocation($source, $stmt)
                    ),
                    $source->getSuppressedIssues()
                )) {
                    // fall through
                }
            }
        }

        if ($config->skip_checks_on_unresolvable_includes) {
            $context->check_classes = false;
            $context->check_variables = false;
            $context->check_functions = false;
        }

        return true;
    }

    /**
     * @psalm-suppress MixedAssignment
     */
    public static function getPathTo(
        PhpParser\Node\Expr $stmt,
        ?\Psalm\Internal\Provider\NodeDataProvider $type_provider,
        ?StatementsAnalyzer $statements_analyzer,
        string $file_name,
        Config $config
    ): ?string {
        if (DIRECTORY_SEPARATOR === '/') {
            $is_path_relative = $file_name[0] !== DIRECTORY_SEPARATOR;
        } else {
            $is_path_relative = !preg_match('~^[A-Z]:\\\\~i', $file_name);
        }

        if ($is_path_relative) {
            $file_name = $config->base_dir . DIRECTORY_SEPARATOR . $file_name;
        }

        if ($stmt instanceof PhpParser\Node\Scalar\String_) {
            if (DIRECTORY_SEPARATOR !== '/') {
                return str_replace('/', DIRECTORY_SEPARATOR, $stmt->value);
            }
            return $stmt->value;
        }

        $stmt_type = $type_provider ? $type_provider->getType($stmt) : null;

        if ($stmt_type && $stmt_type->isSingleStringLiteral()) {
            if (DIRECTORY_SEPARATOR !== '/') {
                return str_replace(
                    '/',
                    DIRECTORY_SEPARATOR,
                    $stmt_type->getSingleStringLiteral()->value
                );
            }

            return $stmt_type->getSingleStringLiteral()->value;
        }

        if ($stmt instanceof PhpParser\Node\Expr\ArrayDimFetch) {
            if ($stmt->var instanceof PhpParser\Node\Expr\Variable
                && $stmt->var->name === 'GLOBALS'
                && $stmt->dim instanceof PhpParser\Node\Scalar\String_
            ) {
                if (isset($GLOBALS[$stmt->dim->value]) && is_string($GLOBALS[$stmt->dim->value])) {
                    /** @var string */
                    return $GLOBALS[$stmt->dim->value];
                }
            }
        } elseif ($stmt instanceof PhpParser\Node\Expr\BinaryOp\Concat) {
            $left_string = self::getPathTo($stmt->left, $type_provider, $statements_analyzer, $file_name, $config);
            $right_string = self::getPathTo($stmt->right, $type_provider, $statements_analyzer, $file_name, $config);

            if ($left_string && $right_string) {
                return $left_string . $right_string;
            }
        } elseif ($stmt instanceof PhpParser\Node\Expr\FuncCall &&
            $stmt->name instanceof PhpParser\Node\Name &&
            $stmt->name->parts === ['dirname']
        ) {
            if ($stmt->args) {
                $dir_level = 1;

                if (isset($stmt->args[1])) {
                    if ($stmt->args[1]->value instanceof PhpParser\Node\Scalar\LNumber) {
                        $dir_level = $stmt->args[1]->value->value;
                    } else {
                        return null;
                    }
                }

                $evaled_path = self::getPathTo(
                    $stmt->args[0]->value,
                    $type_provider,
                    $statements_analyzer,
                    $file_name,
                    $config
                );

                if (!$evaled_path) {
                    return null;
                }

                return dirname($evaled_path, $dir_level);
            }
        } elseif ($stmt instanceof PhpParser\Node\Expr\ConstFetch) {
            $const_name = implode('', $stmt->name->parts);

            if (defined($const_name)) {
                $constant_value = constant($const_name);

                if (is_string($constant_value)) {
                    return $constant_value;
                }
            }
        } elseif ($stmt instanceof PhpParser\Node\Scalar\MagicConst\Dir) {
            return dirname($file_name);
        } elseif ($stmt instanceof PhpParser\Node\Scalar\MagicConst\File) {
            return $file_name;
        }

        return null;
    }

    public static function resolveIncludePath(string $file_name, string $current_directory): ?string
    {
        if (!$current_directory) {
            return $file_name;
        }

        $paths = PATH_SEPARATOR === ':'
            ? preg_split('#(?<!phar):#', get_include_path())
            : explode(PATH_SEPARATOR, get_include_path());

        foreach ($paths as $prefix) {
            $ds = substr($prefix, -1) === DIRECTORY_SEPARATOR ? '' : DIRECTORY_SEPARATOR;

            if ($prefix === '.') {
                $prefix = $current_directory;
            }

            $file = $prefix . $ds . $file_name;

            if (file_exists($file)) {
                return $file;
            }
        }

        return null;
    }

    /**
     * @psalm-pure
     */
    public static function normalizeFilePath(string $path_to_file) : string
    {
        // replace all \ with / for normalization
        $path_to_file = str_replace('\\', '/', $path_to_file);
        $path_to_file = str_replace('/./', '/', $path_to_file);

        // first remove unnecessary / duplicates
        $path_to_file = preg_replace('/\/[\/]+/', '/', $path_to_file);

        $reduce_pattern = '/\/[^\/]+\/\.\.\//';

        while (preg_match($reduce_pattern, $path_to_file)) {
            $path_to_file = preg_replace($reduce_pattern, '/', $path_to_file, 1);
        }

        if (DIRECTORY_SEPARATOR !== '/') {
            $path_to_file = str_replace('/', DIRECTORY_SEPARATOR, $path_to_file);
        }

        return $path_to_file;
    }
}
