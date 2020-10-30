<?php
namespace Psalm\Internal\Codebase;

use function array_filter;
use function array_intersect_key;
use function array_merge;
use function count;
use function explode;
use InvalidArgumentException;
use function number_format;
use function pathinfo;
use PhpParser;
use function preg_replace;
use Psalm\Config;
use Psalm\FileManipulation;
use Psalm\Internal\Analyzer\IssueData;
use Psalm\Internal\Analyzer\FileAnalyzer;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\FileManipulation\ClassDocblockManipulator;
use Psalm\Internal\FileManipulation\FileManipulationBuffer;
use Psalm\Internal\FileManipulation\FunctionDocblockManipulator;
use Psalm\Internal\FileManipulation\PropertyDocblockManipulator;
use Psalm\Internal\Provider\FileProvider;
use Psalm\Internal\Provider\FileStorageProvider;
use Psalm\IssueBuffer;
use Psalm\Progress\Progress;
use Psalm\Internal\Codebase\TaintFlowGraph;
use function strpos;
use function substr;
use function usort;
use function array_values;
use const PATHINFO_EXTENSION;

/**
 * @psalm-type  TaggedCodeType = array<int, array{0: int, 1: non-empty-string}>
 *
 * @psalm-type  FileMapType = array{
 *      0: TaggedCodeType,
 *      1: TaggedCodeType,
 *      2: array<int, array{0: int, 1: non-empty-string, 2: int}>
 * }
 *
 * @psalm-type  WorkerData = array{
 *      issues: array<string, list<IssueData>>,
 *      fixable_issue_counts: array<string, int>,
 *      nonmethod_references_to_classes: array<string, array<string,bool>>,
 *      method_references_to_classes: array<string, array<string,bool>>,
 *      file_references_to_class_members: array<string, array<string,bool>>,
 *      file_references_to_missing_class_members: array<string, array<string,bool>>,
 *      mixed_counts: array<string, array{0: int, 1: int}>,
 *      mixed_member_names: array<string, array<string, bool>>,
 *      function_timings: array<string, float>,
 *      file_manipulations: array<string, FileManipulation[]>,
 *      method_references_to_class_members: array<string, array<string,bool>>,
 *      method_references_to_missing_class_members: array<string, array<string,bool>>,
 *      method_param_uses: array<string, array<int, array<string, bool>>>,
 *      analyzed_methods: array<string, array<string, int>>,
 *      file_maps: array<string, FileMapType>,
 *      class_locations: array<string, array<int, \Psalm\CodeLocation>>,
 *      class_method_locations: array<string, array<int, \Psalm\CodeLocation>>,
 *      class_property_locations: array<string, array<int, \Psalm\CodeLocation>>,
 *      possible_method_param_types: array<string, array<int, \Psalm\Type\Union>>,
 *      taint_data: ?TaintFlowGraph,
 *      unused_suppressions: array<string, array<int, int>>,
 *      used_suppressions: array<string, array<int, bool>>,
 *      function_docblock_manipulators: array<string, array<int, FunctionDocblockManipulator>>,
 *      mutable_classes: array<string, bool>,
 * }
 */

/**
 * @internal
 *
 * Called in the analysis phase of Psalm's execution
 */
