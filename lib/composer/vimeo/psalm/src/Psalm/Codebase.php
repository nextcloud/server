<?php
namespace Psalm;

use Psalm\Internal\Analyzer\StatementsAnalyzer;
use function array_combine;
use function array_merge;
use function count;
use function error_log;
use function explode;
use function in_array;
use function krsort;
use function ksort;
use LanguageServerProtocol\Command;
use LanguageServerProtocol\Position;
use LanguageServerProtocol\Range;
use const PHP_MAJOR_VERSION;
use const PHP_MINOR_VERSION;
use PhpParser;
use function preg_match;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\Analyzer\Statements\Block\ForeachAnalyzer;
use Psalm\Internal\Type\Comparator\UnionTypeComparator;
use Psalm\Internal\Codebase\InternalCallMapHandler;
use Psalm\Internal\Provider\ClassLikeStorageProvider;
use Psalm\Internal\Provider\FileProvider;
use Psalm\Internal\Provider\FileReferenceProvider;
use Psalm\Internal\Provider\FileStorageProvider;
use Psalm\Internal\Provider\Providers;
use Psalm\Internal\Provider\StatementsProvider;
use Psalm\Progress\Progress;
use Psalm\Progress\VoidProgress;
use Psalm\Storage\ClassLikeStorage;
use Psalm\Storage\FileStorage;
use Psalm\Storage\FunctionLikeStorage;
use function is_string;
use function strlen;
use function strpos;
use function strrpos;
use function strtolower;
use function substr;
use function substr_count;

class Codebase
{
    /**
     * @var Config
     */
    public $config;

    /**
     * A map of fully-qualified use declarations to the files
     * that reference them (keyed by filename)
     *
     * @var array<string, array<int, \Psalm\CodeLocation>>
     */
    public $use_referencing_locations = [];

    /**
     * A map of file names to the classes that they contain explicit references to
     * used in collaboration with use_referencing_locations
     *
     * @var array<string, array<string, bool>>
     */
    public $use_referencing_files = [];

    /**
     * @var FileStorageProvider
     */
    public $file_storage_provider;

    /**
     * @var ClassLikeStorageProvider
     */
    public $classlike_storage_provider;

    /**
     * @var bool
     */
    public $collect_references = false;

    /**
     * @var bool
     */
    public $collect_locations = false;

    /**
     * @var null|'always'|'auto'
     */
    public $find_unused_code = null;

    /**
     * @var FileProvider
     */
    public $file_provider;

    /**
     * @var FileReferenceProvider
     */
    public $file_reference_provider;

    /**
     * @var StatementsProvider
     */
    public $statements_provider;

    /**
     * @var Progress
     */
    private $progress;

    /**
     * @var array<string, Type\Union>
     */
    private static $stubbed_constants = [];

    /**
     * Whether to register autoloaded information
     *
     * @var bool
     */
    public $register_autoload_files = false;

    /**
     * Whether to log functions just at the file level or globally (for stubs)
     *
     * @var bool
     */
    public $register_stub_files = false;

    /**
     * @var bool
     */
    public $find_unused_variables = false;

    /**
     * @var Internal\Codebase\Scanner
     */
    public $scanner;

    /**
     * @var Internal\Codebase\Analyzer
     */
    public $analyzer;

    /**
     * @var Internal\Codebase\Functions
     */
    public $functions;

    /**
     * @var Internal\Codebase\ClassLikes
     */
    public $classlikes;

    /**
     * @var Internal\Codebase\Methods
     */
    public $methods;

    /**
     * @var Internal\Codebase\Properties
     */
    public $properties;

    /**
     * @var Internal\Codebase\Populator
     */
    public $populator;

    /**
     * @var ?Internal\Codebase\TaintFlowGraph
     */
    public $taint_flow_graph = null;

    /**
     * @var bool
     */
    public $server_mode = false;

    /**
     * @var bool
     */
    public $store_node_types = false;

    /**
     * Whether or not to infer types from usage. Computationally expensive, so turned off by default
     *
     * @var bool
     */
    public $infer_types_from_usage = false;

    /**
     * @var bool
     */
    public $alter_code = false;

    /**
     * @var bool
     */
    public $diff_methods = false;

    /**
     * @var array<lowercase-string, string>
     */
    public $methods_to_move = [];

    /**
     * @var array<lowercase-string, string>
     */
    public $methods_to_rename = [];

    /**
     * @var array<string, string>
     */
    public $properties_to_move = [];

    /**
     * @var array<string, string>
     */
    public $properties_to_rename = [];

    /**
     * @var array<string, string>
     */
    public $class_constants_to_move = [];

    /**
     * @var array<string, string>
     */
    public $class_constants_to_rename = [];

    /**
     * @var array<lowercase-string, string>
     */
    public $classes_to_move = [];

    /**
     * @var array<string, string>
     */
    public $call_transforms = [];

    /**
     * @var array<string, string>
     */
    public $property_transforms = [];

    /**
     * @var array<string, string>
     */
    public $class_constant_transforms = [];

    /**
     * @var array<lowercase-string, string>
     */
    public $class_transforms = [];

    /**
     * @var bool
     */
    public $allow_backwards_incompatible_changes = true;

    /**
     * @var int
     */
    public $php_major_version = PHP_MAJOR_VERSION;

    /**
     * @var int
     */
    public $php_minor_version = PHP_MINOR_VERSION;

    /**
     * @var bool
     */
    public $track_unused_suppressions = false;

