<?php

namespace Psalm;

use Composer\Autoload\ClassLoader;
use Composer\Semver\Semver;
use DOMDocument;
use LogicException;
use Psalm\Config\IssueHandler;
use Psalm\Config\ProjectFileFilter;
use Psalm\Config\TaintAnalysisFileFilter;
use Psalm\Exception\ConfigException;
use Psalm\Internal\Analyzer\ClassLikeAnalyzer;
use Psalm\Internal\Analyzer\FileAnalyzer;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\Composer;
use Psalm\Internal\IncludeCollector;
use Psalm\Internal\Scanner\FileScanner;
use Psalm\Issue\ArgumentIssue;
use Psalm\Issue\ClassIssue;
use Psalm\Issue\CodeIssue;
use Psalm\Issue\FunctionIssue;
use Psalm\Issue\MethodIssue;
use Psalm\Issue\PropertyIssue;
use Psalm\Issue\VariableIssue;
use Psalm\Plugin\Hook;
use Psalm\Progress\Progress;
use Psalm\Progress\VoidProgress;
use SimpleXMLElement;
use Webmozart\PathUtil\Path;
use XdgBaseDir\Xdg;

use function array_merge;
use function array_pop;
use function chdir;
use function class_exists;
use function count;
use function dirname;
use function explode;
use function file_exists;
use function file_get_contents;
use function filetype;
use function get_class;
use function getcwd;
use function get_defined_constants;
use function get_defined_functions;
use function glob;
use function in_array;
use function intval;
use function is_a;
use function is_dir;
use function is_file;
use function json_decode;
use function libxml_clear_errors;
use function libxml_get_errors;
use function libxml_use_internal_errors;
use function mkdir;
use function phpversion;
use function preg_match;
use function preg_quote;
use function preg_replace;
use function realpath;
use function reset;
use function rmdir;
use function scandir;
use function sha1;
use function simplexml_import_dom;
use function strpos;
use function strrpos;
use function strtolower;
use function strtr;
use function substr;
use function substr_count;
use function sys_get_temp_dir;
use function trigger_error;
use function unlink;
use function version_compare;

use const DIRECTORY_SEPARATOR;
use const E_USER_ERROR;
use const GLOB_NOSORT;
use const LIBXML_ERR_ERROR;
use const LIBXML_ERR_FATAL;
use const LIBXML_NONET;
use const PHP_EOL;
use const SCANDIR_SORT_NONE;
use function array_map;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 * @psalm-consistent-constructor
 */
class Config
{
    private const DEFAULT_FILE_NAME = 'psalm.xml';
    public const REPORT_INFO = 'info';
    public const REPORT_ERROR = 'error';
    public const REPORT_SUPPRESS = 'suppress';

    /**
     * @var array<string>
     */
    public static $ERROR_LEVELS = [
        self::REPORT_INFO,
        self::REPORT_ERROR,
        self::REPORT_SUPPRESS,
    ];

    /**
     * @var array
     */
    private const MIXED_ISSUES = [
        'MixedArgument',
        'MixedArrayAccess',
        'MixedArrayAssignment',
        'MixedArrayOffset',
        'MixedArrayTypeCoercion',
        'MixedAssignment',
        'MixedFunctionCall',
        'MixedInferredReturnType',
        'MixedMethodCall',
        'MixedOperand',
        'MixedPropertyFetch',
        'MixedPropertyAssignment',
        'MixedReturnStatement',
        'MixedStringOffsetAssignment',
        'MixedArgumentTypeCoercion',
        'MixedPropertyTypeCoercion',
        'MixedReturnTypeCoercion',
    ];

    /**
     * These are special object classes that allow any and all properties to be get/set on them
     * @var array<int, class-string>
     */
    protected $universal_object_crates = [
        \stdClass::class,
        SimpleXMLElement::class,
    ];

    /**
     * @var static|null
     */
    private static $instance;

    /**
     * Whether or not to use types as defined in docblocks
     *
     * @var bool
     */
    public $use_docblock_types = true;

    /**
     * Whether or not to use types as defined in property docblocks.
     * This is distinct from the above because you may want to use
     * property docblocks, but not function docblocks.
     *
     * @var bool
     */
    public $use_docblock_property_types = false;

    /**
     * Whether or not to throw an exception on first error
     *
     * @var bool
     */
    public $throw_exception = false;

    /**
     * Whether or not to load Xdebug stub
     *
     * @var bool|null
     */
    public $load_xdebug_stub = null;

    /**
     * The directory to store PHP Parser (and other) caches
     *
     * @var string|null
     */
    public $cache_directory;

    /**
     * The directory to store all Psalm project caches
     *
     * @var string|null
     */
    public $global_cache_directory;

    /**
     * Path to the autoader
     *
     * @var string|null
     */
    public $autoloader;

    /**
     * @var ProjectFileFilter|null
     */
    protected $project_files;

    /**
     * @var ProjectFileFilter|null
     */
    protected $extra_files;

    /**
     * The base directory of this config file
     *
     * @var string
     */
    public $base_dir;

    /**
     * The PHP version to assume as declared in the config file
     *
     * @var string|null
     */
    private $configured_php_version;

    /**
     * @var array<int, string>
     */
    private $file_extensions = ['php'];

    /**
     * @var array<string, class-string<FileScanner>>
     */
    private $filetype_scanners = [];

    /**
     * @var array<string, class-string<FileAnalyzer>>
     */
    private $filetype_analyzers = [];

    /**
     * @var array<string, string>
     */
    private $filetype_scanner_paths = [];

    /**
     * @var array<string, string>
     */
    private $filetype_analyzer_paths = [];

    /**
     * @var array<string, IssueHandler>
     */
    private $issue_handlers = [];

    /**
     * @var array<int, string>
     */
    private $mock_classes = [];

    /**
     * @var array<string, string>
     */
    private $stub_files = [];

    /**
     * @var bool
     */
    public $hide_external_errors = false;

    /** @var bool */
    public $allow_includes = true;

    /** @var 1|2|3|4|5|6|7|8 */
    public $level = 1;

    /**
     * @var ?bool
     */
    public $show_mixed_issues = null;

    /** @var bool */
    public $strict_binary_operands = false;

    /**
     * @var bool
     */
    public $remember_property_assignments_after_call = true;

    /** @var bool */
    public $use_igbinary = false;

    /**
     * @var bool
     */
    public $allow_phpstorm_generics = false;

    /**
     * @var bool
     */
    public $allow_string_standin_for_class = false;

    /**
     * @var bool
     */
    public $use_phpdoc_method_without_magic_or_parent = false;

    /**
     * @var bool
     */
    public $use_phpdoc_property_without_magic_or_parent = false;

    /**
     * @var bool
     */
    public $skip_checks_on_unresolvable_includes = false;

    /**
     * @var bool
     */
    public $seal_all_methods = false;

    /**
     * @var bool
     */
    public $memoize_method_calls = false;

    /**
     * @var bool
     */
    public $hoist_constants = false;

    /**
     * @var bool
     */
    public $add_param_default_to_docblock_type = false;

    /**
     * @var bool
     */
    public $check_for_throws_docblock = false;

    /**
     * @var bool
     */
    public $check_for_throws_in_global_scope = false;

