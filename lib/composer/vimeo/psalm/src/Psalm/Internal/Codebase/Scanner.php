<?php
namespace Psalm\Internal\Codebase;

use function array_filter;
use function array_merge;
use function array_pop;
use function ceil;
use function count;
use const DIRECTORY_SEPARATOR;
use function error_reporting;
use function explode;
use function file_exists;
use function min;
use const PHP_EOL;
use Psalm\Codebase;
use Psalm\Config;
use Psalm\Internal\Analyzer\IssueData;
use Psalm\Internal\Provider\FileProvider;
use Psalm\Internal\Provider\FileReferenceProvider;
use Psalm\Internal\Provider\FileStorageProvider;
use Psalm\Internal\Scanner\FileScanner;
use Psalm\Progress\Progress;
use Psalm\Internal\Codebase\TaintFlowGraph;
use function realpath;
use function strtolower;
use function substr;

/**
 * @psalm-type  ThreadData = array{
 *     0: array<string, string>,
 *     1: array<string, string>,
 *     2: array<string, string>,
 *     3: array<string, bool>,
 *     4: array<string, bool>,
 *     5: array<string, string>,
 *     6: array<string, bool>,
 *     7: array<string, bool>,
 *     8: array<string, bool>
 * }
 *
 * @psalm-type  PoolData = array{
 *     classlikes_data:array{
 *         0:array<lowercase-string, bool>,
 *         1:array<lowercase-string, bool>,
 *         2:array<lowercase-string, bool>,
 *         3:array<string, bool>,
 *         4:array<lowercase-string, bool>,
 *         5:array<string, bool>,
 *         6:array<string, bool>
 *     },
 *     scanner_data: ThreadData,
 *     issues:array<string, list<IssueData>>,
 *     changed_members:array<string, array<string, bool>>,
 *     unchanged_signature_members:array<string, array<string, bool>>,
 *     diff_map:array<string, array<int, array{0:int, 1:int, 2:int, 3:int}>>,
 *     classlike_storage:array<string, \Psalm\Storage\ClassLikeStorage>,
 *     file_storage:array<string, \Psalm\Storage\FileStorage>,
 *     new_file_content_hashes: array<string, string>,
 *     taint_data: ?TaintFlowGraph
 * }
 */

/**
 * @internal
 *
 * Contains methods that aid in the scanning of Psalm's codebase
 */
class Scanner
{
    /**
     * @var Codebase
     */
    private $codebase;

    /**
     * @var array<string, string>
     */
    private $classlike_files = [];

    /**
     * @var array<string, bool>
     */
    private $deep_scanned_classlike_files = [];

    /**
     * @var array<string, string>
     */
    private $files_to_scan = [];

    /**
     * @var array<string, string>
     */
    private $classes_to_scan = [];

    /**
     * @var array<string, bool>
     */
    private $classes_to_deep_scan = [];

    /**
     * @var array<string, string>
     */
    private $files_to_deep_scan = [];

    /**
     * @var array<string, bool>
     */
    private $scanned_files = [];

    /**
     * @var array<string, bool>
     */
    private $store_scan_failure = [];

    /**
     * @var array<string, bool>
     */
    private $reflected_classlikes_lc = [];

    /**
     * @var Reflection
     */
    private $reflection;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var Progress
     */
    private $progress;

    /**
     * @var FileStorageProvider
     */
    private $file_storage_provider;

    /**
     * @var FileProvider
     */
    private $file_provider;

    /**
     * @var FileReferenceProvider
     */
    private $file_reference_provider;

    /**
     * @var bool
     */
    private $is_forked = false;

    public function __construct(
        Codebase $codebase,
        Config $config,
        FileStorageProvider $file_storage_provider,
        FileProvider $file_provider,
        Reflection $reflection,
        FileReferenceProvider $file_reference_provider,
        Progress $progress
    ) {
        $this->codebase = $codebase;
        $this->reflection = $reflection;
        $this->file_provider = $file_provider;
        $this->progress = $progress;
        $this->file_storage_provider = $file_storage_provider;
        $this->config = $config;
        $this->file_reference_provider = $file_reference_provider;
    }