    public function __construct(
        Config $config,
        Providers $providers,
        ?Progress $progress = null
    ) {
        if ($progress === null) {
            $progress = new VoidProgress();
        }

        $this->config = $config;
        $this->file_storage_provider = $providers->file_storage_provider;
        $this->classlike_storage_provider = $providers->classlike_storage_provider;
        $this->progress = $progress;
        $this->file_provider = $providers->file_provider;
        $this->file_reference_provider = $providers->file_reference_provider;
        $this->statements_provider = $providers->statements_provider;

        self::$stubbed_constants = [];

        $reflection = new Internal\Codebase\Reflection($providers->classlike_storage_provider, $this);

        $this->scanner = new Internal\Codebase\Scanner(
            $this,
            $config,
            $providers->file_storage_provider,
            $providers->file_provider,
            $reflection,
            $providers->file_reference_provider,
            $progress
        );

        $this->loadAnalyzer();

        $this->functions = new Internal\Codebase\Functions($providers->file_storage_provider, $reflection);

        $this->properties = new Internal\Codebase\Properties(
            $providers->classlike_storage_provider,
            $providers->file_reference_provider
        );

        $this->classlikes = new Internal\Codebase\ClassLikes(
            $this->config,
            $providers->classlike_storage_provider,
            $providers->file_reference_provider,
            $providers->statements_provider,
            $this->scanner
        );

        $this->methods = new Internal\Codebase\Methods(
            $providers->classlike_storage_provider,
            $providers->file_reference_provider,
            $this->classlikes
        );

        $this->populator = new Internal\Codebase\Populator(
            $config,
            $providers->classlike_storage_provider,
            $providers->file_storage_provider,
            $this->classlikes,
            $providers->file_reference_provider,
            $progress
        );

        $this->loadAnalyzer();
    }

    private function loadAnalyzer(): void
    {
        $this->analyzer = new Internal\Codebase\Analyzer(
            $this->config,
            $this->file_provider,
            $this->file_storage_provider,
            $this->progress
        );
    }

    /**
     * @param array<string> $candidate_files
     *
     */
    public function reloadFiles(ProjectAnalyzer $project_analyzer, array $candidate_files): void
    {
        $this->loadAnalyzer();

        $this->file_reference_provider->loadReferenceCache(false);

        Internal\Analyzer\FunctionLikeAnalyzer::clearCache();

        if (!$this->statements_provider->parser_cache_provider) {
            $diff_files = $candidate_files;
        } else {
            $diff_files = [];

            $parser_cache_provider = $this->statements_provider->parser_cache_provider;

            foreach ($candidate_files as $candidate_file_path) {
                if ($parser_cache_provider->loadExistingFileContentsFromCache($candidate_file_path)
                    !== $this->file_provider->getContents($candidate_file_path)
                ) {
                    $diff_files[] = $candidate_file_path;
                }
            }
        }

        $referenced_files = $project_analyzer->getReferencedFilesFromDiff($diff_files, false);

        foreach ($diff_files as $diff_file_path) {
            $this->invalidateInformationForFile($diff_file_path);
        }

        foreach ($referenced_files as $referenced_file_path) {
            if (in_array($referenced_file_path, $diff_files, true)) {
                continue;
            }

            $file_storage = $this->file_storage_provider->get($referenced_file_path);

            foreach ($file_storage->classlikes_in_file as $fq_classlike_name) {
                $this->classlike_storage_provider->remove($fq_classlike_name);
                $this->classlikes->removeClassLike($fq_classlike_name);
            }

            $this->file_storage_provider->remove($referenced_file_path);
            $this->scanner->removeFile($referenced_file_path);
        }

        $referenced_files = array_combine($referenced_files, $referenced_files);

        $this->scanner->addFilesToDeepScan($referenced_files);
        $this->addFilesToAnalyze(array_combine($candidate_files, $candidate_files));

        $this->scanner->scanFiles($this->classlikes);

        $this->file_reference_provider->updateReferenceCache($this, $referenced_files);

        $this->populator->populateCodebase();
    }

    public function enterServerMode(): void
    {
        $this->server_mode = true;
        $this->store_node_types = true;
    }

    public function collectLocations(): void
    {
        $this->collect_locations = true;
        $this->classlikes->collect_locations = true;
        $this->methods->collect_locations = true;
        $this->properties->collect_locations = true;
    }

    /**
     * @param 'always'|'auto' $find_unused_code
     *
     */
    public function reportUnusedCode(string $find_unused_code = 'auto'): void
    {
        $this->collect_references = true;
        $this->classlikes->collect_references = true;
        $this->find_unused_code = $find_unused_code;
        $this->find_unused_variables = true;
    }

    public function reportUnusedVariables(): void
    {
        $this->collect_references = true;
        $this->find_unused_variables = true;
    }

    /**
     * @param array<string, string> $files_to_analyze
     *
     */
    public function addFilesToAnalyze(array $files_to_analyze): void
    {
        $this->scanner->addFilesToDeepScan($files_to_analyze);
        $this->analyzer->addFilesToAnalyze($files_to_analyze);
    }

    /**
     * Scans all files their related files
     *
     */
    public function scanFiles(int $threads = 1): void
    {
        $has_changes = $this->scanner->scanFiles($this->classlikes, $threads);

        if ($has_changes) {
            $this->populator->populateCodebase();
        }
    }

    public function getFileContents(string $file_path): string
    {
        return $this->file_provider->getContents($file_path);
    }

    /**
     * @return list<PhpParser\Node\Stmt>
     */
    public function getStatementsForFile(string $file_path): array
    {
        return $this->statements_provider->getStatementsForFile(
            $file_path,
            $this->php_major_version . '.' . $this->php_minor_version,
            $this->progress
        );
    }