class Analyzer
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var FileProvider
     */
    private $file_provider;

    /**
     * @var FileStorageProvider
     */
    private $file_storage_provider;

    /**
     * @var Progress
     */
    private $progress;

    /**
     * Used to store counts of mixed vs non-mixed variables
     *
     * @var array<string, array{0: int, 1: int}>
     */
    private $mixed_counts = [];

    /**
     * Used to store member names of mixed property/method access
     *
     * @var array<string, array<string, bool>>
     */
    private $mixed_member_names = [];

    /**
     * @var bool
     */
    private $count_mixed = true;

    /**
     * Used to store debug performance data
     *
     * @var array<string, float>
     */
    private $function_timings = [];

    /**
     * We analyze more files than we necessarily report errors in
     *
     * @var array<string, string>
     */
    private $files_to_analyze = [];

    /**
     * We can show analysis results on more files than we analyze
     * because the results can be cached
     *
     * @var array<string, string>
     */
    private $files_with_analysis_results = [];

    /**
     * We may update fewer files than we analyse (i.e. for dead code detection)
     *
     * @var array<string>|null
     */
    private $files_to_update = null;

    /**
     * @var array<string, array<string, int>>
     */
    private $analyzed_methods = [];

    /**
     * @var array<string, array<int, IssueData>>
     */
    private $existing_issues = [];

    /**
     * @var array<string, array<int, array{0: int, 1: non-empty-string}>>
     */
    private $reference_map = [];

    /**
     * @var array<string, array<int, array{0: int, 1: non-empty-string}>>
     */
    private $type_map = [];

    /**
     * @var array<string, array<int, array{0: int, 1: non-empty-string, 2: int}>>
     */
    private $argument_map = [];

    /**
     * @var array<string, array<int, \Psalm\Type\Union>>
     */
    public $possible_method_param_types = [];

    /**
     * @var array<string, bool>
     */
    public $mutable_classes = [];

    public function __construct(
        Config $config,
        FileProvider $file_provider,
        FileStorageProvider $file_storage_provider,
        Progress $progress
    ) {
        $this->config = $config;
        $this->file_provider = $file_provider;
        $this->file_storage_provider = $file_storage_provider;
        $this->progress = $progress;
    }

    /**
     * @param array<string, string> $files_to_analyze
     *
     */
    public function addFilesToAnalyze(array $files_to_analyze): void
    {
        $this->files_to_analyze += $files_to_analyze;
        $this->files_with_analysis_results += $files_to_analyze;
    }

    /**
     * @param array<string, string> $files_to_analyze
     *
     */
    public function addFilesToShowResults(array $files_to_analyze): void
    {
        $this->files_with_analysis_results += $files_to_analyze;
    }

    /**
     * @param array<string> $files_to_update
     *
     */
    public function setFilesToUpdate(array $files_to_update): void
    {
        $this->files_to_update = $files_to_update;
    }

    public function canReportIssues(string $file_path): bool
    {
        return isset($this->files_with_analysis_results[$file_path]);
    }

    /**
     * @param  array<string, class-string<FileAnalyzer>> $filetype_analyzers
     */
    private function getFileAnalyzer(
        ProjectAnalyzer $project_analyzer,
        string $file_path,
        array $filetype_analyzers
    ): FileAnalyzer {
        $extension = pathinfo($file_path, PATHINFO_EXTENSION);

        $file_name = $this->config->shortenFileName($file_path);

        if (isset($filetype_analyzers[$extension])) {
            $file_analyzer = new $filetype_analyzers[$extension]($project_analyzer, $file_path, $file_name);
        } else {
            $file_analyzer = new FileAnalyzer($project_analyzer, $file_path, $file_name);
        }

        $this->progress->debug('Getting ' . $file_path . "\n");

        return $file_analyzer;
    }

    public function analyzeFiles(
        ProjectAnalyzer $project_analyzer,
        int $pool_size,
        bool $alter_code,
        bool $consolidate_analyzed_data = false
    ): void {
        $this->loadCachedResults($project_analyzer);

        $codebase = $project_analyzer->getCodebase();

        if ($alter_code) {
            $project_analyzer->interpretRefactors();
        }

        $this->files_to_analyze = array_filter(
            $this->files_to_analyze,
            function (string $file_path) : bool {
                return $this->file_provider->fileExists($file_path);
            }
        );

        $this->doAnalysis($project_analyzer, $pool_size);

        $scanned_files = $codebase->scanner->getScannedFiles();

        if ($codebase->taint_flow_graph) {
            $codebase->taint_flow_graph->connectSinksAndSources();
        }

        $this->progress->finish();

        if ($consolidate_analyzed_data) {
            $project_analyzer->consolidateAnalyzedData();
        }

        foreach (IssueBuffer::getIssuesData() as $file_path => $file_issues) {
            $codebase->file_reference_provider->clearExistingIssuesForFile($file_path);

            foreach ($file_issues as $issue_data) {
                $codebase->file_reference_provider->addIssue($file_path, $issue_data);
            }
        }

        $codebase->file_reference_provider->updateReferenceCache($codebase, $scanned_files);

        if ($codebase->track_unused_suppressions) {
            IssueBuffer::processUnusedSuppressions($codebase->file_provider);
        }

        $codebase->file_reference_provider->setAnalyzedMethods($this->analyzed_methods);
        $codebase->file_reference_provider->setFileMaps($this->getFileMaps());
        $codebase->file_reference_provider->setTypeCoverage($this->mixed_counts);
        $codebase->file_reference_provider->updateReferenceCache($codebase, $scanned_files);

        if ($codebase->diff_methods) {
            $codebase->statements_provider->resetDiffs();
        }

        if ($alter_code) {
            $this->progress->startAlteringFiles();

            $project_analyzer->prepareMigration();

            $files_to_update = $this->files_to_update !== null ? $this->files_to_update : $this->files_to_analyze;

            foreach ($files_to_update as $file_path) {
                $this->updateFile($file_path, $project_analyzer->dry_run);
            }

            $project_analyzer->migrateCode();
        }
    }

    private function doAnalysis(ProjectAnalyzer $project_analyzer, int $pool_size) : void
    {
        $this->progress->start(count($this->files_to_analyze));

        \ksort($this->files_to_analyze);

        $codebase = $project_analyzer->getCodebase();

        $filetype_analyzers = $this->config->getFiletypeAnalyzers();

        $analysis_worker =
            /**
             * @return list<\Psalm\Internal\Analyzer\IssueData>
             */
            function (int $_, string $file_path) use ($project_analyzer, $filetype_analyzers): array {
                $file_analyzer = $this->getFileAnalyzer($project_analyzer, $file_path, $filetype_analyzers);

                $this->progress->debug('Analyzing ' . $file_analyzer->getFilePath() . "\n");

                $file_analyzer->analyze(null);
                $file_analyzer->context = null;
                $file_analyzer->clearSourceBeforeDestruction();
                unset($file_analyzer);

                return IssueBuffer::getIssuesDataForFile($file_path);
            };

        $task_done_closure =
            /**
             * @param array<IssueData> $issues
             */
            function (array $issues): void {
                $has_error = false;
                $has_info = false;

                foreach ($issues as $issue) {
                    if ($issue->severity === 'error') {
                        $has_error = true;
                        break;
                    }

                    if ($issue->severity === 'info') {
                        $has_info = true;
                    }
                }

                $this->progress->taskDone($has_error ? 2 : ($has_info ? 1 : 0));
            };

        if ($pool_size > 1 && count($this->files_to_analyze) > $pool_size) {
            $shuffle_count = $pool_size + 1;

            $file_paths = \array_values($this->files_to_analyze);

            $count = count($file_paths);
            $middle = \intdiv($count, $shuffle_count);
            $remainder = $count % $shuffle_count;

            $new_file_paths = [];

            for ($i = 0; $i < $shuffle_count; $i++) {
                for ($j = 0; $j < $middle; $j++) {
                    if ($j * $shuffle_count + $i < $count) {
                        $new_file_paths[] = $file_paths[$j * $shuffle_count + $i];
                    }
                }

                if ($remainder) {
                    $new_file_paths[] = $file_paths[$middle * $shuffle_count + $remainder - 1];
                    $remainder--;
                }
            }

            $process_file_paths = [];

            $i = 0;

            foreach ($new_file_paths as $file_path) {
                $process_file_paths[$i % $pool_size][] = $file_path;
                ++$i;
            }

            // Run analysis one file at a time, splitting the set of
            // files up among a given number of child processes.
            $pool = new \Psalm\Internal\Fork\Pool(
                $process_file_paths,
                function (): void {
                    $project_analyzer = ProjectAnalyzer::getInstance();
                    $codebase = $project_analyzer->getCodebase();

                    $file_reference_provider = $codebase->file_reference_provider;

                    if ($codebase->taint_flow_graph) {
                        $codebase->taint_flow_graph = new TaintFlowGraph();
                    }

                    $file_reference_provider->setNonMethodReferencesToClasses([]);
                    $file_reference_provider->setCallingMethodReferencesToClassMembers([]);
                    $file_reference_provider->setFileReferencesToClassMembers([]);
                    $file_reference_provider->setCallingMethodReferencesToMissingClassMembers([]);
                    $file_reference_provider->setFileReferencesToMissingClassMembers([]);
                    $file_reference_provider->setReferencesToMixedMemberNames([]);
                    $file_reference_provider->setMethodParamUses([]);
                },
                $analysis_worker,
                /** @return WorkerData */
                function () {
                    $project_analyzer = ProjectAnalyzer::getInstance();
                    $codebase = $project_analyzer->getCodebase();
                    $analyzer = $codebase->analyzer;
                    $file_reference_provider = $codebase->file_reference_provider;

                    $this->progress->debug('Gathering data for forked process' . "\n");

                    // @codingStandardsIgnoreStart
                    return [
                        'issues' => IssueBuffer::getIssuesData(),
                        'fixable_issue_counts' => IssueBuffer::getFixableIssues(),
                        'nonmethod_references_to_classes' => $file_reference_provider->getAllNonMethodReferencesToClasses(),
                        'method_references_to_classes' => $file_reference_provider->getAllMethodReferencesToClasses(),
                        'file_references_to_class_members' => $file_reference_provider->getAllFileReferencesToClassMembers(),
                        'method_references_to_class_members' => $file_reference_provider->getAllMethodReferencesToClassMembers(),
                        'file_references_to_missing_class_members' => $file_reference_provider->getAllFileReferencesToMissingClassMembers(),
                        'method_references_to_missing_class_members' => $file_reference_provider->getAllMethodReferencesToMissingClassMembers(),
                        'method_param_uses' => $file_reference_provider->getAllMethodParamUses(),
                        'mixed_member_names' => $analyzer->getMixedMemberNames(),
                        'file_manipulations' => FileManipulationBuffer::getAll(),
                        'mixed_counts' => $analyzer->getMixedCounts(),
                        'function_timings' => $analyzer->getFunctionTimings(),
                        'analyzed_methods' => $analyzer->getAnalyzedMethods(),
                        'file_maps' => $analyzer->getFileMaps(),
                        'class_locations' => $file_reference_provider->getAllClassLocations(),
                        'class_method_locations' => $file_reference_provider->getAllClassMethodLocations(),
                        'class_property_locations' => $file_reference_provider->getAllClassPropertyLocations(),
                        'possible_method_param_types' => $analyzer->getPossibleMethodParamTypes(),
                        'taint_data' => $codebase->taint_flow_graph,
                        'unused_suppressions' => $codebase->track_unused_suppressions ? IssueBuffer::getUnusedSuppressions() : [],
                        'used_suppressions' => $codebase->track_unused_suppressions ? IssueBuffer::getUsedSuppressions() : [],
                        'function_docblock_manipulators' => FunctionDocblockManipulator::getManipulators(),
                        'mutable_classes' => $codebase->analyzer->mutable_classes,
                    ];
                    // @codingStandardsIgnoreEnd
                },
                $task_done_closure
            );

            $this->progress->debug('Forking analysis' . "\n");

            // Wait for all tasks to complete and collect the results.
            /**
             * @var array<int, WorkerData>
             */
            $forked_pool_data = $pool->wait();

            $this->progress->debug('Collecting forked analysis results' . "\n");

            foreach ($forked_pool_data as $pool_data) {
                IssueBuffer::addIssues($pool_data['issues']);
                IssueBuffer::addFixableIssues($pool_data['fixable_issue_counts']);

                if ($codebase->track_unused_suppressions) {
                    IssueBuffer::addUnusedSuppressions($pool_data['unused_suppressions']);
                    IssueBuffer::addUsedSuppressions($pool_data['used_suppressions']);
                }

                if ($codebase->taint_flow_graph && $pool_data['taint_data']) {
                    $codebase->taint_flow_graph->addGraph($pool_data['taint_data']);
                }

                $codebase->file_reference_provider->addNonMethodReferencesToClasses(
                    $pool_data['nonmethod_references_to_classes']
                );
                $codebase->file_reference_provider->addMethodReferencesToClasses(
                    $pool_data['method_references_to_classes']
                );
                $codebase->file_reference_provider->addFileReferencesToClassMembers(
                    $pool_data['file_references_to_class_members']
                );
                $codebase->file_reference_provider->addMethodReferencesToClassMembers(
                    $pool_data['method_references_to_class_members']
                );
                $codebase->file_reference_provider->addFileReferencesToMissingClassMembers(
                    $pool_data['file_references_to_missing_class_members']
                );
                $codebase->file_reference_provider->addMethodReferencesToMissingClassMembers(
                    $pool_data['method_references_to_missing_class_members']
                );
                $codebase->file_reference_provider->addMethodParamUses(
                    $pool_data['method_param_uses']
                );
                $this->addMixedMemberNames(
                    $pool_data['mixed_member_names']
                );
                $this->function_timings += $pool_data['function_timings'];
                $codebase->file_reference_provider->addClassLocations(
                    $pool_data['class_locations']
                );
                $codebase->file_reference_provider->addClassMethodLocations(
                    $pool_data['class_method_locations']
                );
                $codebase->file_reference_provider->addClassPropertyLocations(
                    $pool_data['class_property_locations']
                );

                $this->mutable_classes = array_merge($this->mutable_classes, $pool_data['mutable_classes']);

                FunctionDocblockManipulator::addManipulators($pool_data['function_docblock_manipulators']);

                $this->analyzed_methods = array_merge($pool_data['analyzed_methods'], $this->analyzed_methods);

                foreach ($pool_data['mixed_counts'] as $file_path => [$mixed_count, $nonmixed_count]) {
                    if (!isset($this->mixed_counts[$file_path])) {
                        $this->mixed_counts[$file_path] = [$mixed_count, $nonmixed_count];
                    } else {
                        $this->mixed_counts[$file_path][0] += $mixed_count;
                        $this->mixed_counts[$file_path][1] += $nonmixed_count;
                    }
                }

                foreach ($pool_data['possible_method_param_types'] as $declaring_method_id => $possible_param_types) {
                    if (!isset($this->possible_method_param_types[$declaring_method_id])) {
                        $this->possible_method_param_types[$declaring_method_id] = $possible_param_types;
                    } else {
                        foreach ($possible_param_types as $offset => $possible_param_type) {
                            if (!isset($this->possible_method_param_types[$declaring_method_id][$offset])) {
                                $this->possible_method_param_types[$declaring_method_id][$offset]
                                    = $possible_param_type;
                            } else {
                                $this->possible_method_param_types[$declaring_method_id][$offset]
                                    = \Psalm\Type::combineUnionTypes(
                                        $this->possible_method_param_types[$declaring_method_id][$offset],
                                        $possible_param_type,
                                        $codebase
                                    );
                            }
                        }
                    }
                }

                foreach ($pool_data['file_manipulations'] as $file_path => $manipulations) {
                    FileManipulationBuffer::add($file_path, $manipulations);
                }

                foreach ($pool_data['file_maps'] as $file_path => $file_maps) {
                    [$reference_map, $type_map, $argument_map] = $file_maps;
                    $this->reference_map[$file_path] = $reference_map;
                    $this->type_map[$file_path] = $type_map;
                    $this->argument_map[$file_path] = $argument_map;
                }
            }

            if ($pool->didHaveError()) {
                exit(1);
            }
        } else {
            $i = 0;

            foreach ($this->files_to_analyze as $file_path => $_) {
                $analysis_worker($i, $file_path);
                ++$i;

                $issues = IssueBuffer::getIssuesDataForFile($file_path);
                $task_done_closure($issues);
            }
        }
    }

    public function loadCachedResults(ProjectAnalyzer $project_analyzer): void
    {
        $codebase = $project_analyzer->getCodebase();

        if ($codebase->diff_methods) {
            $this->analyzed_methods = $codebase->file_reference_provider->getAnalyzedMethods();
            $this->existing_issues = $codebase->file_reference_provider->getExistingIssues();
            $file_maps = $codebase->file_reference_provider->getFileMaps();

            foreach ($file_maps as $file_path => [$reference_map, $type_map, $argument_map]) {
                $this->reference_map[$file_path] = $reference_map;
                $this->type_map[$file_path] = $type_map;
                $this->argument_map[$file_path] = $argument_map;
            }
        }

        $statements_provider = $codebase->statements_provider;
        $file_reference_provider = $codebase->file_reference_provider;

        $changed_members = $statements_provider->getChangedMembers();
        $unchanged_signature_members = $statements_provider->getUnchangedSignatureMembers();

        $diff_map = $statements_provider->getDiffMap();

        $method_references_to_class_members
            = $file_reference_provider->getAllMethodReferencesToClassMembers();
        $method_references_to_missing_class_members =
            $file_reference_provider->getAllMethodReferencesToMissingClassMembers();

        $all_referencing_methods = $method_references_to_class_members + $method_references_to_missing_class_members;

        $nonmethod_references_to_classes = $file_reference_provider->getAllNonMethodReferencesToClasses();

        $method_references_to_classes = $file_reference_provider->getAllMethodReferencesToClasses();

        $method_param_uses = $file_reference_provider->getAllMethodParamUses();

        $file_references_to_class_members
            = $file_reference_provider->getAllFileReferencesToClassMembers();
        $file_references_to_missing_class_members
            = $file_reference_provider->getAllFileReferencesToMissingClassMembers();

        $references_to_mixed_member_names = $file_reference_provider->getAllReferencesToMixedMemberNames();

        $this->mixed_counts = $file_reference_provider->getTypeCoverage();

        foreach ($changed_members as $file_path => $members_by_file) {
            foreach ($members_by_file as $changed_member => $_) {
                if (!strpos($changed_member, '&')) {
                    continue;
                }

                [$base_class, $trait] = explode('&', $changed_member);

                foreach ($all_referencing_methods as $member_id => $_) {
                    if (strpos($member_id, $base_class . '::') !== 0) {
                        continue;
                    }

                    $member_bit = substr($member_id, \strlen($base_class) + 2);

                    if (isset($all_referencing_methods[$trait . '::' . $member_bit])) {
                        $changed_members[$file_path][$member_id] = true;
                    }
                }
            }
        }

        $newly_invalidated_methods = [];

        foreach ($unchanged_signature_members as $file_unchanged_signature_members) {
            $newly_invalidated_methods = array_merge($newly_invalidated_methods, $file_unchanged_signature_members);

            foreach ($file_unchanged_signature_members as $unchanged_signature_member_id => $_) {
                // also check for things that might invalidate constructor property initialisation
                if (isset($all_referencing_methods[$unchanged_signature_member_id])) {
                    foreach ($all_referencing_methods[$unchanged_signature_member_id] as $referencing_method_id => $_) {
                        if (substr($referencing_method_id, -13) === '::__construct') {
                            $referencing_base_classlike = explode('::', $referencing_method_id)[0];
                            $unchanged_signature_classlike = explode('::', $unchanged_signature_member_id)[0];

                            if ($referencing_base_classlike === $unchanged_signature_classlike) {
                                $newly_invalidated_methods[$referencing_method_id] = true;
                            } else {
                                try {
                                    $referencing_storage = $codebase->classlike_storage_provider->get(
                                        $referencing_base_classlike
                                    );
                                } catch (InvalidArgumentException $_) {
                                    // Workaround for #3671
                                    $newly_invalidated_methods[$referencing_method_id] = true;
                                    $referencing_storage = null;
                                }

                                if (isset($referencing_storage->used_traits[$unchanged_signature_classlike])
                                    || isset($referencing_storage->parent_classes[$unchanged_signature_classlike])
                                ) {
                                    $newly_invalidated_methods[$referencing_method_id] = true;
                                }
                            }
                        }
                    }
                }
            }
        }

        foreach ($changed_members as $file_changed_members) {
            foreach ($file_changed_members as $member_id => $_) {
                $newly_invalidated_methods[$member_id] = true;

                if (isset($all_referencing_methods[$member_id])) {
                    $newly_invalidated_methods = array_merge(
                        $all_referencing_methods[$member_id],
                        $newly_invalidated_methods
                    );
                }

                unset(
                    $method_references_to_class_members[$member_id],
                    $file_references_to_class_members[$member_id],
                    $method_references_to_missing_class_members[$member_id],
                    $file_references_to_missing_class_members[$member_id],
                    $references_to_mixed_member_names[$member_id],
                    $method_param_uses[$member_id]
                );

                $member_stub = preg_replace('/::.*$/', '::*', $member_id);

                if (isset($all_referencing_methods[$member_stub])) {
                    $newly_invalidated_methods = array_merge(
                        $all_referencing_methods[$member_stub],
                        $newly_invalidated_methods
                    );
                }
            }
        }

        foreach ($newly_invalidated_methods as $method_id => $_) {
            foreach ($method_references_to_class_members as $i => $_) {
                unset($method_references_to_class_members[$i][$method_id]);
            }

            foreach ($method_references_to_classes as $i => $_) {
                unset($method_references_to_classes[$i][$method_id]);
            }

            foreach ($method_references_to_missing_class_members as $i => $_) {
                unset($method_references_to_missing_class_members[$i][$method_id]);
            }

            foreach ($references_to_mixed_member_names as $i => $_) {
                unset($references_to_mixed_member_names[$i][$method_id]);
            }

            foreach ($method_param_uses as $i => $_) {
                foreach ($method_param_uses[$i] as $j => $_) {
                    unset($method_param_uses[$i][$j][$method_id]);
                }
            }
        }

        foreach ($this->analyzed_methods as $file_path => $analyzed_methods) {
            foreach ($analyzed_methods as $correct_method_id => $_) {
                $trait_safe_method_id = $correct_method_id;

                $correct_method_ids = explode('&', $correct_method_id);

                $correct_method_id = $correct_method_ids[0];

                if (isset($newly_invalidated_methods[$correct_method_id])
                    || (isset($correct_method_ids[1])
                        && isset($newly_invalidated_methods[$correct_method_ids[1]]))
                ) {
                    unset($this->analyzed_methods[$file_path][$trait_safe_method_id]);
                }
            }
        }

        $this->shiftFileOffsets($diff_map);

        foreach ($this->files_to_analyze as $file_path) {
            $file_reference_provider->clearExistingIssuesForFile($file_path);
            $file_reference_provider->clearExistingFileMapsForFile($file_path);

            $this->setMixedCountsForFile($file_path, [0, 0]);

            foreach ($file_references_to_class_members as $i => $_) {
                unset($file_references_to_class_members[$i][$file_path]);
            }

            foreach ($nonmethod_references_to_classes as $i => $_) {
                unset($nonmethod_references_to_classes[$i][$file_path]);
            }

            foreach ($references_to_mixed_member_names as $i => $_) {
                unset($references_to_mixed_member_names[$i][$file_path]);
            }

            foreach ($file_references_to_missing_class_members as $i => $_) {
                unset($file_references_to_missing_class_members[$i][$file_path]);
            }
        }

        foreach ($this->existing_issues as $file_path => $issues) {
            if (!isset($this->files_to_analyze[$file_path])) {
                unset($this->existing_issues[$file_path]);

                if ($this->file_provider->fileExists($file_path)) {
                    IssueBuffer::addIssues([$file_path => array_values($issues)]);
                }
            }
        }

        $method_references_to_class_members = array_filter(
            $method_references_to_class_members
        );

        $method_references_to_missing_class_members = array_filter(
            $method_references_to_missing_class_members
        );

        $file_references_to_class_members = array_filter(
            $file_references_to_class_members
        );

        $file_references_to_missing_class_members = array_filter(
            $file_references_to_missing_class_members
        );

        $references_to_mixed_member_names = array_filter(
            $references_to_mixed_member_names
        );

        $nonmethod_references_to_classes = array_filter(
            $nonmethod_references_to_classes
        );

        $method_references_to_classes = array_filter(
            $method_references_to_classes
        );

        $method_param_uses = array_filter(
            $method_param_uses
        );

        $file_reference_provider->setCallingMethodReferencesToClassMembers(
            $method_references_to_class_members
        );

        $file_reference_provider->setFileReferencesToClassMembers(
            $file_references_to_class_members
        );

        $file_reference_provider->setCallingMethodReferencesToMissingClassMembers(
            $method_references_to_missing_class_members
        );

        $file_reference_provider->setFileReferencesToMissingClassMembers(
            $file_references_to_missing_class_members
        );

        $file_reference_provider->setReferencesToMixedMemberNames(
            $references_to_mixed_member_names
        );

        $file_reference_provider->setCallingMethodReferencesToClasses(
            $method_references_to_classes
        );

        $file_reference_provider->setNonMethodReferencesToClasses(
            $nonmethod_references_to_classes
        );

        $file_reference_provider->setMethodParamUses(
            $method_param_uses
        );
    }

    /**
     * @param array<string, array<int, array{int, int, int, int}>> $diff_map
     *
     */
    public function shiftFileOffsets(array $diff_map): void
    {
        foreach ($this->existing_issues as $file_path => &$file_issues) {
            if (!isset($this->analyzed_methods[$file_path])) {
                continue;
            }

            $file_diff_map = $diff_map[$file_path] ?? [];

            if (!$file_diff_map) {
                continue;
            }

            $first_diff_offset = $file_diff_map[0][0];
            $last_diff_offset = $file_diff_map[count($file_diff_map) - 1][1];

            foreach ($file_issues as $i => &$issue_data) {
                if ($issue_data->to < $first_diff_offset || $issue_data->from > $last_diff_offset) {
                    unset($file_issues[$i]);
                    continue;
                }

                $matched = false;

                foreach ($file_diff_map as [$from, $to, $file_offset, $line_offset]) {
                    if ($issue_data->from >= $from
                        && $issue_data->from <= $to
                        && !$matched
                    ) {
                        $issue_data->from += $file_offset;
                        $issue_data->to += $file_offset;
                        $issue_data->snippet_from += $file_offset;
                        $issue_data->snippet_to += $file_offset;
                        $issue_data->line_from += $line_offset;
                        $issue_data->line_to += $line_offset;
                        $matched = true;
                    }
                }

                if (!$matched) {
                    unset($file_issues[$i]);
                }
            }
        }

        foreach ($this->reference_map as $file_path => &$reference_map) {
            if (!isset($this->analyzed_methods[$file_path])) {
                unset($this->reference_map[$file_path]);
                continue;
            }

            $file_diff_map = $diff_map[$file_path] ?? [];

            if (!$file_diff_map) {
                continue;
            }

            $first_diff_offset = $file_diff_map[0][0];
            $last_diff_offset = $file_diff_map[count($file_diff_map) - 1][1];

            foreach ($reference_map as $reference_from => [$reference_to, $tag]) {
                if ($reference_to < $first_diff_offset || $reference_from > $last_diff_offset) {
                    continue;
                }

                foreach ($file_diff_map as [$from, $to, $file_offset]) {
                    if ($reference_from >= $from && $reference_from <= $to) {
                        unset($reference_map[$reference_from]);
                        $reference_map[$reference_from += $file_offset] = [
                            $reference_to += $file_offset,
                            $tag,
                        ];
                    }
                }
            }
        }

        foreach ($this->type_map as $file_path => &$type_map) {
            if (!isset($this->analyzed_methods[$file_path])) {
                unset($this->type_map[$file_path]);
                continue;
            }

            $file_diff_map = $diff_map[$file_path] ?? [];

            if (!$file_diff_map) {
                continue;
            }

            $first_diff_offset = $file_diff_map[0][0];
            $last_diff_offset = $file_diff_map[count($file_diff_map) - 1][1];

            foreach ($type_map as $type_from => [$type_to, $tag]) {
                if ($type_to < $first_diff_offset || $type_from > $last_diff_offset) {
                    continue;
                }

                foreach ($file_diff_map as [$from, $to, $file_offset]) {
                    if ($type_from >= $from && $type_from <= $to) {
                        unset($type_map[$type_from]);
                        $type_map[$type_from += $file_offset] = [
                            $type_to += $file_offset,
                            $tag,
                        ];
                    }
                }
            }
        }

        foreach ($this->argument_map as $file_path => &$argument_map) {
            if (!isset($this->analyzed_methods[$file_path])) {
                unset($this->argument_map[$file_path]);
                continue;
            }

            $file_diff_map = $diff_map[$file_path] ?? [];

            if (!$file_diff_map) {
                continue;
            }

            $first_diff_offset = $file_diff_map[0][0];
            $last_diff_offset = $file_diff_map[count($file_diff_map) - 1][1];

            foreach ($argument_map as $argument_from => [$argument_to, $method_id, $argument_number]) {
                if ($argument_to < $first_diff_offset || $argument_from > $last_diff_offset) {
                    continue;
                }

                foreach ($file_diff_map as [$from, $to, $file_offset]) {
                    if ($argument_from >= $from && $argument_from <= $to) {
                        unset($argument_map[$argument_from]);
                        $argument_map[$argument_from += $file_offset] = [
                            $argument_to += $file_offset,
                            $method_id,
                            $argument_number,
                        ];
                    }
                }
            }
        }
    }

    /**
     * @return array<string, array<string, bool>>
     */
    public function getMixedMemberNames() : array
    {
        return $this->mixed_member_names;
    }

    public function addMixedMemberName(string $member_id, string $reference): void
    {
        $this->mixed_member_names[$member_id][$reference] = true;
    }

    public function hasMixedMemberName(string $member_id) : bool
    {
        return isset($this->mixed_member_names[$member_id]);
    }

    /**
     * @param array<string, array<string, bool>> $names
     *
     */
    public function addMixedMemberNames(array $names): void
    {
        foreach ($names as $key => $name) {
            if (isset($this->mixed_member_names[$key])) {
                $this->mixed_member_names[$key] = array_merge(
                    $this->mixed_member_names[$key],
                    $name
                );
            } else {
                $this->mixed_member_names[$key] = $name;
            }
        }
    }

    /**
     * @return array{0:int, 1:int}
     */
    public function getMixedCountsForFile(string $file_path): array
    {
        if (!isset($this->mixed_counts[$file_path])) {
            $this->mixed_counts[$file_path] = [0, 0];
        }

        return $this->mixed_counts[$file_path];
    }

    /**
     * @param  array{0:int, 1:int} $mixed_counts
     *
     */
    public function setMixedCountsForFile(string $file_path, array $mixed_counts): void
    {
        $this->mixed_counts[$file_path] = $mixed_counts;
    }

    public function incrementMixedCount(string $file_path): void
    {
        if (!$this->count_mixed) {
            return;
        }

        if (!isset($this->mixed_counts[$file_path])) {
            $this->mixed_counts[$file_path] = [0, 0];
        }

        ++$this->mixed_counts[$file_path][0];
    }

    public function decrementMixedCount(string $file_path): void
    {
        if (!$this->count_mixed) {
            return;
        }

        if (!isset($this->mixed_counts[$file_path])) {
            return;
        }

        --$this->mixed_counts[$file_path][0];
    }

    public function incrementNonMixedCount(string $file_path): void
    {
        if (!$this->count_mixed) {
            return;
        }

        if (!isset($this->mixed_counts[$file_path])) {
            $this->mixed_counts[$file_path] = [0, 0];
        }

        ++$this->mixed_counts[$file_path][1];
    }

    /**
     * @return array<string, array{0: int, 1: int}>
     */
    public function getMixedCounts(): array
    {
        $all_deep_scanned_files = [];

        foreach ($this->files_to_analyze as $file_path => $_) {
            $all_deep_scanned_files[$file_path] = true;
        }

        return array_intersect_key($this->mixed_counts, $all_deep_scanned_files);
    }

    /**
     * @return array<string, float>
     */
    public function getFunctionTimings(): array
    {
        return $this->function_timings;
    }

    public function addFunctionTiming(string $function_id, float $time_per_node) : void
    {
        $this->function_timings[$function_id] = $time_per_node;
    }

    public function addNodeType(
        string $file_path,
        PhpParser\Node $node,
        string $node_type,
        PhpParser\Node $parent_node = null
    ): void {
        if (!$node_type) {
            throw new \UnexpectedValueException('non-empty node_type expected');
        }

        $this->type_map[$file_path][(int)$node->getAttribute('startFilePos')] = [
            ($parent_node ? (int)$parent_node->getAttribute('endFilePos') : (int)$node->getAttribute('endFilePos')) + 1,
            $node_type,
        ];
    }

    public function addNodeArgument(
        string $file_path,
        int $start_position,
        int $end_position,
        string $reference,
        int $argument_number
    ): void {
        if (!$reference) {
            throw new \UnexpectedValueException('non-empty node_type expected');
        }

        $this->argument_map[$file_path][$start_position] = [
            $end_position,
            $reference,
            $argument_number,
        ];
    }

    public function addNodeReference(string $file_path, PhpParser\Node $node, string $reference): void
    {
        if (!$reference) {
            throw new \UnexpectedValueException('non-empty node_type expected');
        }

        $this->reference_map[$file_path][(int)$node->getAttribute('startFilePos')] = [
            (int)$node->getAttribute('endFilePos') + 1,
            $reference,
        ];
    }

    public function addOffsetReference(string $file_path, int $start, int $end, string $reference): void
    {
        if (!$reference) {
            throw new \UnexpectedValueException('non-empty node_type expected');
        }

        $this->reference_map[$file_path][$start] = [
            $end,
            $reference,
        ];
    }

    /**
     * @return array{int, int}
     */
    public function getTotalTypeCoverage(\Psalm\Codebase $codebase): array
    {
        $mixed_count = 0;
        $nonmixed_count = 0;

        foreach ($codebase->file_reference_provider->getTypeCoverage() as $file_path => $counts) {
            if (!$this->config->reportTypeStatsForFile($file_path)) {
                continue;
            }

            [$path_mixed_count, $path_nonmixed_count] = $counts;

            if (isset($this->mixed_counts[$file_path])) {
                $mixed_count += $path_mixed_count;
                $nonmixed_count += $path_nonmixed_count;
            }
        }

        return [$mixed_count, $nonmixed_count];
    }

    public function getTypeInferenceSummary(\Psalm\Codebase $codebase): string
    {
        $all_deep_scanned_files = [];

        foreach ($this->files_to_analyze as $file_path => $_) {
            $all_deep_scanned_files[$file_path] = true;

            foreach ($this->file_storage_provider->get($file_path)->required_file_paths as $required_file_path) {
                $all_deep_scanned_files[$required_file_path] = true;
            }
        }

        [$mixed_count, $nonmixed_count] = $this->getTotalTypeCoverage($codebase);

        $total = $mixed_count + $nonmixed_count;

        $total_files = count($all_deep_scanned_files);

        if (!$total_files) {
            return 'No files analyzed';
        }

        if (!$total) {
            return 'Psalm was unable to infer types in the codebase';
        }

        $percentage = $nonmixed_count === $total ? '100' : number_format(100 * $nonmixed_count / $total, 4);

        return 'Psalm was able to infer types for ' . $percentage . '%'
            . ' of the codebase';
    }

    public function getNonMixedStats(): string
    {
        $stats = '';

        $all_deep_scanned_files = [];

        foreach ($this->files_to_analyze as $file_path => $_) {
            $all_deep_scanned_files[$file_path] = true;

            if (!$this->config->reportTypeStatsForFile($file_path)) {
                continue;
            }

            foreach ($this->file_storage_provider->get($file_path)->required_file_paths as $required_file_path) {
                $all_deep_scanned_files[$required_file_path] = true;
            }
        }

        foreach ($all_deep_scanned_files as $file_path => $_) {
            if (isset($this->mixed_counts[$file_path])) {
                [$path_mixed_count, $path_nonmixed_count] = $this->mixed_counts[$file_path];

                if ($path_mixed_count + $path_nonmixed_count) {
                    $stats .= number_format(100 * $path_nonmixed_count / ($path_mixed_count + $path_nonmixed_count), 0)
                        . '% ' . $this->config->shortenFileName($file_path)
                        . ' (' . $path_mixed_count . ' mixed)' . "\n";
                }
            }
        }

        return $stats;
    }

    public function disableMixedCounts(): void
    {
        $this->count_mixed = false;
    }

    public function enableMixedCounts(): void
    {
        $this->count_mixed = true;
    }

    public function updateFile(string $file_path, bool $dry_run): void
    {
        FileManipulationBuffer::add(
            $file_path,
            FunctionDocblockManipulator::getManipulationsForFile($file_path)
        );

        FileManipulationBuffer::add(
            $file_path,
            PropertyDocblockManipulator::getManipulationsForFile($file_path)
        );

        FileManipulationBuffer::add(
            $file_path,
            ClassDocblockManipulator::getManipulationsForFile($file_path)
        );

        $file_manipulations = FileManipulationBuffer::getManipulationsForFile($file_path);

        if (!$file_manipulations) {
            return;
        }

        usort(
            $file_manipulations,
            function (FileManipulation $a, FileManipulation $b): int {
                if ($b->end === $a->end) {
                    if ($a->start === $b->start) {
                        return $b->insertion_text > $a->insertion_text ? 1 : -1;
                    }

                    return $b->start > $a->start ? 1 : -1;
                }

                return $b->end > $a->end ? 1 : -1;
            }
        );

        $last_start = \PHP_INT_MAX;
        $existing_contents = $this->file_provider->getContents($file_path);

        foreach ($file_manipulations as $manipulation) {
            if ($manipulation->start <= $last_start) {
                $existing_contents = $manipulation->transform($existing_contents);
                $last_start = $manipulation->start;
            }
        }

        if ($dry_run) {
            echo $file_path . ':' . "\n";

            $differ = new \SebastianBergmann\Diff\Differ(
                new \SebastianBergmann\Diff\Output\StrictUnifiedDiffOutputBuilder([
                    'fromFile' => $file_path,
                    'toFile' => $file_path,
                ])
            );

            echo $differ->diff($this->file_provider->getContents($file_path), $existing_contents);

            return;
        }

        $this->progress->alterFileDone($file_path);

        $this->file_provider->setContents($file_path, $existing_contents);
    }

    /**
     * @return list<IssueData>
     */
    public function getExistingIssuesForFile(string $file_path, int $start, int $end, ?string $issue_type = null): array
    {
        if (!isset($this->existing_issues[$file_path])) {
            return [];
        }

        $applicable_issues = [];

        foreach ($this->existing_issues[$file_path] as $issue_data) {
            if ($issue_data->from >= $start && $issue_data->from <= $end) {
                if ($issue_type === null || $issue_type === $issue_data->type) {
                    $applicable_issues[] = $issue_data;
                }
            }
        }

        return $applicable_issues;
    }

    public function removeExistingDataForFile(string $file_path, int $start, int $end, ?string $issue_type = null): void
    {
        if (isset($this->existing_issues[$file_path])) {
            foreach ($this->existing_issues[$file_path] as $i => $issue_data) {
                if ($issue_data->from >= $start && $issue_data->from <= $end) {
                    if ($issue_type === null || $issue_type === $issue_data->type) {
                        unset($this->existing_issues[$file_path][$i]);
                    }
                }
            }
        }

        if (isset($this->type_map[$file_path])) {
            foreach ($this->type_map[$file_path] as $map_start => $_) {
                if ($map_start >= $start && $map_start <= $end) {
                    unset($this->type_map[$file_path][$map_start]);
                }
            }
        }

        if (isset($this->reference_map[$file_path])) {
            foreach ($this->reference_map[$file_path] as $map_start => $_) {
                if ($map_start >= $start && $map_start <= $end) {
                    unset($this->reference_map[$file_path][$map_start]);
                }
            }
        }

        if (isset($this->argument_map[$file_path])) {
            foreach ($this->argument_map[$file_path] as $map_start => $_) {
                if ($map_start >= $start && $map_start <= $end) {
                    unset($this->argument_map[$file_path][$map_start]);
                }
            }
        }
    }

    /**
     * @return array<string, array<string, int>>
     */
    public function getAnalyzedMethods(): array
    {
        return $this->analyzed_methods;
    }

    /**
     * @return array<string, FileMapType>
     */
    public function getFileMaps(): array
    {
        $file_maps = [];

        foreach ($this->reference_map as $file_path => $reference_map) {
            $file_maps[$file_path] = [$reference_map, [], []];
        }

        foreach ($this->type_map as $file_path => $type_map) {
            if (isset($file_maps[$file_path])) {
                $file_maps[$file_path][1] = $type_map;
            } else {
                $file_maps[$file_path] = [[], $type_map, []];
            }
        }

        foreach ($this->argument_map as $file_path => $argument_map) {
            if (isset($file_maps[$file_path])) {
                $file_maps[$file_path][2] = $argument_map;
            } else {
                $file_maps[$file_path] = [[], [], $argument_map];
            }
        }

        return $file_maps;
    }

    /**
     * @return FileMapType
     */
    public function getMapsForFile(string $file_path): array
    {
        return [
            $this->reference_map[$file_path] ?? [],
            $this->type_map[$file_path] ?? [],
            $this->argument_map[$file_path] ?? [],
        ];
    }

    /**
     * @return array<string, array<int, \Psalm\Type\Union>>
     */
    public function getPossibleMethodParamTypes(): array
    {
        return $this->possible_method_param_types;
    }

    public function addMutableClass(string $fqcln) : void
    {
        $this->mutable_classes[\strtolower($fqcln)] = true;
    }

    public function setAnalyzedMethod(string $file_path, string $method_id, bool $is_constructor = false): void
    {
        $this->analyzed_methods[$file_path][$method_id] = $is_constructor ? 2 : 1;
    }

    public function isMethodAlreadyAnalyzed(string $file_path, string $method_id, bool $is_constructor = false): bool
    {
        if ($is_constructor) {
            return isset($this->analyzed_methods[$file_path][$method_id])
                && $this->analyzed_methods[$file_path][$method_id] === 2;
        }

        return isset($this->analyzed_methods[$file_path][$method_id]);
    }
}