    /**
     * @var bool
     */
    public $ignore_internal_falsable_issues = true;

    /**
     * @var bool
     */
    public $ignore_internal_nullable_issues = true;

    /**
     * @var array<string, bool>
     */
    public $ignored_exceptions = [];

    /**
     * @var array<string, bool>
     */
    public $ignored_exceptions_in_global_scope = [];

    /**
     * @var array<string, bool>
     */
    public $ignored_exceptions_and_descendants = [];

    /**
     * @var array<string, bool>
     */
    public $ignored_exceptions_and_descendants_in_global_scope = [];

    /**
     * @var bool
     */
    public $infer_property_types_from_constructor = true;

    /**
     * @var bool
     */
    public $ensure_array_string_offsets_exist = false;

    /**
     * @var bool
     */
    public $ensure_array_int_offsets_exist = false;

    /**
     * @var array<string, bool>
     */
    public $forbidden_functions = [];

    /**
     * @var bool
     */
    public $forbid_echo = false;

    /**
     * @var bool
     */
    public $find_unused_code = false;

    /**
     * @var bool
     */
    public $find_unused_variables = false;

    /**
     * @var bool
     */
    public $find_unused_psalm_suppress = false;

    /**
     * @var bool
     */
    public $run_taint_analysis = false;

    /** @var bool */
    public $use_phpstorm_meta_path = true;

    /**
     * @var bool
     */
    public $resolve_from_config_file = true;

    /**
     * @var string[]
     */
    public $plugin_paths = [];

    /**
     * @var array<array{class:string,config:?SimpleXMLElement}>
     */
    private $plugin_classes = [];

    /**
     * Static methods to be called after method checks have completed
     *
     * @var class-string<Hook\AfterMethodCallAnalysisInterface>[]
     */
    public $after_method_checks = [];

    /**
     * Static methods to be called after project function checks have completed
     *
     * Called after function calls to functions defined in the project.
     *
     * Allows influencing the return type and adding of modifications.
     *
     * @var class-string<Hook\AfterFunctionCallAnalysisInterface>[]
     */
    public $after_function_checks = [];

    /**
     * Static methods to be called after every function call
     *
     * Called after each function call, including php internal functions.
     *
     * Cannot change the call or influence its return type
     *
     * @var class-string<Hook\AfterEveryFunctionCallAnalysisInterface>[]
     */
    public $after_every_function_checks = [];


    /**
     * Static methods to be called after expression checks have completed
     *
     * @var class-string<Hook\AfterExpressionAnalysisInterface>[]
     */
    public $after_expression_checks = [];

    /**
     * Static methods to be called after statement checks have completed
     *
     * @var class-string<Hook\AfterStatementAnalysisInterface>[]
     */
    public $after_statement_checks = [];

    /**
     * Static methods to be called after method checks have completed
     *
     * @var class-string<Hook\StringInterpreterInterface>[]
     */
    public $string_interpreters = [];

    /**
     * Static methods to be called after classlike exists checks have completed
     *
     * @var class-string<Hook\AfterClassLikeExistenceCheckInterface>[]
     */
    public $after_classlike_exists_checks = [];

    /**
     * Static methods to be called after classlike checks have completed
     *
     * @var class-string<Hook\AfterClassLikeAnalysisInterface>[]
     */
    public $after_classlike_checks = [];

    /**
     * Static methods to be called after classlikes have been scanned
     *
     * @var class-string<Hook\AfterClassLikeVisitInterface>[]
     */
    public $after_visit_classlikes = [];

    /**
     * Static methods to be called after codebase has been populated
     *
     * @var class-string<Hook\AfterCodebasePopulatedInterface>[]
     */
    public $after_codebase_populated = [];

    /**
     * Static methods to be called after codebase has been populated
     *
     * @var class-string<Hook\AfterAnalysisInterface>[]
     */
    public $after_analysis = [];

    /**
     * Static methods to be called after a file has been analyzed
     * @var class-string<Hook\AfterFileAnalysisInterface>[]
     */
    public $after_file_checks = [];

    /**
     * Static methods to be called before a file is analyzed
     * @var class-string<Hook\BeforeFileAnalysisInterface>[]
     */
    public $before_file_checks = [];

    /**
     * @var bool
     */
    public $allow_internal_named_arg_calls = true;

    /**
     * @var bool
     */
    public $allow_named_arg_calls = true;

    /**
     * Static methods to be called after functionlike checks have completed
     *
     * @var class-string<Hook\AfterFunctionLikeAnalysisInterface>[]
     */
    public $after_functionlike_checks = [];

    /** @var array<string, mixed> */
    private $predefined_constants = [];

    /** @var array<callable-string, bool> */
    private $predefined_functions = [];

    /** @var ClassLoader|null */
    private $composer_class_loader;

    /**
     * Custom functions that always exit
     *
     * @var array<string, bool>
     */
    public $exit_functions = [];

    /**
     * @var string
     */
    public $hash = '';

    /** @var string|null */
    public $error_baseline = null;

    /**
     * @var bool
     */
    public $include_php_versions_in_error_baseline = false;

    /** @var string */
    public $shepherd_host = 'shepherd.dev';

    /**
     * @var array<string, string>
     */
    public $globals = [];

    /**
     * @var int
     */
    public $max_string_length = 1000;

    /** @var ?IncludeCollector */
    private $include_collector;

    /**
     * @var TaintAnalysisFileFilter|null
     */
    protected $taint_analysis_ignored_files;

    /**
     * @var bool whether to emit a backtrace of emitted issues to stderr
     */
    public $debug_emitted_issues = false;

    /**
     * @var bool
     */
    private $report_info = true;

    protected function __construct()
    {
        self::$instance = $this;
    }

    /**
     * Gets a Config object from an XML file.
     *
     * Searches up a folder hierarchy for the most immediate config.
     *
     * @throws ConfigException if a config path is not found
     *
     */
    public static function getConfigForPath(string $path, string $current_dir, string $output_format): Config
    {
        $config_path = self::locateConfigFile($path);

        if (!$config_path) {
            if (in_array($output_format, [\Psalm\Report::TYPE_CONSOLE, \Psalm\Report::TYPE_PHP_STORM])) {
                echo 'Could not locate a config XML file in path ' . $path
                    . '. Have you run \'psalm --init\' ?' . PHP_EOL;
                exit(1);
            }
            throw new ConfigException('Config not found for path ' . $path);
        }

        return self::loadFromXMLFile($config_path, $current_dir);
    }

    /**
     * Searches up a folder hierarchy for the most immediate config.
     *
     * @throws ConfigException
     *
     */
    public static function locateConfigFile(string $path): ?string
    {
        $dir_path = realpath($path);

        if ($dir_path === false) {
            throw new ConfigException('Config not found for path ' . $path);
        }

        if (!is_dir($dir_path)) {
            $dir_path = dirname($dir_path);
        }

        do {
            $maybe_path = $dir_path . DIRECTORY_SEPARATOR . Config::DEFAULT_FILE_NAME;

            if (file_exists($maybe_path) || file_exists($maybe_path .= '.dist')) {
                return $maybe_path;
            }

            $dir_path = dirname($dir_path);
        } while (dirname($dir_path) !== $dir_path);

        return null;
    }

