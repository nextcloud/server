<?php
namespace Psalm\Internal\Provider;

use function abs;
use function array_flip;
use function array_intersect_key;
use function array_map;
use function array_merge;
use function count;
use function filemtime;
use function md5;
use PhpParser;
use Psalm\Progress\Progress;
use Psalm\Progress\VoidProgress;
use function strlen;
use function substr;

/**
 * @internal
 */
class StatementsProvider
{
    /**
     * @var FileProvider
     */
    private $file_provider;

    /**
     * @var ?ParserCacheProvider
     */
    public $parser_cache_provider;

    /**
     * @var int
     */
    private $this_modified_time;

    /**
     * @var ?FileStorageCacheProvider
     */
    private $file_storage_cache_provider;

    /**
     * @var array<string, array<string, bool>>
     */
    private $unchanged_members = [];

    /**
     * @var array<string, array<string, bool>>
     */
    private $unchanged_signature_members = [];

    /**
     * @var array<string, array<string, bool>>
     */
    private $changed_members = [];

    /**
     * @var array<string, array<int, array{0: int, 1: int, 2: int, 3: int}>>
     */
    private $diff_map = [];

    /**
     * @var PhpParser\Lexer|null
     */
    private static $lexer;

    /**
     * @var PhpParser\Parser|null
     */
    private static $parser;

    public function __construct(
        FileProvider $file_provider,
        ?ParserCacheProvider $parser_cache_provider = null,
        ?FileStorageCacheProvider $file_storage_cache_provider = null
    ) {
        $this->file_provider = $file_provider;
        $this->parser_cache_provider = $parser_cache_provider;
        $this->this_modified_time = filemtime(__FILE__);
        $this->file_storage_cache_provider = $file_storage_cache_provider;
    }