    public function createClassLikeStorage(string $fq_classlike_name): ClassLikeStorage
    {
        return $this->classlike_storage_provider->create($fq_classlike_name);
    }

    public function cacheClassLikeStorage(ClassLikeStorage $classlike_storage, string $file_path): void
    {
        $file_contents = $this->file_provider->getContents($file_path);

        if ($this->classlike_storage_provider->cache) {
            $this->classlike_storage_provider->cache->writeToCache($classlike_storage, $file_path, $file_contents);
        }
    }

    public function exhumeClassLikeStorage(string $fq_classlike_name, string $file_path): void
    {
        $file_contents = $this->file_provider->getContents($file_path);
        $storage = $this->classlike_storage_provider->exhume(
            $fq_classlike_name,
            $file_path,
            $file_contents
        );

        if ($storage->is_trait) {
            $this->classlikes->addFullyQualifiedTraitName($storage->name, $file_path);
        } elseif ($storage->is_interface) {
            $this->classlikes->addFullyQualifiedInterfaceName($storage->name, $file_path);
        } else {
            $this->classlikes->addFullyQualifiedClassName($storage->name, $file_path);
        }
    }

    public static function getPsalmTypeFromReflection(?\ReflectionType $type) : Type\Union
    {
        return \Psalm\Internal\Codebase\Reflection::getPsalmTypeFromReflectionType($type);
    }

    public function createFileStorageForPath(string $file_path): FileStorage
    {
        return $this->file_storage_provider->create($file_path);
    }

    /**
     * @return array<int, CodeLocation>
     */
    public function findReferencesToSymbol(string $symbol): array
    {
        if (!$this->collect_locations) {
            throw new \UnexpectedValueException('Should not be checking references');
        }

        if (strpos($symbol, '::$') !== false) {
            return $this->findReferencesToProperty($symbol);
        }

        if (strpos($symbol, '::') !== false) {
            return $this->findReferencesToMethod($symbol);
        }

        return $this->findReferencesToClassLike($symbol);
    }

    /**
     * @return array<int, CodeLocation>
     */
    public function findReferencesToMethod(string $method_id): array
    {
        return $this->file_reference_provider->getClassMethodLocations(strtolower($method_id));
    }

    /**
     * @return array<int, CodeLocation>
     */
    public function findReferencesToProperty(string $property_id): array
    {
        [$fq_class_name, $property_name] = explode('::', $property_id);

        return $this->file_reference_provider->getClassPropertyLocations(
            strtolower($fq_class_name) . '::' . $property_name
        );
    }

    /**
     * @return CodeLocation[]
     *
     * @psalm-return array<int, CodeLocation>
     */
    public function findReferencesToClassLike(string $fq_class_name): array
    {
        $fq_class_name_lc = strtolower($fq_class_name);
        $locations = $this->file_reference_provider->getClassLocations($fq_class_name_lc);

        if (isset($this->use_referencing_locations[$fq_class_name_lc])) {
            $locations = array_merge($locations, $this->use_referencing_locations[$fq_class_name_lc]);
        }

        return $locations;
    }

    public function getClosureStorage(string $file_path, string $closure_id): Storage\FunctionStorage
    {
        $file_storage = $this->file_storage_provider->get($file_path);

        // closures can be returned here
        if (isset($file_storage->functions[$closure_id])) {
            return $file_storage->functions[$closure_id];
        }

        throw new \UnexpectedValueException(
            'Expecting ' . $closure_id . ' to have storage in ' . $file_path
        );
    }

    public function addGlobalConstantType(string $const_id, Type\Union $type): void
    {
        self::$stubbed_constants[$const_id] = $type;
    }

    public function getStubbedConstantType(string $const_id): ?Type\Union
    {
        return isset(self::$stubbed_constants[$const_id]) ? self::$stubbed_constants[$const_id] : null;
    }

    /**
     * @return array<string, Type\Union>
     */
    public function getAllStubbedConstants(): array
    {
        return self::$stubbed_constants;
    }

    public function fileExists(string $file_path): bool
    {
        return $this->file_provider->fileExists($file_path);
    }

    /**
     * Check whether a class/interface exists
     */
    public function classOrInterfaceExists(
        string $fq_class_name,
        ?CodeLocation $code_location = null,
        ?string $calling_fq_class_name = null,
        ?string $calling_method_id = null
    ): bool {
        return $this->classlikes->classOrInterfaceExists(
            $fq_class_name,
            $code_location,
            $calling_fq_class_name,
            $calling_method_id
        );
    }

    public function classExtendsOrImplements(string $fq_class_name, string $possible_parent): bool
    {
        return $this->classlikes->classExtends($fq_class_name, $possible_parent)
            || $this->classlikes->classImplements($fq_class_name, $possible_parent);
    }

    /**
     * Determine whether or not a given class exists
     */
    public function classExists(
        string $fq_class_name,
        ?CodeLocation $code_location = null,
        ?string $calling_fq_class_name = null,
        ?string $calling_method_id = null
    ): bool {
        return $this->classlikes->classExists(
            $fq_class_name,
            $code_location,
            $calling_fq_class_name,
            $calling_method_id
        );
    }

    /**
     * Determine whether or not a class extends a parent
     *
     * @throws \Psalm\Exception\UnpopulatedClasslikeException when called on unpopulated class
     * @throws \InvalidArgumentException when class does not exist
     */
    public function classExtends(string $fq_class_name, string $possible_parent): bool
    {
        return $this->classlikes->classExtends($fq_class_name, $possible_parent, true);
    }