    /**
     * Creates a new config object from the file
     */
    public static function loadFromXMLFile(string $file_path, string $current_dir): Config
    {
        $file_contents = file_get_contents($file_path);

        $base_dir = dirname($file_path) . DIRECTORY_SEPARATOR;

        if ($file_contents === false) {
            throw new \InvalidArgumentException('Cannot open ' . $file_path);
        }

        try {
            $config = self::loadFromXML($base_dir, $file_contents, $current_dir);
            $config->hash = sha1($file_contents . \PSALM_VERSION);
        } catch (ConfigException $e) {
            throw new ConfigException(
                'Problem parsing ' . $file_path . ":\n" . '  ' . $e->getMessage()
            );
        }

        return $config;
    }

    /**
     * Creates a new config object from an XML string
     * @param  string|null      $current_dir Current working directory, if different to $base_dir
     *
     * @throws ConfigException
     */
    public static function loadFromXML(string $base_dir, string $file_contents, ?string $current_dir = null): Config
    {
        if ($current_dir === null) {
            $current_dir = $base_dir;
        }

        self::validateXmlConfig($base_dir, $file_contents);

        return self::fromXmlAndPaths($base_dir, $file_contents, $current_dir);
    }

    private static function loadDomDocument(string $base_dir, string $file_contents): DOMDocument
    {
        $dom_document = new DOMDocument();

        // there's no obvious way to set xml:base for a document when loading it from string
        // so instead we're changing the current directory instead to be able to process XIncludes
        $oldpwd = getcwd();
        chdir($base_dir);

        $dom_document->loadXML($file_contents, LIBXML_NONET);
        $dom_document->xinclude(LIBXML_NONET);

        chdir($oldpwd);
        return $dom_document;
    }

    /**
     * @throws ConfigException
     */
    private static function validateXmlConfig(string $base_dir, string $file_contents): void
    {
        $schema_path = dirname(__DIR__, 2). '/config.xsd';

        if (!file_exists($schema_path)) {
            throw new ConfigException('Cannot locate config schema');
        }

        $dom_document = self::loadDomDocument($base_dir, $file_contents);

        $psalm_nodes = $dom_document->getElementsByTagName('psalm');

        /** @var \DomElement|null */
        $psalm_node = $psalm_nodes->item(0);

        if (!$psalm_node) {
            throw new ConfigException(
                'Missing psalm node'
            );
        }

        if (!$psalm_node->hasAttribute('xmlns')) {
            $psalm_node->setAttribute('xmlns', 'https://getpsalm.org/schema/config');

            $old_dom_document = $dom_document;
            $dom_document = self::loadDomDocument($base_dir, $old_dom_document->saveXML());
        }

        // Enable user error handling
        libxml_use_internal_errors(true);

        if (!$dom_document->schemaValidate($schema_path)) {
            $errors = libxml_get_errors();
            foreach ($errors as $error) {
                if ($error->level === LIBXML_ERR_FATAL || $error->level === LIBXML_ERR_ERROR) {
                    throw new ConfigException(
                        'Error on line ' . $error->line . ":\n" . '    ' . $error->message
                    );
                }
            }
            libxml_clear_errors();
        }
    }