    /**
     * @param array<string, string> $files_to_scan
     *
     */
    public function addFilesToShallowScan(array $files_to_scan): void
    {
        $this->files_to_scan += $files_to_scan;
    }

    /**
     * @param array<string, string> $files_to_scan
     */
    public function addFilesToDeepScan(array $files_to_scan): void
    {
        $this->files_to_scan += $files_to_scan;
        $this->files_to_deep_scan += $files_to_scan;
    }

    public function addFileToShallowScan(string $file_path): void
    {
        $this->files_to_scan[$file_path] = $file_path;
    }

    public function addFileToDeepScan(string $file_path): void
    {
        $this->files_to_scan[$file_path] = $file_path;
        $this->files_to_deep_scan[$file_path] = $file_path;
    }

    public function removeFile(string $file_path): void
    {
        unset($this->scanned_files[$file_path]);
    }

    public function removeClassLike(string $fq_classlike_name_lc): void
    {
        unset(
            $this->classlike_files[$fq_classlike_name_lc],
            $this->deep_scanned_classlike_files[$fq_classlike_name_lc]
        );
    }

    public function setClassLikeFilePath(string $fq_classlike_name_lc, string $file_path): void
    {
        $this->classlike_files[$fq_classlike_name_lc] = $file_path;
    }

    public function getClassLikeFilePath(string $fq_classlike_name_lc): string
    {
        if (!isset($this->classlike_files[$fq_classlike_name_lc])) {
            throw new \UnexpectedValueException('Could not find file for ' . $fq_classlike_name_lc);
        }

        return $this->classlike_files[$fq_classlike_name_lc];
    }

    /**
     * @param  array<string, mixed> $phantom_classes
     */
    public function queueClassLikeForScanning(
        string $fq_classlike_name,
        bool $analyze_too = false,
        bool $store_failure = true,
        array $phantom_classes = []
    ): void {
        if ($fq_classlike_name[0] === '\\') {
            $fq_classlike_name = substr($fq_classlike_name, 1);
        }

        $fq_classlike_name_lc = strtolower($fq_classlike_name);

        if ($fq_classlike_name_lc === 'static') {
            return;
        }

        // avoid checking classes that we know will just end in failure
        if ($fq_classlike_name_lc === 'null' || substr($fq_classlike_name_lc, -5) === '\null') {
            return;
        }

        if (!isset($this->classlike_files[$fq_classlike_name_lc])
            || ($analyze_too && !isset($this->deep_scanned_classlike_files[$fq_classlike_name_lc]))
        ) {
            if (!isset($this->classes_to_scan[$fq_classlike_name_lc]) || $store_failure) {
                $this->classes_to_scan[$fq_classlike_name_lc] = $fq_classlike_name;
            }

            if ($analyze_too) {
                $this->classes_to_deep_scan[$fq_classlike_name_lc] = true;
            }

            $this->store_scan_failure[$fq_classlike_name] = $store_failure;

            if (PropertyMap::inPropertyMap($fq_classlike_name_lc)) {
                $public_mapped_properties = PropertyMap::getPropertyMap()[$fq_classlike_name_lc];

                foreach ($public_mapped_properties as $public_mapped_property) {
                    $property_type = \Psalm\Type::parseString($public_mapped_property);
                    $property_type->queueClassLikesForScanning(
                        $this->codebase,
                        null,
                        $phantom_classes + [$fq_classlike_name_lc => true]
                    );
                }
            }
        }
    }

    public function scanFiles(ClassLikes $classlikes, int $pool_size = 1): bool
    {
        $has_changes = false;
        while ($this->files_to_scan || $this->classes_to_scan) {
            if ($this->files_to_scan) {
                if ($this->scanFilePaths($pool_size)) {
                    $has_changes = true;
                }
            } else {
                $this->convertClassesToFilePaths($classlikes);
            }
        }

        return $has_changes;
    }