    /**
     * Check whether a class implements an interface
     */
    public function classImplements(string $fq_class_name, string $interface): bool
    {
        return $this->classlikes->classImplements($fq_class_name, $interface);
    }

    public function interfaceExists(
        string $fq_interface_name,
        ?CodeLocation $code_location = null,
        ?string $calling_fq_class_name = null,
        ?string $calling_method_id = null
    ): bool {
        return $this->classlikes->interfaceExists(
            $fq_interface_name,
            $code_location,
            $calling_fq_class_name,
            $calling_method_id
        );
    }

    public function interfaceExtends(string $interface_name, string $possible_parent): bool
    {
        return $this->classlikes->interfaceExtends($interface_name, $possible_parent);
    }

    /**
     * @return array<string, string> all interfaces extended by $interface_name
     */
    public function getParentInterfaces(string $fq_interface_name): array
    {
        return $this->classlikes->getParentInterfaces(
            $this->classlikes->getUnAliasedName($fq_interface_name)
        );
    }

    /**
     * Determine whether or not a class has the correct casing
     */
    public function classHasCorrectCasing(string $fq_class_name): bool
    {
        return $this->classlikes->classHasCorrectCasing($fq_class_name);
    }

    public function interfaceHasCorrectCasing(string $fq_interface_name): bool
    {
        return $this->classlikes->interfaceHasCorrectCasing($fq_interface_name);
    }

    public function traitHasCorrectCase(string $fq_trait_name): bool
    {
        return $this->classlikes->traitHasCorrectCase($fq_trait_name);
    }

    /**
     * Given a function id, return the function like storage for
     * a method, closure, or function.
     *
     * @param non-empty-string $function_id
     *
     * @return Storage\FunctionStorage|Storage\MethodStorage
     */
    public function getFunctionLikeStorage(
        StatementsAnalyzer $statements_analyzer,
        string $function_id
    ): FunctionLikeStorage {
        $doesMethodExist =
            \Psalm\Internal\MethodIdentifier::isValidMethodIdReference($function_id)
            && $this->methodExists($function_id);

        if ($doesMethodExist) {
            $method_id = \Psalm\Internal\MethodIdentifier::wrap($function_id);

            $declaring_method_id = $this->methods->getDeclaringMethodId($method_id);

            if (!$declaring_method_id) {
                throw new \UnexpectedValueException('Declaring method for ' . $method_id . ' cannot be found');
            }

            return $this->methods->getStorage($declaring_method_id);
        }

        return $this->functions->getStorage($statements_analyzer, strtolower($function_id));
    }

    /**
     * Whether or not a given method exists
     *
     * @param  string|\Psalm\Internal\MethodIdentifier       $method_id
     * @param  string|\Psalm\Internal\MethodIdentifier|null $calling_method_id
     *
     @return bool
     */
    public function methodExists(
        $method_id,
        ?CodeLocation $code_location = null,
        $calling_method_id = null,
        ?string $file_path = null
    ): bool {
        return $this->methods->methodExists(
            Internal\MethodIdentifier::wrap($method_id),
            is_string($calling_method_id) ? strtolower($calling_method_id) : strtolower((string) $calling_method_id),
            $code_location,
            null,
            $file_path
        );
    }

    /**
     * @param  string|\Psalm\Internal\MethodIdentifier $method_id
     *
     * @return array<int, \Psalm\Storage\FunctionLikeParameter>
     */
    public function getMethodParams($method_id): array
    {
        return $this->methods->getMethodParams(Internal\MethodIdentifier::wrap($method_id));
    }

    /**
     * @param  string|\Psalm\Internal\MethodIdentifier $method_id
     *
     */
    public function isVariadic($method_id): bool
    {
        return $this->methods->isVariadic(Internal\MethodIdentifier::wrap($method_id));
    }

    /**
     * @param  string|\Psalm\Internal\MethodIdentifier $method_id
     * @param  array<int, PhpParser\Node\Arg> $call_args
     *
     */
    public function getMethodReturnType($method_id, ?string &$self_class, array $call_args = []): ?Type\Union
    {
        return $this->methods->getMethodReturnType(
            Internal\MethodIdentifier::wrap($method_id),
            $self_class,
            null,
            $call_args
        );
    }

    /**
     * @param  string|\Psalm\Internal\MethodIdentifier $method_id
     *
     */
    public function getMethodReturnsByRef($method_id): bool
    {
        return $this->methods->getMethodReturnsByRef(Internal\MethodIdentifier::wrap($method_id));
    }

    /**
     * @param  string|\Psalm\Internal\MethodIdentifier   $method_id
     * @param  CodeLocation|null    $defined_location
     *
     */
    public function getMethodReturnTypeLocation(
        $method_id,
        CodeLocation &$defined_location = null
    ): ?CodeLocation {
        return $this->methods->getMethodReturnTypeLocation(
            Internal\MethodIdentifier::wrap($method_id),
            $defined_location
        );
    }

    /**
     * @param  string|\Psalm\Internal\MethodIdentifier $method_id
     *
     */
    public function getDeclaringMethodId($method_id): ?string
    {
        return $this->methods->getDeclaringMethodId(Internal\MethodIdentifier::wrap($method_id));
    }

    /**
     * Get the class this method appears in (vs is declared in, which could give a trait)
     *
     * @param  string|\Psalm\Internal\MethodIdentifier $method_id
     *
     */
    public function getAppearingMethodId($method_id): ?string
    {
        return $this->methods->getAppearingMethodId(Internal\MethodIdentifier::wrap($method_id));
    }