    /**
     * @psalm-suppress MixedMethodCall
     * @psalm-suppress MixedAssignment
     * @psalm-suppress MixedOperand
     * @psalm-suppress MixedArgument
     * @psalm-suppress MixedPropertyFetch
     *
     * @throws ConfigException
     */
    private static function fromXmlAndPaths(string $base_dir, string $file_contents, string $current_dir): self
    {
        $config = new static();

        $dom_document = self::loadDomDocument($base_dir, $file_contents);

        $config_xml = simplexml_import_dom($dom_document);

        $booleanAttributes = [
            'useDocblockTypes' => 'use_docblock_types',
            'useDocblockPropertyTypes' => 'use_docblock_property_types',
            'throwExceptionOnError' => 'throw_exception',
            'hideExternalErrors' => 'hide_external_errors',
            'resolveFromConfigFile' => 'resolve_from_config_file',
            'allowFileIncludes' => 'allow_includes',
            'strictBinaryOperands' => 'strict_binary_operands',
            'rememberPropertyAssignmentsAfterCall' => 'remember_property_assignments_after_call',
            'allowPhpStormGenerics' => 'allow_phpstorm_generics',
            'allowStringToStandInForClass' => 'allow_string_standin_for_class',
            'usePhpDocMethodsWithoutMagicCall' => 'use_phpdoc_method_without_magic_or_parent',
            'usePhpDocPropertiesWithoutMagicCall' => 'use_phpdoc_property_without_magic_or_parent',
            'memoizeMethodCallResults' => 'memoize_method_calls',
            'hoistConstants' => 'hoist_constants',
            'addParamDefaultToDocblockType' => 'add_param_default_to_docblock_type',
            'checkForThrowsDocblock' => 'check_for_throws_docblock',
            'checkForThrowsInGlobalScope' => 'check_for_throws_in_global_scope',
            'forbidEcho' => 'forbid_echo',
            'ignoreInternalFunctionFalseReturn' => 'ignore_internal_falsable_issues',
            'ignoreInternalFunctionNullReturn' => 'ignore_internal_nullable_issues',
            'includePhpVersionsInErrorBaseline' => 'include_php_versions_in_error_baseline',
            'loadXdebugStub' => 'load_xdebug_stub',
            'ensureArrayStringOffsetsExist' => 'ensure_array_string_offsets_exist',
            'ensureArrayIntOffsetsExist' => 'ensure_array_int_offsets_exist',
            'reportMixedIssues' => 'show_mixed_issues',
            'skipChecksOnUnresolvableIncludes' => 'skip_checks_on_unresolvable_includes',
            'sealAllMethods' => 'seal_all_methods',
            'runTaintAnalysis' => 'run_taint_analysis',
            'usePhpStormMetaPath' => 'use_phpstorm_meta_path',
            'allowInternalNamedArgumentsCalls' => 'allow_internal_named_arg_calls',
            'allowNamedArgumentCalls' => 'allow_named_arg_calls',
            'findUnusedPsalmSuppress' => 'find_unused_psalm_suppress',
            'reportInfo' => 'report_info',
        ];

        foreach ($booleanAttributes as $xmlName => $internalName) {
            if (isset($config_xml[$xmlName])) {
                $attribute_text = (string) $config_xml[$xmlName];
                $config->setBooleanAttribute(
                    $internalName,
                    $attribute_text === 'true' || $attribute_text === '1'
                );
            }
        }

        if ($config->resolve_from_config_file) {
            $config->base_dir = $base_dir;
        } else {
            $config->base_dir = $current_dir;
            $base_dir = $current_dir;
        }

        if (isset($config_xml['phpVersion'])) {
            $config->configured_php_version = (string) $config_xml['phpVersion'];
        }

        if (isset($config_xml['autoloader'])) {
            $autoloader_path = $config->base_dir . DIRECTORY_SEPARATOR . $config_xml['autoloader'];

            if (!file_exists($autoloader_path)) {
                throw new ConfigException('Cannot locate autoloader');
            }

            $config->autoloader = realpath($autoloader_path);
        }

        if (isset($config_xml['cacheDirectory'])) {
            $config->cache_directory = (string)$config_xml['cacheDirectory'];
        } elseif ($user_cache_dir = (new Xdg())->getHomeCacheDir()) {
            $config->cache_directory = $user_cache_dir . '/psalm';
        } else {
            $config->cache_directory = sys_get_temp_dir() . '/psalm';
        }

        $config->global_cache_directory = $config->cache_directory;

        $config->cache_directory .= DIRECTORY_SEPARATOR . sha1($base_dir);

        if (is_dir($config->cache_directory) === false && @mkdir($config->cache_directory, 0777, true) === false) {
            trigger_error('Could not create cache directory: ' . $config->cache_directory, E_USER_ERROR);
        }

        if (isset($config_xml['serializer'])) {
            $attribute_text = (string) $config_xml['serializer'];
            $config->use_igbinary = $attribute_text === 'igbinary';
        } elseif ($igbinary_version = phpversion('igbinary')) {
            $config->use_igbinary = version_compare($igbinary_version, '2.0.5') >= 0;
        }


        if (isset($config_xml['findUnusedCode'])) {
            $attribute_text = (string) $config_xml['findUnusedCode'];
            $config->find_unused_code = $attribute_text === 'true' || $attribute_text === '1';
            $config->find_unused_variables = $config->find_unused_code;
        }

        if (isset($config_xml['findUnusedVariablesAndParams'])) {
            $attribute_text = (string) $config_xml['findUnusedVariablesAndParams'];
            $config->find_unused_variables = $attribute_text === 'true' || $attribute_text === '1';
        }

        if (isset($config_xml['errorLevel'])) {
            $attribute_text = (int) $config_xml['errorLevel'];

            if (!in_array($attribute_text, [1, 2, 3, 4, 5, 6, 7, 8], true)) {
                throw new Exception\ConfigException(
                    'Invalid error level ' . $config_xml['errorLevel']
                );
            }

            $config->level = $attribute_text;
        } elseif (isset($config_xml['totallyTyped'])) {
            $totally_typed = (string) $config_xml['totallyTyped'];

            if ($totally_typed === 'true' || $totally_typed === '1') {
                $config->level = 1;
            } else {
                $config->level = 2;

                if ($config->show_mixed_issues === null) {
                    $config->show_mixed_issues = false;
                }
            }
        } else {
            $config->level = 2;
        }

        if (isset($config_xml['errorBaseline'])) {
            $attribute_text = (string) $config_xml['errorBaseline'];
            $config->error_baseline = $attribute_text;
        }

        if (isset($config_xml['maxStringLength'])) {
            $attribute_text = intval($config_xml['maxStringLength']);
            $config->max_string_length = $attribute_text;
        }

        if (isset($config_xml['inferPropertyTypesFromConstructor'])) {
            $attribute_text = (string) $config_xml['inferPropertyTypesFromConstructor'];
            $config->infer_property_types_from_constructor = $attribute_text === 'true' || $attribute_text === '1';
        }

        if (isset($config_xml->projectFiles)) {
            $config->project_files = ProjectFileFilter::loadFromXMLElement($config_xml->projectFiles, $base_dir, true);
        }

        if (isset($config_xml->extraFiles)) {
            $config->extra_files = ProjectFileFilter::loadFromXMLElement($config_xml->extraFiles, $base_dir, true);
        }

        if (isset($config_xml->taintAnalysis->ignoreFiles)) {
            $config->taint_analysis_ignored_files = TaintAnalysisFileFilter::loadFromXMLElement(
                $config_xml->taintAnalysis->ignoreFiles,
                $base_dir,
                false
            );
        }

        if (isset($config_xml->fileExtensions)) {
            $config->file_extensions = [];

            $config->loadFileExtensions($config_xml->fileExtensions->extension);
        }

        if (isset($config_xml->mockClasses) && isset($config_xml->mockClasses->class)) {
            /** @var \SimpleXMLElement $mock_class */
            foreach ($config_xml->mockClasses->class as $mock_class) {
                $config->mock_classes[] = strtolower((string)$mock_class['name']);
            }
        }

        if (isset($config_xml->universalObjectCrates) && isset($config_xml->universalObjectCrates->class)) {
            /** @var \SimpleXMLElement $universal_object_crate */
            foreach ($config_xml->universalObjectCrates->class as $universal_object_crate) {
                /** @var class-string $classString */
                $classString = $universal_object_crate['name'];
                $config->addUniversalObjectCrate($classString);
            }
        }

        if (isset($config_xml->ignoreExceptions)) {
            if (isset($config_xml->ignoreExceptions->class)) {
                /** @var \SimpleXMLElement $exception_class */
                foreach ($config_xml->ignoreExceptions->class as $exception_class) {
                    $exception_name = (string) $exception_class['name'];
                    $global_attribute_text = (string) $exception_class['onlyGlobalScope'];
                    if ($global_attribute_text !== 'true' && $global_attribute_text !== '1') {
                        $config->ignored_exceptions[$exception_name] = true;
                    }
                    $config->ignored_exceptions_in_global_scope[$exception_name] = true;
                }
            }
            if (isset($config_xml->ignoreExceptions->classAndDescendants)) {
                /** @var \SimpleXMLElement $exception_class */
                foreach ($config_xml->ignoreExceptions->classAndDescendants as $exception_class) {
                    $exception_name = (string) $exception_class['name'];
                    $global_attribute_text = (string) $exception_class['onlyGlobalScope'];
                    if ($global_attribute_text !== 'true' && $global_attribute_text !== '1') {
                        $config->ignored_exceptions_and_descendants[$exception_name] = true;
                    }
                    $config->ignored_exceptions_and_descendants_in_global_scope[$exception_name] = true;
                }
            }
        }

        if (isset($config_xml->forbiddenFunctions) && isset($config_xml->forbiddenFunctions->function)) {
            /** @var \SimpleXMLElement $forbidden_function */
            foreach ($config_xml->forbiddenFunctions->function as $forbidden_function) {
                $config->forbidden_functions[strtolower((string) $forbidden_function['name'])] = true;
            }
        }

        if (isset($config_xml->exitFunctions) && isset($config_xml->exitFunctions->function)) {
            /** @var \SimpleXMLElement $exit_function */
            foreach ($config_xml->exitFunctions->function as $exit_function) {
                $config->exit_functions[strtolower((string) $exit_function['name'])] = true;
            }
        }

        if (isset($config_xml->stubs) && isset($config_xml->stubs->file)) {
            /** @var \SimpleXMLElement $stub_file */
            foreach ($config_xml->stubs->file as $stub_file) {
                $stub_file_name = (string)$stub_file['name'];
                if (!Path::isAbsolute($stub_file_name)) {
                    $stub_file_name = $config->base_dir . DIRECTORY_SEPARATOR . $stub_file_name;
                }
                $file_path = realpath($stub_file_name);

                if (!$file_path) {
                    throw new Exception\ConfigException(
                        'Cannot resolve stubfile path ' . $config->base_dir . DIRECTORY_SEPARATOR . $stub_file['name']
                    );
                }

                $config->addStubFile($file_path);
            }
        }

        // this plugin loading system borrows heavily from etsy/phan
        if (isset($config_xml->plugins)) {
            if (isset($config_xml->plugins->plugin)) {
                /** @var \SimpleXMLElement $plugin */
                foreach ($config_xml->plugins->plugin as $plugin) {
                    $plugin_file_name = (string) $plugin['filename'];

                    $path = Path::isAbsolute($plugin_file_name)
                        ? $plugin_file_name
                        : $config->base_dir . $plugin_file_name;

                    $config->addPluginPath($path);
                }
            }
            if (isset($config_xml->plugins->pluginClass)) {
                /** @var \SimpleXMLElement $plugin */
                foreach ($config_xml->plugins->pluginClass as $plugin) {
                    $plugin_class_name = $plugin['class'];
                    // any child elements are used as plugin configuration
                    $plugin_config = null;
                    if ($plugin->count()) {
                        $plugin_config = $plugin->children();
                    }

                    $config->addPluginClass((string) $plugin_class_name, $plugin_config);
                }
            }
        }

        if (isset($config_xml->issueHandlers)) {
            /** @var \SimpleXMLElement $issue_handler */
            foreach ($config_xml->issueHandlers->children() as $key => $issue_handler) {
                if ($key === 'PluginIssue') {
                    $custom_class_name = (string) $issue_handler['name'];
                    /** @var string $key */
                    $config->issue_handlers[$custom_class_name] = IssueHandler::loadFromXMLElement(
                        $issue_handler,
                        $base_dir
                    );
                } else {
                    /** @var string $key */
                    $config->issue_handlers[$key] = IssueHandler::loadFromXMLElement(
                        $issue_handler,
                        $base_dir
                    );
                }
            }
        }

        if (isset($config_xml->globals) && isset($config_xml->globals->var)) {
            /** @var \SimpleXMLElement $var */
            foreach ($config_xml->globals->var as $var) {
                $config->globals['$' . (string) $var['name']] = (string) $var['type'];
            }
        }

        return $config;
    }