    private function scanFilePaths(int $pool_size) : bool
    {
        $filetype_scanners = $this->config->getFiletypeScanners();
        $files_to_scan = array_filter(
            $this->files_to_scan,
            function (string $file_path) : bool {
                return $this->file_provider->fileExists($file_path)
                    && (!isset($this->scanned_files[$file_path])
                        || (isset($this->files_to_deep_scan[$file_path]) && !$this->scanned_files[$file_path]));
            }
        );

        $this->files_to_scan = [];

        if (!$files_to_scan) {
            return false;
        }

        $files_to_deep_scan = $this->files_to_deep_scan;

        $scanner_worker =
            function (int $_, string $file_path) use ($filetype_scanners, $files_to_deep_scan): void {
                $this->scanFile(
                    $file_path,
                    $filetype_scanners,
                    isset($files_to_deep_scan[$file_path])
                );
            };

        if (!$this->is_forked && $pool_size > 1 && count($files_to_scan) > 512) {
            $pool_size = ceil(min($pool_size, count($files_to_scan) / 256));
        } else {
            $pool_size = 1;
        }

        if ($pool_size > 1) {
            $process_file_paths = [];

            $i = 0;

            foreach ($files_to_scan as $file_path) {
                $process_file_paths[$i % $pool_size][] = $file_path;
                ++$i;
            }

            $this->progress->debug('Forking process for scanning' . PHP_EOL);

            // Run scanning one file at a time, splitting the set of
            // files up among a given number of child processes.
            $pool = new \Psalm\Internal\Fork\Pool(
                $process_file_paths,
                function () {
                    $this->progress->debug('Initialising forked process for scanning' . PHP_EOL);

                    $project_analyzer = \Psalm\Internal\Analyzer\ProjectAnalyzer::getInstance();
                    $codebase = $project_analyzer->getCodebase();
                    $statements_provider = $codebase->statements_provider;

                    $codebase->scanner->isForked();
                    $codebase->file_storage_provider->deleteAll();
                    $codebase->classlike_storage_provider->deleteAll();

                    $statements_provider->resetDiffs();

                    $this->progress->debug('Have initialised forked process for scanning' . PHP_EOL);
                },
                $scanner_worker,
                /**
                 * @return PoolData
                 */
                function () {
                    $this->progress->debug('Collecting data from forked scanner process' . PHP_EOL);

                    $project_analyzer = \Psalm\Internal\Analyzer\ProjectAnalyzer::getInstance();
                    $codebase = $project_analyzer->getCodebase();
                    $statements_provider = $codebase->statements_provider;

                    return [
                        'classlikes_data' => $codebase->classlikes->getThreadData(),
                        'scanner_data' => $codebase->scanner->getThreadData(),
                        'issues' => \Psalm\IssueBuffer::getIssuesData(),
                        'changed_members' => $statements_provider->getChangedMembers(),
                        'unchanged_signature_members' => $statements_provider->getUnchangedSignatureMembers(),
                        'diff_map' => $statements_provider->getDiffMap(),
                        'classlike_storage' => $codebase->classlike_storage_provider->getAll(),
                        'file_storage' => $codebase->file_storage_provider->getAll(),
                        'new_file_content_hashes' => $statements_provider->parser_cache_provider
                            ? $statements_provider->parser_cache_provider->getNewFileContentHashes()
                            : [],
                        'taint_data' => $codebase->taint_flow_graph,
                    ];
                }
            );

            // Wait for all tasks to complete and collect the results.
            /**
             * @var array<int, PoolData>
             */
            $forked_pool_data = $pool->wait();

            foreach ($forked_pool_data as $pool_data) {
                \Psalm\IssueBuffer::addIssues($pool_data['issues']);

                $this->codebase->statements_provider->addChangedMembers(
                    $pool_data['changed_members']
                );
                $this->codebase->statements_provider->addUnchangedSignatureMembers(
                    $pool_data['unchanged_signature_members']
                );
                $this->codebase->statements_provider->addDiffMap(
                    $pool_data['diff_map']
                );
                if ($this->codebase->taint_flow_graph && $pool_data['taint_data']) {
                    $this->codebase->taint_flow_graph->addGraph($pool_data['taint_data']);
                }

                $this->codebase->file_storage_provider->addMore($pool_data['file_storage']);
                $this->codebase->classlike_storage_provider->addMore($pool_data['classlike_storage']);

                $this->codebase->classlikes->addThreadData($pool_data['classlikes_data']);

                $this->addThreadData($pool_data['scanner_data']);

                if ($this->codebase->statements_provider->parser_cache_provider) {
                    $this->codebase->statements_provider->parser_cache_provider->addNewFileContentHashes(
                        $pool_data['new_file_content_hashes']
                    );
                }
            }

            if ($pool->didHaveError()) {
                exit(1);
            }
        } else {
            $i = 0;

            foreach ($files_to_scan as $file_path => $_) {
                $scanner_worker($i, $file_path);
                ++$i;
            }
        }

        if ($this->codebase->statements_provider->parser_cache_provider) {
            $this->codebase->statements_provider->parser_cache_provider->saveFileContentHashes();
        }

        foreach ($files_to_scan as $scanned_file) {
            if ($this->config->hasStubFile($scanned_file)) {
                $file_storage = $this->file_storage_provider->get($scanned_file);

                foreach ($file_storage->functions as $function_storage) {
                    if ($function_storage->cased_name
                        && !$this->codebase->functions->hasStubbedFunction($function_storage->cased_name)
                    ) {
                        $this->codebase->functions->addGlobalFunction(
                            $function_storage->cased_name,
                            $function_storage
                        );
                    }
                }

                foreach ($file_storage->constants as $name => $type) {
                    $this->codebase->addGlobalConstantType($name, $type);
                }
            }
        }

        $this->file_reference_provider->addClassLikeFiles($this->classlike_files);

        return true;
    }

