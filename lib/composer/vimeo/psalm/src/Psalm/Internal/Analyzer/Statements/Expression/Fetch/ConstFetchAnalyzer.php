<?php
namespace Psalm\Internal\Analyzer\Statements\Expression\Fetch;

use PhpParser;
use Psalm\Aliases;
use Psalm\Internal\Analyzer\ClassLikeAnalyzer;
use Psalm\Internal\Analyzer\NamespaceAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\SimpleTypeInferer;
use Psalm\Codebase;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Issue\UndefinedConstant;
use Psalm\IssueBuffer;
use Psalm\Type;
use function array_key_exists;
use function implode;
use function strtolower;
use function explode;
use function array_pop;

/**
 * @internal
 */
class ConstFetchAnalyzer
{
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\ConstFetch $stmt,
        Context $context
    ): void {
        $const_name = implode('\\', $stmt->name->parts);

        switch (strtolower($const_name)) {
            case 'null':
                $statements_analyzer->node_data->setType($stmt, Type::getNull());
                break;

            case 'false':
                // false is a subtype of bool
                $statements_analyzer->node_data->setType($stmt, Type::getFalse());
                break;

            case 'true':
                $statements_analyzer->node_data->setType($stmt, Type::getTrue());
                break;

            case 'stdin':
                $statements_analyzer->node_data->setType($stmt, Type::getResource());
                break;

            default:
                $const_type = self::getConstType(
                    $statements_analyzer,
                    $const_name,
                    $stmt->name instanceof PhpParser\Node\Name\FullyQualified,
                    $context
                );

                if ($const_type) {
                    $statements_analyzer->node_data->setType($stmt, clone $const_type);
                } elseif ($context->check_consts) {
                    if (IssueBuffer::accepts(
                        new UndefinedConstant(
                            'Const ' . $const_name . ' is not defined',
                            new CodeLocation($statements_analyzer->getSource(), $stmt)
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                }
        }
    }

    public static function getGlobalConstType(
        Codebase $codebase,
        ?string $fq_const_name,
        string $const_name
    ): ?Type\Union {
        if ($const_name === 'STDERR'
            || $const_name === 'STDOUT'
            || $const_name === 'STDIN'
        ) {
            return Type::getResource();
        }

        if ($fq_const_name) {
            $stubbed_const_type = $codebase->getStubbedConstantType(
                $fq_const_name
            );

            if ($stubbed_const_type) {
                return $stubbed_const_type;
            }
        }

        $stubbed_const_type = $codebase->getStubbedConstantType(
            $const_name
        );

        if ($stubbed_const_type) {
            return $stubbed_const_type;
        }

        $predefined_constants = $codebase->config->getPredefinedConstants();

        if (($fq_const_name && array_key_exists($fq_const_name, $predefined_constants))
            || array_key_exists($const_name, $predefined_constants)
        ) {
            switch ($const_name) {
                case 'PHP_VERSION':
                case 'DIRECTORY_SEPARATOR':
                case 'PATH_SEPARATOR':
                case 'PEAR_EXTENSION_DIR':
                case 'PEAR_INSTALL_DIR':
                case 'PHP_BINARY':
                case 'PHP_BINDIR':
                case 'PHP_CONFIG_FILE_PATH':
                case 'PHP_CONFIG_FILE_SCAN_DIR':
                case 'PHP_DATADIR':
                case 'PHP_EOL':
                case 'PHP_EXTENSION_DIR':
                case 'PHP_EXTRA_VERSION':
                case 'PHP_LIBDIR':
                case 'PHP_LOCALSTATEDIR':
                case 'PHP_MANDIR':
                case 'PHP_OS':
                case 'PHP_OS_FAMILY':
                case 'PHP_PREFIX':
                case 'PHP_SAPI':
                case 'PHP_SYSCONFDIR':
                    return Type::getString();

                case 'PHP_MAJOR_VERSION':
                case 'PHP_MINOR_VERSION':
                case 'PHP_RELEASE_VERSION':
                case 'PHP_DEBUG':
                case 'PHP_FLOAT_DIG':
                case 'PHP_INT_MAX':
                case 'PHP_INT_MIN':
                case 'PHP_INT_SIZE':
                case 'PHP_MAXPATHLEN':
                case 'PHP_VERSION_ID':
                case 'PHP_ZTS':
                    return Type::getInt();

                case 'PHP_FLOAT_EPSILON':
                case 'PHP_FLOAT_MAX':
                case 'PHP_FLOAT_MIN':
                    return Type::getFloat();
            }

            if ($fq_const_name && array_key_exists($fq_const_name, $predefined_constants)) {
                return ClassLikeAnalyzer::getTypeFromValue($predefined_constants[$fq_const_name]);
            }

            return ClassLikeAnalyzer::getTypeFromValue($predefined_constants[$const_name]);
        }

        return null;
    }

    public static function getConstType(
        StatementsAnalyzer $statements_analyzer,
        string $const_name,
        bool $is_fully_qualified,
        ?Context $context
    ): ?Type\Union {
        $aliased_constants = $statements_analyzer->getAliases()->constants;

        if (isset($aliased_constants[$const_name])) {
            $fq_const_name = $aliased_constants[$const_name];
        } elseif ($is_fully_qualified) {
            $fq_const_name = $const_name;
        } else {
            $fq_const_name = Type::getFQCLNFromString($const_name, $statements_analyzer->getAliases());
        }

        if ($fq_const_name) {
            $const_name_parts = explode('\\', $fq_const_name);
            $const_name = array_pop($const_name_parts);
            $namespace_name = implode('\\', $const_name_parts);
            $namespace_constants = NamespaceAnalyzer::getConstantsForNamespace(
                $namespace_name,
                \ReflectionProperty::IS_PUBLIC
            );

            if (isset($namespace_constants[$const_name])) {
                return $namespace_constants[$const_name];
            }
        }

        if ($context && $context->hasVariable($fq_const_name)) {
            return $context->vars_in_scope[$fq_const_name];
        }

        $file_path = $statements_analyzer->getRootFilePath();
        $codebase = $statements_analyzer->getCodebase();

        $file_storage_provider = $codebase->file_storage_provider;

        $file_storage = $file_storage_provider->get($file_path);

        if (isset($file_storage->declaring_constants[$const_name])) {
            $constant_file_path = $file_storage->declaring_constants[$const_name];

            return $file_storage_provider->get($constant_file_path)->constants[$const_name];
        }

        if (isset($file_storage->declaring_constants[$fq_const_name])) {
            $constant_file_path = $file_storage->declaring_constants[$fq_const_name];

            return $file_storage_provider->get($constant_file_path)->constants[$fq_const_name];
        }

        return ConstFetchAnalyzer::getGlobalConstType($codebase, $fq_const_name, $const_name)
            ?? ConstFetchAnalyzer::getGlobalConstType($codebase, $const_name, $const_name);
    }

    public static function setConstType(
        StatementsAnalyzer $statements_analyzer,
        string $const_name,
        Type\Union $const_type,
        Context $context
    ): void {
        $context->vars_in_scope[$const_name] = $const_type;
        $context->constants[$const_name] = $const_type;

        $source = $statements_analyzer->getSource();

        if ($source instanceof NamespaceAnalyzer) {
            $source->setConstType($const_name, $const_type);
        }
    }

    public static function getConstName(
        PhpParser\Node\Expr $first_arg_value,
        \Psalm\Internal\Provider\NodeDataProvider $type_provider,
        Codebase $codebase,
        Aliases $aliases
    ) : ?string {
        $const_name = null;

        if ($first_arg_value instanceof PhpParser\Node\Scalar\String_) {
            $const_name = $first_arg_value->value;
        } elseif ($first_arg_type = $type_provider->getType($first_arg_value)) {
            if ($first_arg_type->isSingleStringLiteral()) {
                $const_name = $first_arg_type->getSingleStringLiteral()->value;
            }
        } else {
            $simple_type = SimpleTypeInferer::infer($codebase, $type_provider, $first_arg_value, $aliases);

            if ($simple_type && $simple_type->isSingleStringLiteral()) {
                $const_name = $simple_type->getSingleStringLiteral()->value;
            }
        }

        return $const_name;
    }

    public static function analyzeConstAssignment(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Stmt\Const_ $stmt,
        Context $context
    ): void {
        foreach ($stmt->consts as $const) {
            ExpressionAnalyzer::analyze($statements_analyzer, $const->value, $context);

            self::setConstType(
                $statements_analyzer,
                $const->name->name,
                $statements_analyzer->node_data->getType($const->value) ?: Type::getMixed(),
                $context
            );
        }
    }
}