    /**
     * @param  string|\Psalm\Internal\MethodIdentifier $method_id
     *
     * @return array<string, Internal\MethodIdentifier>
     */
    public function getOverriddenMethodIds($method_id): array
    {
        return $this->methods->getOverriddenMethodIds(Internal\MethodIdentifier::wrap($method_id));
    }

    /**
     * @param  string|\Psalm\Internal\MethodIdentifier $method_id
     *
     */
    public function getCasedMethodId($method_id): string
    {
        return $this->methods->getCasedMethodId(Internal\MethodIdentifier::wrap($method_id));
    }

    public function invalidateInformationForFile(string $file_path): void
    {
        $this->scanner->removeFile($file_path);

        try {
            $file_storage = $this->file_storage_provider->get($file_path);
        } catch (\InvalidArgumentException $e) {
            return;
        }

        foreach ($file_storage->classlikes_in_file as $fq_classlike_name) {
            $this->classlike_storage_provider->remove($fq_classlike_name);
            $this->classlikes->removeClassLike($fq_classlike_name);
        }

        $this->file_storage_provider->remove($file_path);
    }

    public function getSymbolInformation(string $file_path, string $symbol): ?string
    {
        if (\is_numeric($symbol[0])) {
            return \preg_replace('/^[^:]*:/', '', $symbol);
        }

        try {
            if (strpos($symbol, '::')) {
                if (strpos($symbol, '()')) {
                    $symbol = substr($symbol, 0, -2);

                    /** @psalm-suppress ArgumentTypeCoercion */
                    $method_id = new \Psalm\Internal\MethodIdentifier(...explode('::', $symbol));

                    $declaring_method_id = $this->methods->getDeclaringMethodId($method_id);

                    if (!$declaring_method_id) {
                        return null;
                    }

                    $storage = $this->methods->getStorage($declaring_method_id);

                    return '<?php ' . $storage->getSignature(true);
                }

                [, $symbol_name] = explode('::', $symbol);

                if (strpos($symbol, '$') !== false) {
                    $storage = $this->properties->getStorage($symbol);

                    return '<?php ' . $storage->getInfo() . ' ' . $symbol_name;
                }

                [$fq_classlike_name, $const_name] = explode('::', $symbol);

                $class_constants = $this->classlikes->getConstantsForClass(
                    $fq_classlike_name,
                    \ReflectionProperty::IS_PRIVATE
                );

                if (!isset($class_constants[$const_name])) {
                    return null;
                }

                return '<?php ' . $const_name;
            }

            if (strpos($symbol, '()')) {
                $function_id = strtolower(substr($symbol, 0, -2));
                $file_storage = $this->file_storage_provider->get($file_path);

                if (isset($file_storage->functions[$function_id])) {
                    $function_storage = $file_storage->functions[$function_id];

                    return '<?php ' . $function_storage->getSignature(true);
                }

                if (!$function_id) {
                    return null;
                }

                $function = $this->functions->getStorage(null, $function_id);
                return '<?php ' . $function->getSignature(true);
            }

            $storage = $this->classlike_storage_provider->get($symbol);

            return '<?php ' . ($storage->abstract ? 'abstract ' : '') . 'class ' . $storage->name;
        } catch (\Exception $e) {
            error_log($e->getMessage());

            return null;
        }
    }

    public function getSymbolLocation(string $file_path, string $symbol): ?CodeLocation
    {
        if (\is_numeric($symbol[0])) {
            $symbol = \preg_replace('/:.*/', '', $symbol);
            $symbol_parts = explode('-', $symbol);

            $file_contents = $this->getFileContents($file_path);

            return new CodeLocation\Raw(
                $file_contents,
                $file_path,
                $this->config->shortenFileName($file_path),
                (int) $symbol_parts[0],
                (int) $symbol_parts[1]
            );
        }

        try {
            if (strpos($symbol, '::')) {
                if (strpos($symbol, '()')) {
                    $symbol = substr($symbol, 0, -2);

                    /** @psalm-suppress ArgumentTypeCoercion */
                    $method_id = new \Psalm\Internal\MethodIdentifier(...explode('::', $symbol));

                    $declaring_method_id = $this->methods->getDeclaringMethodId($method_id);

                    if (!$declaring_method_id) {
                        return null;
                    }

                    $storage = $this->methods->getStorage($declaring_method_id);

                    return $storage->location;
                }

                if (strpos($symbol, '$') !== false) {
                    $storage = $this->properties->getStorage($symbol);

                    return $storage->location;
                }

                [$fq_classlike_name, $const_name] = explode('::', $symbol);

                $class_constants = $this->classlikes->getConstantsForClass(
                    $fq_classlike_name,
                    \ReflectionProperty::IS_PRIVATE
                );

                if (!isset($class_constants[$const_name])) {
                    return null;
                }

                return $class_constants[$const_name]->location;
            }

            if (strpos($symbol, '()')) {
                $file_storage = $this->file_storage_provider->get($file_path);

                $function_id = strtolower(substr($symbol, 0, -2));

                if (isset($file_storage->functions[$function_id])) {
                    return $file_storage->functions[$function_id]->location;
                }

                if (!$function_id) {
                    return null;
                }

                $function = $this->functions->getStorage(null, $function_id);
                return $function->location;
            }

            $storage = $this->classlike_storage_provider->get($symbol);

            return $storage->location;
        } catch (\UnexpectedValueException $e) {
            error_log($e->getMessage());

            return null;
        } catch (\InvalidArgumentException $e) {
            return null;
        }
    }