    private function convertClassesToFilePaths(ClassLikes $classlikes): void
    {
        $classes_to_scan = $this->classes_to_scan;

        $this->classes_to_scan = [];

        foreach ($classes_to_scan as $fq_classlike_name) {
            $fq_classlike_name_lc = strtolower($fq_classlike_name);

            if (isset($this->reflected_classlikes_lc[$fq_classlike_name_lc])) {
                continue;
            }

            if ($classlikes->isMissingClassLike($fq_classlike_name_lc)) {
                continue;
            }

            if (!isset($this->classlike_files[$fq_classlike_name_lc])) {
                if ($classlikes->doesClassLikeExist($fq_classlike_name_lc)) {
                    if ($fq_classlike_name_lc === 'self') {
                        continue;
                    }

                    $this->progress->debug('Using reflection to get metadata for ' . $fq_classlike_name . "\n");

                    /** @psalm-suppress ArgumentTypeCoercion */
                    $reflected_class = new \ReflectionClass($fq_classlike_name);
                    $this->reflection->registerClass($reflected_class);
                    $this->reflected_classlikes_lc[$fq_classlike_name_lc] = true;
                } elseif ($this->fileExistsForClassLike($classlikes, $fq_classlike_name)) {
                    $fq_classlike_name_lc = strtolower($classlikes->getUnAliasedName(
                        $fq_classlike_name_lc
                    ));

                    // even though we've checked this above, calling the method invalidates it
                    if (isset($this->classlike_files[$fq_classlike_name_lc])) {
                        $file_path = $this->classlike_files[$fq_classlike_name_lc];
                        $this->files_to_scan[$file_path] = $file_path;
                        if (isset($this->classes_to_deep_scan[$fq_classlike_name_lc])) {
                            unset($this->classes_to_deep_scan[$fq_classlike_name_lc]);
                            $this->files_to_deep_scan[$file_path] = $file_path;
                        }
                    }
                } elseif ($this->store_scan_failure[$fq_classlike_name]) {
                    $classlikes->registerMissingClassLike($fq_classlike_name_lc);
                }
            } elseif (isset($this->classes_to_deep_scan[$fq_classlike_name_lc])
                && !isset($this->deep_scanned_classlike_files[$fq_classlike_name_lc])
            ) {
                $file_path = $this->classlike_files[$fq_classlike_name_lc];
                $this->files_to_scan[$file_path] = $file_path;
                unset($this->classes_to_deep_scan[$fq_classlike_name_lc]);
                $this->files_to_deep_scan[$file_path] = $file_path;
                $this->deep_scanned_classlike_files[$fq_classlike_name_lc] = true;
            }
        }
    }