    /**
     * @return list<\PhpParser\Node\Stmt>
     */
    public function getStatementsForFile(string $file_path, string $php_version, ?Progress $progress = null): array
    {
        if ($progress === null) {
            $progress = new VoidProgress();
        }

        $from_cache = false;

        $version = (string) PHP_PARSER_VERSION . $this->this_modified_time;

        $file_contents = $this->file_provider->getContents($file_path);
        $modified_time = $this->file_provider->getModifiedTime($file_path);

        $config = \Psalm\Config::getInstance();

        if (!$this->parser_cache_provider
            || (!$config->isInProjectDirs($file_path) && \strpos($file_path, 'vendor'))
        ) {
            $progress->debug('Parsing ' . $file_path . "\n");

            $stmts = self::parseStatements($file_contents, $php_version, $file_path);

            return $stmts ?: [];
        }

        $file_content_hash = md5($version . $file_contents);

        $stmts = $this->parser_cache_provider->loadStatementsFromCache(
            $file_path,
            $modified_time,
            $file_content_hash
        );

        if ($stmts === null) {
            $progress->debug('Parsing ' . $file_path . "\n");

            $existing_statements = $this->parser_cache_provider->loadExistingStatementsFromCache($file_path);

            /** @psalm-suppress DocblockTypeContradiction */
            if ($existing_statements && !$existing_statements[0] instanceof PhpParser\Node\Stmt) {
                $existing_statements = null;
            }

            $existing_file_contents = $this->parser_cache_provider->loadExistingFileContentsFromCache($file_path);

            // this happens after editing temporary file
            if ($existing_file_contents === $file_contents && $existing_statements) {
                $this->diff_map[$file_path] = [];
                $this->parser_cache_provider->saveStatementsToCache(
                    $file_path,
                    $file_content_hash,
                    $existing_statements,
                    true
                );

                return $existing_statements;
            }

            $file_changes = null;

            $existing_statements_copy = null;

            if ($existing_statements
                && $existing_file_contents
                && abs(strlen($existing_file_contents) - strlen($file_contents)) < 5000
            ) {
                $file_changes = \Psalm\Internal\Diff\FileDiffer::getDiff($existing_file_contents, $file_contents);

                if (count($file_changes) < 10) {
                    $traverser = new PhpParser\NodeTraverser;
                    $traverser->addVisitor(new \Psalm\Internal\PhpVisitor\CloningVisitor);
                    // performs a deep clone
                    /** @var list<PhpParser\Node\Stmt> */
                    $existing_statements_copy = $traverser->traverse($existing_statements);
                } else {
                    $file_changes = null;
                }
            }

            $stmts = self::parseStatements(
                $file_contents,
                $php_version,
                $file_path,
                $existing_file_contents,
                $existing_statements_copy,
                $file_changes
            );

            if ($existing_file_contents && $existing_statements) {
                [$unchanged_members, $unchanged_signature_members, $changed_members, $diff_map]
                    = \Psalm\Internal\Diff\FileStatementsDiffer::diff(
                        $existing_statements,
                        $stmts,
                        $existing_file_contents,
                        $file_contents
                    );

                $unchanged_members = array_map(
                    /**
                     * @param int $_
                     *
                     * @return bool
                     */
                    function ($_): bool {
                        return true;
                    },
                    array_flip($unchanged_members)
                );

                $unchanged_signature_members = array_map(
                    /**
                     * @param int $_
                     *
                     * @return bool
                     */
                    function ($_): bool {
                        return true;
                    },
                    array_flip($unchanged_signature_members)
                );

                $file_path_hash = \md5($file_path);

                $changed_members = array_map(
                    function (string $key) use ($file_path_hash) : string {
                        if (substr($key, 0, 4) === 'use:') {
                            return $key . ':' . $file_path_hash;
                        }

                        return $key;
                    },
                    $changed_members
                );

                $changed_members = array_map(
                    /**
                     * @param int $_
                     *
                     * @return bool
                     */
                    function ($_): bool {
                        return true;
                    },
                    array_flip($changed_members)
                );

                if (isset($this->unchanged_members[$file_path])) {
                    $this->unchanged_members[$file_path] = array_intersect_key(
                        $this->unchanged_members[$file_path],
                        $unchanged_members
                    );
                } else {
                    $this->unchanged_members[$file_path] = $unchanged_members;
                }

                if (isset($this->unchanged_signature_members[$file_path])) {
                    $this->unchanged_signature_members[$file_path] = array_intersect_key(
                        $this->unchanged_signature_members[$file_path],
                        $unchanged_signature_members
                    );
                } else {
                    $this->unchanged_signature_members[$file_path] = $unchanged_signature_members;
                }

                if (isset($this->changed_members[$file_path])) {
                    $this->changed_members[$file_path] = array_merge(
                        $this->changed_members[$file_path],
                        $changed_members
                    );
                } else {
                    $this->changed_members[$file_path] = $changed_members;
                }

                $this->diff_map[$file_path] = $diff_map;
            }

            if ($this->file_storage_cache_provider) {
                $this->file_storage_cache_provider->removeCacheForFile($file_path);
            }

            $this->parser_cache_provider->cacheFileContents($file_path, $file_contents);
        } else {
            $from_cache = true;
            $this->diff_map[$file_path] = [];
        }

        $this->parser_cache_provider->saveStatementsToCache($file_path, $file_content_hash, $stmts, $from_cache);

        if (!$stmts) {
            return [];
        }

        return $stmts;
    }

    /**
     * @return array<string, array<string, bool>>
     */
    public function getChangedMembers(): array
    {
        return $this->changed_members;
    }

    /**
     * @param array<string, array<string, bool>> $more_changed_members
     *
     */
    public function addChangedMembers(array $more_changed_members): void
    {
        $this->changed_members = array_merge($more_changed_members, $this->changed_members);
    }

    /**
     * @return array<string, array<string, bool>>
     */
    public function getUnchangedSignatureMembers(): array
    {
        return $this->unchanged_signature_members;
    }

    /**
     * @param array<string, array<string, bool>> $more_unchanged_members
     *
     */
    public function addUnchangedSignatureMembers(array $more_unchanged_members): void
    {
        $this->unchanged_signature_members = array_merge($more_unchanged_members, $this->unchanged_signature_members);
    }