    /**
     * @return array{0: string, 1: Range}|null
     */
    public function getReferenceAtPosition(string $file_path, Position $position): ?array
    {
        $is_open = $this->file_provider->isOpen($file_path);

        if (!$is_open) {
            throw new \Psalm\Exception\UnanalyzedFileException($file_path . ' is not open');
        }

        $file_contents = $this->getFileContents($file_path);

        $offset = $position->toOffset($file_contents);

        [$reference_map, $type_map] = $this->analyzer->getMapsForFile($file_path);

        $reference = null;

        if (!$reference_map && !$type_map) {
            return null;
        }

        $reference_start_pos = null;
        $reference_end_pos = null;

        ksort($reference_map);

        foreach ($reference_map as $start_pos => [$end_pos, $possible_reference]) {
            if ($offset < $start_pos) {
                break;
            }

            if ($offset > $end_pos) {
                continue;
            }
            $reference_start_pos = $start_pos;
            $reference_end_pos = $end_pos;
            $reference = $possible_reference;
        }

        if ($reference === null || $reference_start_pos === null || $reference_end_pos === null) {
            return null;
        }

        $range = new Range(
            self::getPositionFromOffset($reference_start_pos, $file_contents),
            self::getPositionFromOffset($reference_end_pos, $file_contents)
        );

        return [$reference, $range];
    }

    /**
     * @return array{0: non-empty-string, 1: int, 2: Range}|null
     */
    public function getFunctionArgumentAtPosition(string $file_path, Position $position): ?array
    {
        $is_open = $this->file_provider->isOpen($file_path);

        if (!$is_open) {
            throw new \Psalm\Exception\UnanalyzedFileException($file_path . ' is not open');
        }

        $file_contents = $this->getFileContents($file_path);

        $offset = $position->toOffset($file_contents);

        [, , $argument_map] = $this->analyzer->getMapsForFile($file_path);

        $reference = null;
        $argument_number = null;

        if (!$argument_map) {
            return null;
        }

        $start_pos = null;
        $end_pos = null;

        ksort($argument_map);

        foreach ($argument_map as $start_pos => [$end_pos, $possible_reference, $possible_argument_number]) {
            if ($offset < $start_pos) {
                break;
            }

            if ($offset > $end_pos) {
                continue;
            }

            $reference = $possible_reference;
            $argument_number = $possible_argument_number;
        }

        if ($reference === null || $start_pos === null || $end_pos === null || $argument_number === null) {
            return null;
        }

        $range = new Range(
            self::getPositionFromOffset($start_pos, $file_contents),
            self::getPositionFromOffset($end_pos, $file_contents)
        );

        return [$reference, $argument_number, $range];
    }

    /**
     * @param  non-empty-string $function_symbol
     */
    public function getSignatureInformation(string $function_symbol) : ?\LanguageServerProtocol\SignatureInformation
    {
        if (strpos($function_symbol, '::') !== false) {
            /** @psalm-suppress ArgumentTypeCoercion */
            $method_id = new \Psalm\Internal\MethodIdentifier(...explode('::', $function_symbol));

            $declaring_method_id = $this->methods->getDeclaringMethodId($method_id);

            if ($declaring_method_id === null) {
                return null;
            }

            $method_storage = $this->methods->getStorage($declaring_method_id);
            $params = $method_storage->params;
        } else {
            try {
                $function_storage = $this->functions->getStorage(null, strtolower($function_symbol));

                $params = $function_storage->params;
            } catch (\Exception $exception) {
                if (InternalCallMapHandler::inCallMap($function_symbol)) {
                    $callables = InternalCallMapHandler::getCallablesFromCallMap($function_symbol);

                    if (!$callables || !$callables[0]->params) {
                        return null;
                    }

                    $params = $callables[0]->params;
                } else {
                    return null;
                }
            }
        }

        $signature_label = '(';
        $parameters = [];

        foreach ($params as $i => $param) {
            $parameter_label = ($param->type ?: 'mixed') . ' $' . $param->name;
            $parameters[] = new \LanguageServerProtocol\ParameterInformation([
                strlen($signature_label),
                strlen($signature_label) + strlen($parameter_label),
            ]);

            $signature_label .= $parameter_label;

            if ($i < (count($params) - 1)) {
                $signature_label .= ', ';
            }
        }

        $signature_label .= ')';

        return new \LanguageServerProtocol\SignatureInformation(
            $signature_label,
            $parameters
        );
    }

    /**
     * @return array{0: string, 1: '->'|'::'|'symbol', 2: int}|null
     */
    public function getCompletionDataAtPosition(string $file_path, Position $position): ?array
    {
        $is_open = $this->file_provider->isOpen($file_path);

        if (!$is_open) {
            throw new \Psalm\Exception\UnanalyzedFileException($file_path . ' is not open');
        }

        $file_contents = $this->getFileContents($file_path);

        $offset = $position->toOffset($file_contents);

        [$reference_map, $type_map] = $this->analyzer->getMapsForFile($file_path);

        if (!$reference_map && !$type_map) {
            return null;
        }

        krsort($type_map);

        foreach ($type_map as $start_pos => [$end_pos_excluding_whitespace, $possible_type]) {
            if ($offset < $start_pos) {
                continue;
            }

            $num_whitespace_bytes = preg_match('/\G\s+/', $file_contents, $matches, 0, $end_pos_excluding_whitespace)
                ? strlen($matches[0])
                : 0;
            $end_pos = $end_pos_excluding_whitespace + $num_whitespace_bytes;

            if ($offset - $end_pos === 2 || $offset - $end_pos === 3) {
                $candidate_gap = substr($file_contents, $end_pos, 2);

                if ($candidate_gap === '->' || $candidate_gap === '::') {
                    $gap = $candidate_gap;
                    $recent_type = $possible_type;

                    if ($recent_type === 'mixed') {
                        return null;
                    }

                    return [$recent_type, $gap, $offset];
                }
            }
        }

        foreach ($reference_map as $start_pos => [$end_pos, $possible_reference]) {
            if ($offset < $start_pos || $possible_reference[0] !== '*') {
                continue;
            }

            if ($offset - $end_pos === 0) {
                $recent_type = $possible_reference;

                return [$recent_type, 'symbol', $offset];
            }
        }

        return null;
    }