    /**
     * @param  array<string, class-string<FileScanner>>  $filetype_scanners
     */
    private function scanFile(
        string $file_path,
        array $filetype_scanners,
        bool $will_analyze = false
    ): FileScanner {
        $file_scanner = $this->getScannerForPath($file_path, $filetype_scanners, $will_analyze);

        if (isset($this->scanned_files[$file_path])
            && (!$will_analyze || $this->scanned_files[$file_path])
        ) {
            throw new \UnexpectedValueException('Should not be rescanning ' . $file_path);
        }

        $file_contents = $this->file_provider->getContents($file_path);

        $from_cache = $this->file_storage_provider->has($file_path, $file_contents);

        if (!$from_cache) {
            $this->file_storage_provider->create($file_path);
        }

        $this->scanned_files[$file_path] = $will_analyze;

        $file_storage = $this->file_storage_provider->get($file_path);

        $file_scanner->scan(
            $this->codebase,
            $file_storage,
            $from_cache,
            $this->progress
        );

        if (!$from_cache) {
            if (!$file_storage->has_visitor_issues && $this->file_storage_provider->cache) {
                $this->file_storage_provider->cache->writeToCache($file_storage, $file_contents);
            }
        } else {
            $this->codebase->statements_provider->setUnchangedFile($file_path);

            foreach ($file_storage->required_file_paths as $required_file_path) {
                if ($will_analyze) {
                    $this->addFileToDeepScan($required_file_path);
                } else {
                    $this->addFileToShallowScan($required_file_path);
                }
            }

            foreach ($file_storage->classlikes_in_file as $fq_classlike_name) {
                $this->codebase->exhumeClassLikeStorage(strtolower($fq_classlike_name), $file_path);
            }

            foreach ($file_storage->required_classes as $fq_classlike_name) {
                $this->queueClassLikeForScanning($fq_classlike_name, $will_analyze, false);
            }

            foreach ($file_storage->required_interfaces as $fq_classlike_name) {
                $this->queueClassLikeForScanning($fq_classlike_name, false, false);
            }

            foreach ($file_storage->referenced_classlikes as $fq_classlike_name) {
                $this->queueClassLikeForScanning($fq_classlike_name, false, false);
            }

            if ($this->codebase->register_autoload_files) {
                foreach ($file_storage->functions as $function_storage) {
                    if ($function_storage->cased_name
                        && !$this->codebase->functions->hasStubbedFunction($function_storage->cased_name)
                    ) {
                        $this->codebase->functions->addGlobalFunction(
                            $function_storage->cased_name,
                            $function_storage
                        );
                    }
                }

                foreach ($file_storage->constants as $name => $type) {
                    $this->codebase->addGlobalConstantType($name, $type);
                }
            }

            foreach ($file_storage->classlike_aliases as $aliased_name => $unaliased_name) {
                $this->codebase->classlikes->addClassAlias($unaliased_name, $aliased_name);
            }
        }

        return $file_scanner;
    }

    /**
     * @param  array<string, class-string<FileScanner>>  $filetype_scanners
     */
    private function getScannerForPath(
        string $file_path,
        array $filetype_scanners,
        bool $will_analyze = false
    ): FileScanner {
        $path_parts = explode(DIRECTORY_SEPARATOR, $file_path);
        $file_name_parts = explode('.', array_pop($path_parts));
        $extension = count($file_name_parts) > 1 ? array_pop($file_name_parts) : null;

        $file_name = $this->config->shortenFileName($file_path);

        if (isset($filetype_scanners[$extension])) {
            return new $filetype_scanners[$extension]($file_path, $file_name, $will_analyze);
        }

        return new FileScanner($file_path, $file_name, $will_analyze);
    }

    /**
     * @return array<string, bool>
     */
    public function getScannedFiles(): array
    {
        return $this->scanned_files;
    }