    public static function getInstance(): Config
    {
        if (self::$instance) {
            return self::$instance;
        }

        throw new \UnexpectedValueException('No config initialized');
    }

    public function setComposerClassLoader(?ClassLoader $loader = null): void
    {
        $this->composer_class_loader = $loader;
    }

    public function setCustomErrorLevel(string $issue_key, string $error_level): void
    {
        $this->issue_handlers[$issue_key] = new IssueHandler();
        $this->issue_handlers[$issue_key]->setErrorLevel($error_level);
    }

    /**
     * @throws ConfigException if a Config file could not be found
     *
     */
    private function loadFileExtensions(SimpleXMLElement $extensions): void
    {
        foreach ($extensions as $extension) {
            $extension_name = preg_replace('/^\.?/', '', (string)$extension['name']);
            $this->file_extensions[] = $extension_name;

            if (isset($extension['scanner'])) {
                $path = $this->base_dir . (string)$extension['scanner'];

                if (!file_exists($path)) {
                    throw new Exception\ConfigException('Error parsing config: cannot find file ' . $path);
                }

                $this->filetype_scanner_paths[$extension_name] = $path;
            }

            if (isset($extension['checker'])) {
                $path = $this->base_dir . (string)$extension['checker'];

                if (!file_exists($path)) {
                    throw new Exception\ConfigException('Error parsing config: cannot find file ' . $path);
                }

                $this->filetype_analyzer_paths[$extension_name] = $path;
            }
        }
    }

    public function addPluginPath(string $path): void
    {
        if (!file_exists($path)) {
            throw new \InvalidArgumentException('Cannot find plugin file ' . $path);
        }

        $this->plugin_paths[] = $path;
    }

    public function addPluginClass(string $class_name, ?SimpleXMLElement $plugin_config = null): void
    {
        $this->plugin_classes[] = ['class' => $class_name, 'config' => $plugin_config];
    }

    /** @return array<array{class:string, config:?SimpleXmlElement}> */
    public function getPluginClasses(): array
    {
        return $this->plugin_classes;
    }

    /**
     * Initialises all the plugins (done once the config is fully loaded)
     *
     * @psalm-suppress MixedAssignment
     */
    public function initializePlugins(ProjectAnalyzer $project_analyzer): void
    {
        $codebase = $project_analyzer->getCodebase();

        $project_analyzer->progress->debug('Initializing plugins...' . PHP_EOL);

        $socket = new PluginRegistrationSocket($this, $codebase);
        // initialize plugin classes earlier to let them hook into subsequent load process
        foreach ($this->plugin_classes as $plugin_class_entry) {
            $plugin_class_name = $plugin_class_entry['class'];
            $plugin_config = $plugin_class_entry['config'];

            try {
                // Below will attempt to load plugins from the project directory first.
                // Failing that, it will use registered autoload chain, which will load
                // plugins from Psalm directory or phar file. If that fails as well, it
                // will fall back to project autoloader. It may seem that the last step
                // will always fail, but it's only true if project uses Composer autoloader
                if ($this->composer_class_loader
                    && ($plugin_class_path = $this->composer_class_loader->findFile($plugin_class_name))
                ) {
                    $project_analyzer->progress->debug(
                        'Loading plugin ' . $plugin_class_name . ' via require' . PHP_EOL
                    );

                    self::requirePath($plugin_class_path);
                } else {
                    if (!class_exists($plugin_class_name, true)) {
                        throw new \UnexpectedValueException($plugin_class_name . ' is not a known class');
                    }
                }

                /**
                 * @psalm-suppress InvalidStringClass
                 *
                 * @var Plugin\PluginEntryPointInterface
                 */
                $plugin_object = new $plugin_class_name;
                $plugin_object($socket, $plugin_config);
            } catch (\Throwable $e) {
                throw new ConfigException('Failed to load plugin ' . $plugin_class_name, 0, $e);
            }

            $project_analyzer->progress->debug('Loaded plugin ' . $plugin_class_name . ' successfully' . PHP_EOL);
        }

        foreach ($this->filetype_scanner_paths as $extension => $path) {
            $fq_class_name = $this->getPluginClassForPath(
                $codebase,
                $path,
                FileScanner::class
            );

            self::requirePath($path);

            $this->filetype_scanners[$extension] = $fq_class_name;
        }

        foreach ($this->filetype_analyzer_paths as $extension => $path) {
            $fq_class_name = $this->getPluginClassForPath(
                $codebase,
                $path,
                FileAnalyzer::class
            );

            self::requirePath($path);

            $this->filetype_analyzers[$extension] = $fq_class_name;
        }

        foreach ($this->plugin_paths as $path) {
            try {
                $plugin_object = new FileBasedPluginAdapter($path, $this, $codebase);
                $plugin_object($socket);
            } catch (\Throwable $e) {
                throw new ConfigException('Failed to load plugin ' . $path, 0, $e);
            }
        }
    }

    private static function requirePath(string $path): void
    {
        /** @psalm-suppress UnresolvableInclude */
        require_once($path);
    }