    /**
     * @return list<\LanguageServerProtocol\CompletionItem>
     */
    public function getCompletionItemsForClassishThing(string $type_string, string $gap) : array
    {
        $instance_completion_items = [];
        $static_completion_items = [];

        $type = Type::parseString($type_string);

        foreach ($type->getAtomicTypes() as $atomic_type) {
            if ($atomic_type instanceof Type\Atomic\TNamedObject) {
                try {
                    $class_storage = $this->classlike_storage_provider->get($atomic_type->value);

                    foreach ($class_storage->appearing_method_ids as $declaring_method_id) {
                        $method_storage = $this->methods->getStorage($declaring_method_id);

                        $completion_item = new \LanguageServerProtocol\CompletionItem(
                            $method_storage->cased_name,
                            \LanguageServerProtocol\CompletionItemKind::METHOD,
                            (string)$method_storage,
                            null,
                            (string)$method_storage->visibility,
                            $method_storage->cased_name,
                            $method_storage->cased_name . (count($method_storage->params) !== 0 ? '($0)' : '()'),
                            null,
                            null,
                            new Command('Trigger parameter hints', 'editor.action.triggerParameterHints')
                        );
                        $completion_item->insertTextFormat = \LanguageServerProtocol\InsertTextFormat::SNIPPET;

                        if ($method_storage->is_static) {
                            $static_completion_items[] = $completion_item;
                        } else {
                            $instance_completion_items[] = $completion_item;
                        }
                    }

                    foreach ($class_storage->declaring_property_ids as $property_name => $declaring_class) {
                        $property_storage = $this->properties->getStorage(
                            $declaring_class . '::$' . $property_name
                        );

                        $completion_item = new \LanguageServerProtocol\CompletionItem(
                            '$' . $property_name,
                            \LanguageServerProtocol\CompletionItemKind::PROPERTY,
                            $property_storage->getInfo(),
                            null,
                            (string)$property_storage->visibility,
                            $property_name,
                            ($gap === '::' ? '$' : '') . $property_name
                        );

                        if ($property_storage->is_static) {
                            $static_completion_items[] = $completion_item;
                        } else {
                            $instance_completion_items[] = $completion_item;
                        }
                    }

                    foreach ($class_storage->constants as $const_name => $_) {
                        $static_completion_items[] = new \LanguageServerProtocol\CompletionItem(
                            $const_name,
                            \LanguageServerProtocol\CompletionItemKind::VARIABLE,
                            'const ' . $const_name,
                            null,
                            null,
                            $const_name,
                            $const_name
                        );
                    }
                } catch (\Exception $e) {
                    error_log($e->getMessage());
                    continue;
                }
            }
        }

        if ($gap === '->') {
            $completion_items = $instance_completion_items;
        } else {
            $completion_items = array_merge(
                $instance_completion_items,
                $static_completion_items
            );
        }

        return $completion_items;
    }

    /**
     * @return list<\LanguageServerProtocol\CompletionItem>
     */
    public function getCompletionItemsForPartialSymbol(
        string $type_string,
        int $offset,
        string $file_path
    ) : array {
        $matching_classlike_names = $this->classlikes->getMatchingClassLikeNames($type_string);

        $completion_items = [];

        $file_storage = $this->file_storage_provider->get($file_path);

        $aliases = null;

        foreach ($file_storage->classlikes_in_file as $fq_class_name => $_) {
            try {
                $class_storage = $this->classlike_storage_provider->get($fq_class_name);
            } catch (\Exception $e) {
                continue;
            }

            if (!$class_storage->stmt_location) {
                continue;
            }

            if ($offset > $class_storage->stmt_location->raw_file_start
                && $offset < $class_storage->stmt_location->raw_file_end
            ) {
                $aliases = $class_storage->aliases;
                break;
            }
        }

        if (!$aliases) {
            foreach ($file_storage->namespace_aliases as $namespace_start => $namespace_aliases) {
                if ($namespace_start < $offset) {
                    $aliases = $namespace_aliases;
                    break;
                }
            }

            if (!$aliases) {
                $aliases = $file_storage->aliases;
            }
        }

        foreach ($matching_classlike_names as $fq_class_name) {
            $extra_edits = [];

            $insertion_text = Type::getStringFromFQCLN(
                $fq_class_name,
                $aliases && $aliases->namespace ? $aliases->namespace : null,
                $aliases ? $aliases->uses_flipped : [],
                null
            );

            if ($aliases
                && $aliases->namespace
                && $insertion_text === '\\' . $fq_class_name
                && $aliases->namespace_first_stmt_start
            ) {
                $file_contents = $this->getFileContents($file_path);

                $class_name = \preg_replace('/^.*\\\/', '', $fq_class_name);

                if ($aliases->uses_end) {
                    $position = self::getPositionFromOffset($aliases->uses_end, $file_contents);
                    $extra_edits[] = new \LanguageServerProtocol\TextEdit(
                        new Range(
                            $position,
                            $position
                        ),
                        "\n" . 'use ' . $fq_class_name . ';'
                    );
                } else {
                    $position = self::getPositionFromOffset($aliases->namespace_first_stmt_start, $file_contents);
                    $extra_edits[] = new \LanguageServerProtocol\TextEdit(
                        new Range(
                            $position,
                            $position
                        ),
                        'use ' . $fq_class_name . ';' . "\n" . "\n"
                    );
                }

                $insertion_text = $class_name;
            }

            $completion_items[] = new \LanguageServerProtocol\CompletionItem(
                $fq_class_name,
                \LanguageServerProtocol\CompletionItemKind::CLASS_,
                null,
                null,
                null,
                $fq_class_name,
                $insertion_text,
                null,
                $extra_edits
            );
        }

        return $completion_items;
    }