    /**
     * Checks whether a class exists, and if it does then records what file it's in
     * for later checking
     */
    private function fileExistsForClassLike(ClassLikes $classlikes, string $fq_class_name): bool
    {
        $fq_class_name_lc = strtolower($fq_class_name);

        if (isset($this->classlike_files[$fq_class_name_lc])) {
            return true;
        }

        if ($fq_class_name === 'self') {
            return false;
        }

        if (isset($this->existing_classlikes_lc[$fq_class_name_lc])) {
            throw new \InvalidArgumentException('Why are you asking about a builtin class?');
        }

        $composer_file_path = $this->config->getComposerFilePathForClassLike($fq_class_name);

        if ($composer_file_path && file_exists($composer_file_path)) {
            $this->progress->debug('Using composer to locate file for ' . $fq_class_name . "\n");

            $classlikes->addFullyQualifiedClassLikeName(
                $fq_class_name_lc,
                realpath($composer_file_path)
            );

            return true;
        }

        $old_level = error_reporting();

        $this->progress->setErrorReporting();

        try {
            $this->progress->debug('Using reflection to locate file for ' . $fq_class_name . "\n");

            /** @psalm-suppress ArgumentTypeCoercion */
            $reflected_class = new \ReflectionClass($fq_class_name);
        } catch (\Throwable $e) {
            error_reporting($old_level);

            // do not cache any results here (as case-sensitive filenames can screw things up)

            return false;
        }

        error_reporting($old_level);

        $file_path = (string)$reflected_class->getFileName();

        // if the file was autoloaded but exists in evaled code only, return false
        if (!file_exists($file_path)) {
            return false;
        }

        $new_fq_class_name = $reflected_class->getName();
        $new_fq_class_name_lc = strtolower($new_fq_class_name);

        if ($new_fq_class_name_lc !== $fq_class_name_lc) {
            $classlikes->addClassAlias($new_fq_class_name, $fq_class_name_lc);
            $fq_class_name_lc = $new_fq_class_name_lc;
        }

        $fq_class_name = $new_fq_class_name;
        $classlikes->addFullyQualifiedClassLikeName($fq_class_name_lc);

        if ($reflected_class->isInterface()) {
            $classlikes->addFullyQualifiedInterfaceName($fq_class_name, $file_path);
        } elseif ($reflected_class->isTrait()) {
            $classlikes->addFullyQualifiedTraitName($fq_class_name, $file_path);
        } else {
            $classlikes->addFullyQualifiedClassName($fq_class_name, $file_path);
        }

        return true;
    }

    /**
     * @return ThreadData
     */
    public function getThreadData(): array
    {
        return [
            $this->files_to_scan,
            $this->files_to_deep_scan,
            $this->classes_to_scan,
            $this->classes_to_deep_scan,
            $this->store_scan_failure,
            $this->classlike_files,
            $this->deep_scanned_classlike_files,
            $this->scanned_files,
            $this->reflected_classlikes_lc,
        ];
    }

    /**
     * @param ThreadData $thread_data
     *
     */
    public function addThreadData(array $thread_data): void
    {
        [
            $files_to_scan,
            $files_to_deep_scan,
            $classes_to_scan,
            $classes_to_deep_scan,
            $store_scan_failure,
            $classlike_files,
            $deep_scanned_classlike_files,
            $scanned_files,
            $reflected_classlikes_lc
        ] = $thread_data;

        $this->files_to_scan = array_merge($files_to_scan, $this->files_to_scan);
        $this->files_to_deep_scan = array_merge($files_to_deep_scan, $this->files_to_deep_scan);
        $this->classes_to_scan = array_merge($classes_to_scan, $this->classes_to_scan);
        $this->classes_to_deep_scan = array_merge($classes_to_deep_scan, $this->classes_to_deep_scan);
        $this->store_scan_failure = array_merge($store_scan_failure, $this->store_scan_failure);
        $this->classlike_files = array_merge($classlike_files, $this->classlike_files);
        $this->deep_scanned_classlike_files = array_merge(
            $deep_scanned_classlike_files,
            $this->deep_scanned_classlike_files
        );
        $this->scanned_files = array_merge($scanned_files, $this->scanned_files);
        $this->reflected_classlikes_lc = array_merge($reflected_classlikes_lc, $this->reflected_classlikes_lc);
    }

    public function isForked(): void
    {
        $this->is_forked = true;
    }
}