    /**
     * @template T
     *
     * @param  T::class $must_extend
     *
     * @return class-string<T>
     */
    private function getPluginClassForPath(Codebase $codebase, string $path, string $must_extend): string
    {
        $file_storage = $codebase->createFileStorageForPath($path);
        $file_to_scan = new FileScanner($path, $this->shortenFileName($path), true);
        $file_to_scan->scan(
            $codebase,
            $file_storage
        );

        $declared_classes = ClassLikeAnalyzer::getClassesForFile($codebase, $path);

        if (!count($declared_classes)) {
            throw new \InvalidArgumentException(
                'Plugins must have at least one class in the file - ' . $path . ' has ' .
                    count($declared_classes)
            );
        }

        $fq_class_name = reset($declared_classes);

        if (!$codebase->classlikes->classExtends(
            $fq_class_name,
            $must_extend
        )
        ) {
            throw new \InvalidArgumentException(
                'This plugin must extend ' . $must_extend . ' - ' . $path . ' does not'
            );
        }

        /**
         * @var class-string<T>
         */
        return $fq_class_name;
    }

    public function shortenFileName(string $file_name): string
    {
        return preg_replace('/^' . preg_quote($this->base_dir, '/') . '/', '', $file_name);
    }

    public function reportIssueInFile(string $issue_type, string $file_path): bool
    {
        if (($this->show_mixed_issues === false || $this->level > 2)
            && in_array($issue_type, self::MIXED_ISSUES, true)
        ) {
            return false;
        }

        if ($this->mustBeIgnored($file_path)) {
            return false;
        }

        $dependent_files = [strtolower($file_path) => $file_path];

        $project_analyzer = ProjectAnalyzer::getInstance();

        $codebase = $project_analyzer->getCodebase();

        if (!$this->hide_external_errors) {
            try {
                $file_storage = $codebase->file_storage_provider->get($file_path);
                $dependent_files += $file_storage->required_by_file_paths;
            } catch (\InvalidArgumentException $e) {
                // do nothing
            }
        }

        $any_file_path_matched = false;

        foreach ($dependent_files as $dependent_file_path) {
            if (((!$project_analyzer->full_run && $codebase->analyzer->canReportIssues($dependent_file_path))
                    || $project_analyzer->canReportIssues($dependent_file_path))
                && ($file_path === $dependent_file_path || !$this->mustBeIgnored($dependent_file_path))
            ) {
                $any_file_path_matched = true;
                break;
            }
        }

        if (!$any_file_path_matched) {
            return false;
        }

        if ($this->getReportingLevelForFile($issue_type, $file_path) === self::REPORT_SUPPRESS) {
            return false;
        }

        return true;
    }

    public function isInProjectDirs(string $file_path): bool
    {
        return $this->project_files && $this->project_files->allows($file_path);
    }

    public function isInExtraDirs(string $file_path): bool
    {
        return $this->extra_files && $this->extra_files->allows($file_path);
    }

    public function mustBeIgnored(string $file_path): bool
    {
        return $this->project_files && $this->project_files->forbids($file_path);
    }

    public function trackTaintsInPath(string $file_path): bool
    {
        return !$this->taint_analysis_ignored_files
            || $this->taint_analysis_ignored_files->allows($file_path);
    }

    public function getReportingLevelForIssue(CodeIssue $e): string
    {
        $fqcn_parts = explode('\\', get_class($e));
        $issue_type = array_pop($fqcn_parts);

        $reporting_level = null;

        if ($e instanceof ClassIssue) {
            $reporting_level = $this->getReportingLevelForClass($issue_type, $e->fq_classlike_name);
        } elseif ($e instanceof MethodIssue) {
            $reporting_level = $this->getReportingLevelForMethod($issue_type, $e->method_id);
        } elseif ($e instanceof FunctionIssue) {
            $reporting_level = $this->getReportingLevelForFunction($issue_type, $e->function_id);
        } elseif ($e instanceof PropertyIssue) {
            $reporting_level = $this->getReportingLevelForProperty($issue_type, $e->property_id);
        } elseif ($e instanceof ArgumentIssue && $e->function_id) {
            $reporting_level = $this->getReportingLevelForArgument($issue_type, $e->function_id);
        } elseif ($e instanceof VariableIssue) {
            $reporting_level = $this->getReportingLevelForVariable($issue_type, $e->var_name);
        }

        if ($reporting_level === null) {
            $reporting_level = $this->getReportingLevelForFile($issue_type, $e->getFilePath());
        }

        if (!$this->report_info && $reporting_level === self::REPORT_INFO) {
            $reporting_level = self::REPORT_SUPPRESS;
        }

        $parent_issue_type = self::getParentIssueType($issue_type);

        if ($parent_issue_type && $reporting_level === Config::REPORT_ERROR) {
            $parent_reporting_level = $this->getReportingLevelForFile($parent_issue_type, $e->getFilePath());

            if ($parent_reporting_level !== $reporting_level) {
                return $parent_reporting_level;
            }
        }

        return $reporting_level;
    }

    /**
     * @psalm-pure
     */
    public static function getParentIssueType(string $issue_type): ?string
    {
        if ($issue_type === 'PossiblyUndefinedIntArrayOffset'
            || $issue_type === 'PossiblyUndefinedStringArrayOffset'
        ) {
            return 'PossiblyUndefinedArrayOffset';
        }

        if ($issue_type === 'PossiblyNullReference') {
            return 'NullReference';
        }

        if ($issue_type === 'PossiblyFalseReference') {
            return null;
        }

        if ($issue_type === 'PossiblyUndefinedArrayOffset') {
            return null;
        }

        if (strpos($issue_type, 'Possibly') === 0) {
            $stripped_issue_type = preg_replace('/^Possibly(False|Null)?/', '', $issue_type);

            if (strpos($stripped_issue_type, 'Invalid') === false && strpos($stripped_issue_type, 'Un') !== 0) {
                $stripped_issue_type = 'Invalid' . $stripped_issue_type;
            }

            return $stripped_issue_type;
        }

        if (preg_match('/^(False|Null)[A-Z]/', $issue_type) && !strpos($issue_type, 'Reference')) {
            return preg_replace('/^(False|Null)/', 'Invalid', $issue_type);
        }

        if ($issue_type === 'UndefinedInterfaceMethod') {
            return 'UndefinedMethod';
        }

        if ($issue_type === 'UndefinedMagicPropertyFetch') {
            return 'UndefinedPropertyFetch';
        }

        if ($issue_type === 'UndefinedMagicPropertyAssignment') {
            return 'UndefinedPropertyAssignment';
        }

        if ($issue_type === 'UndefinedMagicMethod') {
            return 'UndefinedMethod';
        }

        if ($issue_type === 'PossibleRawObjectIteration') {
            return 'RawObjectIteration';
        }

        if ($issue_type === 'UninitializedProperty') {
            return 'PropertyNotSetInConstructor';
        }

        if ($issue_type === 'InvalidDocblockParamName') {
            return 'InvalidDocblock';
        }

        if ($issue_type === 'UnusedClosureParam') {
            return 'UnusedParam';
        }

        if ($issue_type === 'StringIncrement') {
            return 'InvalidOperand';
        }

        if ($issue_type === 'InvalidLiteralArgument') {
            return 'InvalidArgument';
        }

        if ($issue_type === 'TraitMethodSignatureMismatch') {
            return 'MethodSignatureMismatch';
        }

        if ($issue_type === 'ImplementedParamTypeMismatch') {
            return 'MoreSpecificImplementedParamType';
        }

        if ($issue_type === 'UndefinedDocblockClass') {
            return 'UndefinedClass';
        }

        return null;
    }