    private static function getPositionFromOffset(int $offset, string $file_contents) : Position
    {
        $file_contents = substr($file_contents, 0, $offset);

        $before_newline_count = strrpos($file_contents, "\n", $offset - strlen($file_contents));

        return new Position(
            substr_count($file_contents, "\n"),
            $offset - (int)$before_newline_count - 1
        );
    }

    public function addTemporaryFileChanges(string $file_path, string $new_content): void
    {
        $this->file_provider->addTemporaryFileChanges($file_path, $new_content);
    }

    public function removeTemporaryFileChanges(string $file_path): void
    {
        $this->file_provider->removeTemporaryFileChanges($file_path);
    }

    /**
     * Checks if type is a subtype of other
     *
     * Given two types, checks if `$input_type` is a subtype of `$container_type`.
     * If you consider `Type\Union` as a set of types, this will tell you
     * if `$input_type` is fully contained in `$container_type`,
     *
     * $input_type ⊆ $container_type
     *
     * Useful for emitting issues like InvalidArgument, where argument at the call site
     * should be a subset of the function parameter type.
     */
    public function isTypeContainedByType(
        Type\Union $input_type,
        Type\Union $container_type
    ): bool {
        return UnionTypeComparator::isContainedBy($this, $input_type, $container_type);
    }

    /**
     * Checks if type has any part that is a subtype of other
     *
     * Given two types, checks if *any part* of `$input_type` is a subtype of `$container_type`.
     * If you consider `Type\Union` as a set of types, this will tell you if intersection
     * of `$input_type` with `$container_type` is not empty.
     *
     * $input_type ∩ $container_type ≠ ∅ , e.g. they are not disjoint.
     *
     * Useful for emitting issues like PossiblyInvalidArgument, where argument at the call
     * site should be a subtype of the function parameter type, but it's has some types that are
     * not a subtype of the required type.
     */
    public function canTypeBeContainedByType(
        Type\Union $input_type,
        Type\Union $container_type
    ): bool {
        return UnionTypeComparator::canBeContainedBy($this, $input_type, $container_type);
    }

    /**
     * Extracts key and value types from a traversable object (or iterable)
     *
     * Given an iterable type (*but not TArray*) returns a tuple of it's key/value types.
     * First element of the tuple holds key type, second has the value type.
     *
     * Example:
     * ```php
     * $codebase->getKeyValueParamsForTraversableObject(Type::parseString('iterable<int,string>'))
     * //  returns [Union(TInt), Union(TString)]
     * ```
     *
     * @return array{Type\Union,Type\Union}
     */
    public function getKeyValueParamsForTraversableObject(Type\Atomic $type): array
    {
        $key_type = null;
        $value_type = null;

        ForeachAnalyzer::getKeyValueParamsForTraversableObject($type, $this, $key_type, $value_type);

        return [
            $key_type ?? Type::getMixed(),
            $value_type ?? Type::getMixed(),
        ];
    }

    /**
     * @param array<string, mixed> $phantom_classes
     * @psalm-suppress PossiblyUnusedMethod part of the public API
     */
    public function queueClassLikeForScanning(
        string $fq_classlike_name,
        bool $analyze_too = false,
        bool $store_failure = true,
        array $phantom_classes = []
    ): void {
        $this->scanner->queueClassLikeForScanning($fq_classlike_name, $analyze_too, $store_failure, $phantom_classes);
    }

    /**
     * @param array<string> $taints
     *
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function addTaintSource(
        Type\Union $expr_type,
        string $taint_id,
        array $taints = \Psalm\Type\TaintKindGroup::ALL_INPUT,
        ?CodeLocation $code_location = null
    ) : void {
        if (!$this->taint_flow_graph) {
            return;
        }

        $source = new \Psalm\Internal\DataFlow\TaintSource(
            $taint_id,
            $taint_id,
            $code_location,
            null,
            $taints
        );

        $this->taint_flow_graph->addSource($source);

        $expr_type->parent_nodes = [
            $source->id => $source,
        ];
    }

    /**
     * @param array<string> $taints
     *
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function addTaintSink(
        string $taint_id,
        array $taints = \Psalm\Type\TaintKindGroup::ALL_INPUT,
        ?CodeLocation $code_location = null
    ) : void {
        if (!$this->taint_flow_graph) {
            return;
        }

        $sink = new \Psalm\Internal\DataFlow\TaintSink(
            $taint_id,
            $taint_id,
            $code_location,
            null,
            $taints
        );

        $this->taint_flow_graph->addSink($sink);
    }
}