    public function setUnchangedFile(string $file_path): void
    {
        if (!isset($this->diff_map[$file_path])) {
            $this->diff_map[$file_path] = [];
        }
    }

    /**
     * @return array<string, array<int, array{0: int, 1: int, 2: int, 3: int}>>
     */
    public function getDiffMap(): array
    {
        return $this->diff_map;
    }

    /**
     * @param array<string, array<int, array{0: int, 1: int, 2: int, 3: int}>> $diff_map
     *
     */
    public function addDiffMap(array $diff_map): void
    {
        $this->diff_map = array_merge($diff_map, $this->diff_map);
    }

    public function resetDiffs(): void
    {
        $this->changed_members = [];
        $this->unchanged_members = [];
        $this->unchanged_signature_members = [];
        $this->diff_map = [];
    }

    /**
     * @param  list<\PhpParser\Node\Stmt> $existing_statements
     * @param  array<int, array{0:int, 1:int, 2: int, 3: int, 4: int, 5:string}> $file_changes
     *
     * @return list<\PhpParser\Node\Stmt>
     */
    public static function parseStatements(
        string $file_contents,
        string $php_version,
        ?string $file_path = null,
        ?string $existing_file_contents = null,
        ?array $existing_statements = null,
        ?array $file_changes = null
    ): array {
        $attributes = [
            'comments', 'startLine', 'startFilePos', 'endFilePos',
        ];

        if (!self::$lexer) {
            self::$lexer = new PhpParser\Lexer\Emulative([
                'usedAttributes' => $attributes,
                'phpVersion' => $php_version,
            ]);
        }

        if (!self::$parser) {
            self::$parser = (new PhpParser\ParserFactory())->create(PhpParser\ParserFactory::ONLY_PHP7, self::$lexer);
        }

        $used_cached_statements = false;

        $error_handler = new \PhpParser\ErrorHandler\Collecting();

        if ($existing_statements && $file_changes && $existing_file_contents) {
            $clashing_traverser = new \Psalm\Internal\PhpTraverser\CustomTraverser;
            $offset_analyzer = new \Psalm\Internal\PhpVisitor\PartialParserVisitor(
                self::$parser,
                $error_handler,
                $file_changes,
                $existing_file_contents,
                $file_contents
            );
            $clashing_traverser->addVisitor($offset_analyzer);
            $clashing_traverser->traverse($existing_statements);

            if (!$offset_analyzer->mustRescan()) {
                $used_cached_statements = true;
                $stmts = $existing_statements;
            } else {
                try {
                    /** @var list<\PhpParser\Node\Stmt> */
                    $stmts = self::$parser->parse($file_contents, $error_handler) ?: [];
                } catch (\Throwable $t) {
                    $stmts = [];

                    // hope this got caught below
                }
            }
        } else {
            try {
                /** @var list<\PhpParser\Node\Stmt> */
                $stmts = self::$parser->parse($file_contents, $error_handler) ?: [];
            } catch (\Throwable $t) {
                $stmts = [];

                // hope this got caught below
            }
        }

        if ($error_handler->hasErrors() && $file_path) {
            $config = \Psalm\Config::getInstance();

            foreach ($error_handler->getErrors() as $error) {
                if ($error->hasColumnInfo()) {
                    \Psalm\IssueBuffer::add(
                        new \Psalm\Issue\ParseError(
                            $error->getMessage(),
                            new \Psalm\CodeLocation\ParseErrorLocation(
                                $error,
                                $file_contents,
                                $file_path,
                                $config->shortenFileName($file_path)
                            )
                        )
                    );
                }
            }
        }

        $error_handler->clearErrors();

        $resolving_traverser = new PhpParser\NodeTraverser;
        $name_resolver = new \Psalm\Internal\PhpVisitor\SimpleNameResolver(
            $error_handler,
            $used_cached_statements ? $file_changes : []
        );
        $resolving_traverser->addVisitor($name_resolver);
        $resolving_traverser->traverse($stmts);

        return $stmts;
    }

    public static function clearLexer() : void
    {
        self::$lexer = null;
    }

    public static function clearParser(): void
    {
        self::$parser = null;
    }
}