    public function getReportingLevelForFile(string $issue_type, string $file_path): string
    {
        if (isset($this->issue_handlers[$issue_type])) {
            return $this->issue_handlers[$issue_type]->getReportingLevelForFile($file_path);
        }

        // this string is replaced by scoper for Phars, so be careful
        $issue_class = 'Psalm\\Issue\\' . $issue_type;

        if (!class_exists($issue_class) || !is_a($issue_class, \Psalm\Issue\CodeIssue::class, true)) {
            return self::REPORT_ERROR;
        }

        /** @var int */
        $issue_level = $issue_class::ERROR_LEVEL;

        if ($issue_level > 0 && $issue_level < $this->level) {
            return self::REPORT_INFO;
        }

        return self::REPORT_ERROR;
    }

    public function getReportingLevelForClass(string $issue_type, string $fq_classlike_name): ?string
    {
        if (isset($this->issue_handlers[$issue_type])) {
            return $this->issue_handlers[$issue_type]->getReportingLevelForClass($fq_classlike_name);
        }

        return null;
    }

    public function getReportingLevelForMethod(string $issue_type, string $method_id): ?string
    {
        if (isset($this->issue_handlers[$issue_type])) {
            return $this->issue_handlers[$issue_type]->getReportingLevelForMethod($method_id);
        }

        return null;
    }

    public function getReportingLevelForFunction(string $issue_type, string $function_id): ?string
    {
        if (isset($this->issue_handlers[$issue_type])) {
            return $this->issue_handlers[$issue_type]->getReportingLevelForFunction($function_id);
        }

        return null;
    }

    public function getReportingLevelForArgument(string $issue_type, string $function_id): ?string
    {
        if (isset($this->issue_handlers[$issue_type])) {
            return $this->issue_handlers[$issue_type]->getReportingLevelForArgument($function_id);
        }

        return null;
    }

    public function getReportingLevelForProperty(string $issue_type, string $property_id): ?string
    {
        if (isset($this->issue_handlers[$issue_type])) {
            return $this->issue_handlers[$issue_type]->getReportingLevelForProperty($property_id);
        }

        return null;
    }

    public function getReportingLevelForVariable(string $issue_type, string $var_name): ?string
    {
        if (isset($this->issue_handlers[$issue_type])) {
            return $this->issue_handlers[$issue_type]->getReportingLevelForVariable($var_name);
        }

        return null;
    }

    /**
     * @return array<string>
     */
    public function getProjectDirectories(): array
    {
        if (!$this->project_files) {
            return [];
        }

        return $this->project_files->getDirectories();
    }

    /**
     * @return array<string>
     */
    public function getProjectFiles(): array
    {
        if (!$this->project_files) {
            return [];
        }

        return $this->project_files->getFiles();
    }

    /**
     * @return array<string>
     */
    public function getExtraDirectories(): array
    {
        if (!$this->extra_files) {
            return [];
        }

        return $this->extra_files->getDirectories();
    }

    public function reportTypeStatsForFile(string $file_path): bool
    {
        return $this->project_files
            && $this->project_files->allows($file_path)
            && $this->project_files->reportTypeStats($file_path);
    }

    public function useStrictTypesForFile(string $file_path): bool
    {
        return $this->project_files && $this->project_files->useStrictTypes($file_path);
    }

    /**
     * @return array<int, string>
     */
    public function getFileExtensions(): array
    {
        return $this->file_extensions;
    }

    /**
     * @return array<string, class-string<FileScanner>>
     */
    public function getFiletypeScanners(): array
    {
        return $this->filetype_scanners;
    }

    /**
     * @return array<string, class-string<FileAnalyzer>>
     */
    public function getFiletypeAnalyzers(): array
    {
        return $this->filetype_analyzers;
    }

    /**
     * @return array<int, string>
     */
    public function getMockClasses(): array
    {
        return $this->mock_classes;
    }

    public function visitStubFiles(Codebase $codebase, ?Progress $progress = null): void
    {
        if ($progress === null) {
            $progress = new VoidProgress();
        }

        $codebase->register_stub_files = true;

        // note: don't realpath $generic_stubs_path, or phar version will fail
        $generic_stubs_path = dirname(__DIR__, 2) . '/stubs/CoreGenericFunctions.phpstub';

        if (!file_exists($generic_stubs_path)) {
            throw new \UnexpectedValueException('Cannot locate core generic stubs');
        }

        // note: don't realpath $generic_classes_path, or phar version will fail
        $generic_classes_path = dirname(__DIR__, 2) . '/stubs/CoreGenericClasses.phpstub';

        if (!file_exists($generic_classes_path)) {
            throw new \UnexpectedValueException('Cannot locate core generic classes');
        }

        // note: don't realpath $generic_classes_path, or phar version will fail
        $immutable_classes_path = dirname(__DIR__, 2) . '/stubs/CoreImmutableClasses.phpstub';

        if (!file_exists($immutable_classes_path)) {
            throw new \UnexpectedValueException('Cannot locate core immutable classes');
        }

        $core_generic_files = [$generic_stubs_path, $generic_classes_path, $immutable_classes_path];

        if (\extension_loaded('ds')) {
            $ext_ds_path = dirname(__DIR__, 2) . '/stubs/ext-ds.php';

            if (!file_exists($ext_ds_path)) {
                throw new \UnexpectedValueException('Cannot locate core generic classes');
            }

            $core_generic_files[] = $ext_ds_path;
        }

        $stub_files = array_merge($core_generic_files, $this->stub_files);

        $phpstorm_meta_path = $this->base_dir . DIRECTORY_SEPARATOR . '.phpstorm.meta.php';

        if ($this->use_phpstorm_meta_path) {
            if (is_file($phpstorm_meta_path)) {
                $stub_files[] = $phpstorm_meta_path;
            } elseif (is_dir($phpstorm_meta_path)) {
                $phpstorm_meta_path = realpath($phpstorm_meta_path);

                foreach (glob($phpstorm_meta_path . '/*.meta.php', GLOB_NOSORT) as $glob) {
                    if (is_file($glob) && realpath(dirname($glob)) === $phpstorm_meta_path) {
                        $stub_files[] = $glob;
                    }
                }
            }
        }

        if ($this->load_xdebug_stub) {
            $xdebug_stub_path = dirname(__DIR__, 2) . '/stubs/Xdebug.php';

            if (!file_exists($xdebug_stub_path)) {
                throw new \UnexpectedValueException('Cannot locate XDebug stub');
            }

            $stub_files[] = $xdebug_stub_path;
        }

        foreach ($stub_files as $file_path) {
            $file_path = \str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $file_path);
            $codebase->scanner->addFileToDeepScan($file_path);
        }

        $progress->debug('Registering stub files' . "\n");

        $codebase->scanFiles();

        $progress->debug('Finished registering stub files' . "\n");

        $codebase->register_stub_files = false;
    }

    public function getCacheDirectory(): ?string
    {
        return $this->cache_directory;
    }

    public function getGlobalCacheDirectory(): ?string
    {
        return $this->global_cache_directory;
    }

    /**
     * @return array<string, mixed>
     */
    public function getPredefinedConstants(): array
    {
        return $this->predefined_constants;
    }

    public function collectPredefinedConstants(): void
    {
        $this->predefined_constants = get_defined_constants();
    }

    /**
     * @return array<callable-string, bool>
     */
    public function getPredefinedFunctions(): array
    {
        return $this->predefined_functions;
    }

    public function collectPredefinedFunctions(): void
    {
        $defined_functions = get_defined_functions();

        if (isset($defined_functions['user'])) {
            foreach ($defined_functions['user'] as $function_name) {
                $this->predefined_functions[$function_name] = true;
            }
        }

        if (isset($defined_functions['internal'])) {
            foreach ($defined_functions['internal'] as $function_name) {
                $this->predefined_functions[$function_name] = true;
            }
        }
    }

    public function setIncludeCollector(IncludeCollector $include_collector): void
    {
        $this->include_collector = $include_collector;
    }

    /**
     * @psalm-suppress MixedAssignment
     * @psalm-suppress MixedArrayAccess
     */
    public function visitComposerAutoloadFiles(ProjectAnalyzer $project_analyzer, ?Progress $progress = null): void
    {
        if ($progress === null) {
            $progress = new VoidProgress();
        }

        if (!$this->include_collector) {
            throw new LogicException("IncludeCollector should be set at this point");
        }

        $vendor_autoload_files_path
            = $this->base_dir . DIRECTORY_SEPARATOR . 'vendor'
                . DIRECTORY_SEPARATOR . 'composer' . DIRECTORY_SEPARATOR . 'autoload_files.php';

        if (file_exists($vendor_autoload_files_path)) {
            $this->include_collector->runAndCollect(
                function () use ($vendor_autoload_files_path) {
                    /**
                     * @psalm-suppress UnresolvableInclude
                     * @var string[]
                     */
                    return require $vendor_autoload_files_path;
                }
            );
        }

        $codebase = $project_analyzer->getCodebase();

        $this->collectPredefinedFunctions();

        if ($this->autoloader) {
            // somee classes that we think are missing may not actually be missing
            // as they might be autoloadable once we require the autoloader below
            $codebase->classlikes->forgetMissingClassLikes();

            $this->include_collector->runAndCollect(
                function () {
                    // do this in a separate method so scope does not leak
                    /** @psalm-suppress UnresolvableInclude */
                    require $this->autoloader;
                }
            );
        }

        $this->collectPredefinedConstants();

        $autoload_included_files = $this->include_collector->getFilteredIncludedFiles();

        if ($autoload_included_files) {
            $codebase->register_autoload_files = true;

            $progress->debug('Registering autoloaded files' . "\n");
            foreach ($autoload_included_files as $file_path) {
                $file_path = \str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $file_path);
                $progress->debug('   ' . $file_path . "\n");
                $codebase->scanner->addFileToDeepScan($file_path);
            }

            $codebase->scanner->scanFiles($codebase->classlikes);

            $progress->debug('Finished registering autoloaded files' . "\n");

            $codebase->register_autoload_files = false;
        }
    }

    /**
     * @return string|false
     */
    public function getComposerFilePathForClassLike(string $fq_classlike_name)
    {
        if (!$this->composer_class_loader) {
            return false;
        }

        return $this->composer_class_loader->findFile($fq_classlike_name);
    }

    public function getPotentialComposerFilePathForClassLike(string $class): ?string
    {
        if (!$this->composer_class_loader) {
            return null;
        }

        /** @var array<string, array<int, string>> */
        $psr4_prefixes = $this->composer_class_loader->getPrefixesPsr4();

        // PSR-4 lookup
        $logicalPathPsr4 = strtr($class, '\\', DIRECTORY_SEPARATOR) . '.php';

        $candidate_path = null;

        $maxDepth = 0;

        $subPath = $class;
        while (false !== $lastPos = strrpos($subPath, '\\')) {
            $subPath = substr($subPath, 0, $lastPos);
            $search = $subPath . '\\';
            if (isset($psr4_prefixes[$search])) {
                $depth = substr_count($search, '\\');
                $pathEnd = DIRECTORY_SEPARATOR . substr($logicalPathPsr4, $lastPos + 1);

                foreach ($psr4_prefixes[$search] as $dir) {
                    $dir = realpath($dir);

                    if ($dir
                        && $depth > $maxDepth
                        && $this->isInProjectDirs($dir . DIRECTORY_SEPARATOR . 'testdummy.php')
                    ) {
                        $maxDepth = $depth;
                        $candidate_path = realpath($dir) . $pathEnd;
                    }
                }
            }
        }

        return $candidate_path;
    }

    public static function removeCacheDirectory(string $dir): void
    {
        if (is_dir($dir)) {
            $objects = scandir($dir, SCANDIR_SORT_NONE);

            if ($objects === false) {
                throw new \UnexpectedValueException('Not expecting false here');
            }

            foreach ($objects as $object) {
                if ($object !== '.' && $object !== '..') {
                    if (filetype($dir . '/' . $object) === 'dir') {
                        self::removeCacheDirectory($dir . '/' . $object);
                    } else {
                        unlink($dir . '/' . $object);
                    }
                }
            }

            reset($objects);
            rmdir($dir);
        }
    }

    public function setServerMode(): void
    {
        $this->cache_directory .= '-s';
    }

    public function addStubFile(string $stub_file): void
    {
        $this->stub_files[$stub_file] = $stub_file;
    }

    public function hasStubFile(string $stub_file): bool
    {
        return isset($this->stub_files[$stub_file]);
    }

    /**
     * @return array<string, string>
     */
    public function getStubFiles(): array
    {
        return $this->stub_files;
    }

    public function getPhpVersion(): ?string
    {
        if (isset($this->configured_php_version)) {
            return $this->configured_php_version;
        }

        return $this->getPHPVersionFromComposerJson();
    }

    private function setBooleanAttribute(string $name, bool $value): void
    {
        $this->$name = $value;
    }

    /**
     * @psalm-suppress MixedAssignment
     * @psalm-suppress MixedArrayAccess
     */
    private function getPHPVersionFromComposerJson(): ?string
    {
        $composer_json_path = Composer::getJsonFilePath($this->base_dir);

        if (file_exists($composer_json_path)) {
            if (!$composer_json = json_decode(file_get_contents($composer_json_path), true)) {
                throw new \UnexpectedValueException('Invalid composer.json at ' . $composer_json_path);
            }
            $php_version = $composer_json['require']['php'] ?? null;

            if (\is_string($php_version)) {
                foreach (['5.4', '5.5', '5.6', '7.0', '7.1', '7.2', '7.3', '7.4', '8.0'] as $candidate) {
                    if (Semver::satisfies($candidate, $php_version)) {
                        return $candidate;
                    }
                }
            }
        }
        return null;
    }

    /**
     * @param class-string $class
     */
    public function addUniversalObjectCrate(string $class): void
    {
        $this->universal_object_crates[] = $class;
    }

    /**
     * @return array<int, lowercase-string>
     */
    public function getUniversalObjectCrates(): array
    {
        return array_map('strtolower', $this->universal_object_crates);
    }
}
