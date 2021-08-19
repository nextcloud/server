<?php

/**
 * SCSSPHP
 *
 * @copyright 2012-2020 Leaf Corcoran
 *
 * @license http://opensource.org/licenses/MIT MIT
 *
 * @link http://scssphp.github.io/scssphp
 */

namespace ScssPhp\ScssPhp;

use ScssPhp\ScssPhp\Base\Range;
use ScssPhp\ScssPhp\Compiler\CachedResult;
use ScssPhp\ScssPhp\Compiler\Environment;
use ScssPhp\ScssPhp\Exception\CompilerException;
use ScssPhp\ScssPhp\Exception\ParserException;
use ScssPhp\ScssPhp\Exception\SassException;
use ScssPhp\ScssPhp\Exception\SassScriptException;
use ScssPhp\ScssPhp\Formatter\Compressed;
use ScssPhp\ScssPhp\Formatter\Expanded;
use ScssPhp\ScssPhp\Formatter\OutputBlock;
use ScssPhp\ScssPhp\Logger\LoggerInterface;
use ScssPhp\ScssPhp\Logger\StreamLogger;
use ScssPhp\ScssPhp\Node\Number;
use ScssPhp\ScssPhp\SourceMap\SourceMapGenerator;
use ScssPhp\ScssPhp\Util\Path;

/**
 * The scss compiler and parser.
 *
 * Converting SCSS to CSS is a three stage process. The incoming file is parsed
 * by `Parser` into a syntax tree, then it is compiled into another tree
 * representing the CSS structure by `Compiler`. The CSS tree is fed into a
 * formatter, like `Formatter` which then outputs CSS as a string.
 *
 * During the first compile, all values are *reduced*, which means that their
 * types are brought to the lowest form before being dump as strings. This
 * handles math equations, variable dereferences, and the like.
 *
 * The `compile` function of `Compiler` is the entry point.
 *
 * In summary:
 *
 * The `Compiler` class creates an instance of the parser, feeds it SCSS code,
 * then transforms the resulting tree to a CSS tree. This class also holds the
 * evaluation context, such as all available mixins and variables at any given
 * time.
 *
 * The `Parser` class is only concerned with parsing its input.
 *
 * The `Formatter` takes a CSS tree, and dumps it to a formatted string,
 * handling things like indentation.
 */

/**
 * SCSS compiler
 *
 * @author Leaf Corcoran <leafot@gmail.com>
 *
 * @final Extending the Compiler is deprecated
 */
class Compiler
{
    /**
     * @deprecated
     */
    const LINE_COMMENTS = 1;
    /**
     * @deprecated
     */
    const DEBUG_INFO    = 2;

    /**
     * @deprecated
     */
    const WITH_RULE     = 1;
    /**
     * @deprecated
     */
    const WITH_MEDIA    = 2;
    /**
     * @deprecated
     */
    const WITH_SUPPORTS = 4;
    /**
     * @deprecated
     */
    const WITH_ALL      = 7;

    const SOURCE_MAP_NONE   = 0;
    const SOURCE_MAP_INLINE = 1;
    const SOURCE_MAP_FILE   = 2;

    /**
     * @var array<string, string>
     */
    protected static $operatorNames = [
        '+'   => 'add',
        '-'   => 'sub',
        '*'   => 'mul',
        '/'   => 'div',
        '%'   => 'mod',

        '=='  => 'eq',
        '!='  => 'neq',
        '<'   => 'lt',
        '>'   => 'gt',

        '<='  => 'lte',
        '>='  => 'gte',
    ];

    /**
     * @var array<string, string>
     */
    protected static $namespaces = [
        'special'  => '%',
        'mixin'    => '@',
        'function' => '^',
    ];

    public static $true         = [Type::T_KEYWORD, 'true'];
    public static $false        = [Type::T_KEYWORD, 'false'];
    /** @deprecated */
    public static $NaN          = [Type::T_KEYWORD, 'NaN'];
    /** @deprecated */
    public static $Infinity     = [Type::T_KEYWORD, 'Infinity'];
    public static $null         = [Type::T_NULL];
    public static $nullString   = [Type::T_STRING, '', []];
    public static $defaultValue = [Type::T_KEYWORD, ''];
    public static $selfSelector = [Type::T_SELF];
    public static $emptyList    = [Type::T_LIST, '', []];
    public static $emptyMap     = [Type::T_MAP, [], []];
    public static $emptyString  = [Type::T_STRING, '"', []];
    public static $with         = [Type::T_KEYWORD, 'with'];
    public static $without      = [Type::T_KEYWORD, 'without'];

    /**
     * @var array<int, string|callable>
     */
    protected $importPaths = [];
    /**
     * @var array<string, Block>
     */
    protected $importCache = [];

    /**
     * @var string[]
     */
    protected $importedFiles = [];

    /**
     * @var array
     * @phpstan-var array<string, array{0: callable, 1: array|null}>
     */
    protected $userFunctions = [];
    /**
     * @var array<string, mixed>
     */
    protected $registeredVars = [];
    /**
     * @var array<string, bool>
     */
    protected $registeredFeatures = [
        'extend-selector-pseudoclass' => false,
        'at-error'                    => true,
        'units-level-3'               => true,
        'global-variable-shadowing'   => false,
    ];

    /**
     * @var string|null
     */
    protected $encoding = null;
    /**
     * @var null
     * @deprecated
     */
    protected $lineNumberStyle = null;

    /**
     * @var int|SourceMapGenerator
     * @phpstan-var self::SOURCE_MAP_*|SourceMapGenerator
     */
    protected $sourceMap = self::SOURCE_MAP_NONE;

    /**
     * @var array
     * @phpstan-var array{sourceRoot?: string, sourceMapFilename?: string|null, sourceMapURL?: string|null, sourceMapWriteTo?: string|null, outputSourceFiles?: bool, sourceMapRootpath?: string, sourceMapBasepath?: string}
     */
    protected $sourceMapOptions = [];

    /**
     * @var string|\ScssPhp\ScssPhp\Formatter
     */
    protected $formatter = Expanded::class;

    /**
     * @var Environment
     */
    protected $rootEnv;
    /**
     * @var OutputBlock|null
     */
    protected $rootBlock;

    /**
     * @var \ScssPhp\ScssPhp\Compiler\Environment
     */
    protected $env;
    /**
     * @var OutputBlock|null
     */
    protected $scope;
    /**
     * @var Environment|null
     */
    protected $storeEnv;
    /**
     * @var bool|null
     */
    protected $charsetSeen;
    /**
     * @var array<int, string|null>
     */
    protected $sourceNames;

    /**
     * @var Cache|null
     */
    protected $cache;

    /**
     * @var bool
     */
    protected $cacheCheckImportResolutions = false;

    /**
     * @var int
     */
    protected $indentLevel;
    /**
     * @var array[]
     */
    protected $extends;
    /**
     * @var array<string, int[]>
     */
    protected $extendsMap;

    /**
     * @var array<string, int>
     */
    protected $parsedFiles = [];

    /**
     * @var Parser|null
     */
    protected $parser;
    /**
     * @var int|null
     */
    protected $sourceIndex;
    /**
     * @var int|null
     */
    protected $sourceLine;
    /**
     * @var int|null
     */
    protected $sourceColumn;
    /**
     * @var bool|null
     */
    protected $shouldEvaluate;
    /**
     * @var null
     * @deprecated
     */
    protected $ignoreErrors;
    /**
     * @var bool
     */
    protected $ignoreCallStackMessage = false;

    /**
     * @var array[]
     */
    protected $callStack = [];

    /**
     * @var array
     * @phpstan-var list<array{currentDir: string|null, path: string, filePath: string}>
     */
    private $resolvedImports = [];

    /**
     * The directory of the currently processed file
     *
     * @var string|null
     */
    private $currentDirectory;

    /**
     * The directory of the input file
     *
     * @var string
     */
    private $rootDirectory;

    /**
     * @var bool
     */
    private $legacyCwdImportPath = true;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var array<string, bool>
     */
    private $warnedChildFunctions = [];

    /**
     * Constructor
     *
     * @param array|null $cacheOptions
     * @phpstan-param array{cacheDir?: string, prefix?: string, forceRefresh?: string, checkImportResolutions?: bool}|null $cacheOptions
     */
    public function __construct($cacheOptions = null)
    {
        $this->sourceNames = [];

        if ($cacheOptions) {
            $this->cache = new Cache($cacheOptions);
            if (!empty($cacheOptions['checkImportResolutions'])) {
                $this->cacheCheckImportResolutions = true;
            }
        }

        $this->logger = new StreamLogger(fopen('php://stderr', 'w'), true);
    }

    /**
     * Get compiler options
     *
     * @return array<string, mixed>
     *
     * @internal
     */
    public function getCompileOptions()
    {
        $options = [
            'importPaths'        => $this->importPaths,
            'registeredVars'     => $this->registeredVars,
            'registeredFeatures' => $this->registeredFeatures,
            'encoding'           => $this->encoding,
            'sourceMap'          => serialize($this->sourceMap),
            'sourceMapOptions'   => $this->sourceMapOptions,
            'formatter'          => $this->formatter,
            'legacyImportPath'   => $this->legacyCwdImportPath,
        ];

        return $options;
    }

    /**
     * Sets an alternative logger.
     *
     * Changing the logger in the middle of the compilation is not
     * supported and will result in an undefined behavior.
     *
     * @param LoggerInterface $logger
     *
     * @return void
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Set an alternative error output stream, for testing purpose only
     *
     * @param resource $handle
     *
     * @return void
     *
     * @deprecated Use {@see setLogger} instead
     */
    public function setErrorOuput($handle)
    {
        @trigger_error('The method "setErrorOuput" is deprecated. Use "setLogger" instead.', E_USER_DEPRECATED);

        $this->logger = new StreamLogger($handle);
    }

    /**
     * Compile scss
     *
     * @param string      $code
     * @param string|null $path
     *
     * @return string
     *
     * @throws SassException when the source fails to compile
     *
     * @deprecated Use {@see compileString} instead.
     */
    public function compile($code, $path = null)
    {
        @trigger_error(sprintf('The "%s" method is deprecated. Use "compileString" instead.', __METHOD__), E_USER_DEPRECATED);

        $result = $this->compileString($code, $path);

        $sourceMap = $result->getSourceMap();

        if ($sourceMap !== null) {
            if ($this->sourceMap instanceof SourceMapGenerator) {
                $this->sourceMap->saveMap($sourceMap);
            } elseif ($this->sourceMap === self::SOURCE_MAP_FILE) {
                $sourceMapGenerator = new SourceMapGenerator($this->sourceMapOptions);
                $sourceMapGenerator->saveMap($sourceMap);
            }
        }

        return $result->getCss();
    }

    /**
     * Compile scss
     *
     * @param string      $source
     * @param string|null $path
     *
     * @return CompilationResult
     *
     * @throws SassException when the source fails to compile
     */
    public function compileString($source, $path = null)
    {
        if ($this->cache) {
            $cacheKey       = ($path ? $path : '(stdin)') . ':' . md5($source);
            $compileOptions = $this->getCompileOptions();
            $cachedResult = $this->cache->getCache('compile', $cacheKey, $compileOptions);

            if ($cachedResult instanceof CachedResult && $this->isFreshCachedResult($cachedResult)) {
                return $cachedResult->getResult();
            }
        }

        $this->indentLevel    = -1;
        $this->extends        = [];
        $this->extendsMap     = [];
        $this->sourceIndex    = null;
        $this->sourceLine     = null;
        $this->sourceColumn   = null;
        $this->env            = null;
        $this->scope          = null;
        $this->storeEnv       = null;
        $this->charsetSeen    = null;
        $this->shouldEvaluate = null;
        $this->ignoreCallStackMessage = false;
        $this->parsedFiles = [];
        $this->importedFiles = [];
        $this->resolvedImports = [];

        if (!\is_null($path) && is_file($path)) {
            $path = realpath($path) ?: $path;
            $this->currentDirectory = dirname($path);
            $this->rootDirectory = $this->currentDirectory;
        } else {
            $this->currentDirectory = null;
            $this->rootDirectory = getcwd();
        }

        try {
            $this->parser = $this->parserFactory($path);
            $tree         = $this->parser->parse($source);
            $this->parser = null;

            $this->formatter = new $this->formatter();
            $this->rootBlock = null;
            $this->rootEnv   = $this->pushEnv($tree);

            $warnCallback = function ($message, $deprecation) {
                $this->logger->warn($message, $deprecation);
            };
            $previousWarnCallback = Warn::setCallback($warnCallback);

            try {
                $this->injectVariables($this->registeredVars);
                $this->compileRoot($tree);
                $this->popEnv();
            } finally {
                Warn::setCallback($previousWarnCallback);
            }

            $sourceMapGenerator = null;

            if ($this->sourceMap) {
                if (\is_object($this->sourceMap) && $this->sourceMap instanceof SourceMapGenerator) {
                    $sourceMapGenerator = $this->sourceMap;
                    $this->sourceMap = self::SOURCE_MAP_FILE;
                } elseif ($this->sourceMap !== self::SOURCE_MAP_NONE) {
                    $sourceMapGenerator = new SourceMapGenerator($this->sourceMapOptions);
                }
            }

            $out = $this->formatter->format($this->scope, $sourceMapGenerator);

            $prefix = '';

            if (!$this->charsetSeen) {
                if (strlen($out) !== Util::mbStrlen($out)) {
                    $prefix = '@charset "UTF-8";' . "\n";
                    $out = $prefix . $out;
                }
            }

            $sourceMap = null;

            if (! empty($out) && $this->sourceMap && $this->sourceMap !== self::SOURCE_MAP_NONE) {
                $sourceMap = $sourceMapGenerator->generateJson($prefix);
                $sourceMapUrl = null;

                switch ($this->sourceMap) {
                    case self::SOURCE_MAP_INLINE:
                        $sourceMapUrl = sprintf('data:application/json,%s', Util::encodeURIComponent($sourceMap));
                        break;

                    case self::SOURCE_MAP_FILE:
                        if (isset($this->sourceMapOptions['sourceMapURL'])) {
                            $sourceMapUrl = $this->sourceMapOptions['sourceMapURL'];
                        }
                        break;
                }

                if ($sourceMapUrl !== null) {
                    $out .= sprintf('/*# sourceMappingURL=%s */', $sourceMapUrl);
                }
            }
        } catch (SassScriptException $e) {
            throw new CompilerException($this->addLocationToMessage($e->getMessage()), 0, $e);
        }

        $includedFiles = [];

        foreach ($this->resolvedImports as $resolvedImport) {
            $includedFiles[$resolvedImport['filePath']] = $resolvedImport['filePath'];
        }

        $result = new CompilationResult($out, $sourceMap, array_values($includedFiles));

        if ($this->cache && isset($cacheKey) && isset($compileOptions)) {
            $this->cache->setCache('compile', $cacheKey, new CachedResult($result, $this->parsedFiles, $this->resolvedImports), $compileOptions);
        }

        // Reset state to free memory
        // TODO in 2.0, reset parsedFiles as well when the getter is removed.
        $this->resolvedImports = [];
        $this->importedFiles = [];

        return $result;
    }

    /**
     * @param CachedResult $result
     *
     * @return bool
     */
    private function isFreshCachedResult(CachedResult $result)
    {
        // check if any dependency file changed since the result was compiled
        foreach ($result->getParsedFiles() as $file => $mtime) {
            if (! is_file($file) || filemtime($file) !== $mtime) {
                return false;
            }
        }

        if ($this->cacheCheckImportResolutions) {
            $resolvedImports = [];

            foreach ($result->getResolvedImports() as $import) {
                $currentDir = $import['currentDir'];
                $path = $import['path'];
                // store the check across all the results in memory to avoid multiple findImport() on the same path
                // with same context.
                // this is happening in a same hit with multiple compilations (especially with big frameworks)
                if (empty($resolvedImports[$currentDir][$path])) {
                    $resolvedImports[$currentDir][$path] = $this->findImport($path, $currentDir);
                }

                if ($resolvedImports[$currentDir][$path] !== $import['filePath']) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Instantiate parser
     *
     * @param string|null $path
     *
     * @return \ScssPhp\ScssPhp\Parser
     */
    protected function parserFactory($path)
    {
        // https://sass-lang.com/documentation/at-rules/import
        // CSS files imported by Sass don’t allow any special Sass features.
        // In order to make sure authors don’t accidentally write Sass in their CSS,
        // all Sass features that aren’t also valid CSS will produce errors.
        // Otherwise, the CSS will be rendered as-is. It can even be extended!
        $cssOnly = false;

        if ($path !== null && substr($path, -4) === '.css') {
            $cssOnly = true;
        }

        $parser = new Parser($path, \count($this->sourceNames), $this->encoding, $this->cache, $cssOnly, $this->logger);

        $this->sourceNames[] = $path;
        $this->addParsedFile($path);

        return $parser;
    }

    /**
     * Is self extend?
     *
     * @param array $target
     * @param array $origin
     *
     * @return boolean
     */
    protected function isSelfExtend($target, $origin)
    {
        foreach ($origin as $sel) {
            if (\in_array($target, $sel)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Push extends
     *
     * @param array      $target
     * @param array      $origin
     * @param array|null $block
     *
     * @return void
     */
    protected function pushExtends($target, $origin, $block)
    {
        $i = \count($this->extends);
        $this->extends[] = [$target, $origin, $block];

        foreach ($target as $part) {
            if (isset($this->extendsMap[$part])) {
                $this->extendsMap[$part][] = $i;
            } else {
                $this->extendsMap[$part] = [$i];
            }
        }
    }

    /**
     * Make output block
     *
     * @param string|null   $type
     * @param string[]|null $selectors
     *
     * @return \ScssPhp\ScssPhp\Formatter\OutputBlock
     */
    protected function makeOutputBlock($type, $selectors = null)
    {
        $out = new OutputBlock();
        $out->type      = $type;
        $out->lines     = [];
        $out->children  = [];
        $out->parent    = $this->scope;
        $out->selectors = $selectors;
        $out->depth     = $this->env->depth;

        if ($this->env->block instanceof Block) {
            $out->sourceName   = $this->env->block->sourceName;
            $out->sourceLine   = $this->env->block->sourceLine;
            $out->sourceColumn = $this->env->block->sourceColumn;
        } else {
            $out->sourceName   = null;
            $out->sourceLine   = null;
            $out->sourceColumn = null;
        }

        return $out;
    }

    /**
     * Compile root
     *
     * @param \ScssPhp\ScssPhp\Block $rootBlock
     *
     * @return void
     */
    protected function compileRoot(Block $rootBlock)
    {
        $this->rootBlock = $this->scope = $this->makeOutputBlock(Type::T_ROOT);

        $this->compileChildrenNoReturn($rootBlock->children, $this->scope);
        $this->flattenSelectors($this->scope);
        $this->missingSelectors();
    }

    /**
     * Report missing selectors
     *
     * @return void
     */
    protected function missingSelectors()
    {
        foreach ($this->extends as $extend) {
            if (isset($extend[3])) {
                continue;
            }

            list($target, $origin, $block) = $extend;

            // ignore if !optional
            if ($block[2]) {
                continue;
            }

            $target = implode(' ', $target);
            $origin = $this->collapseSelectors($origin);

            $this->sourceLine = $block[Parser::SOURCE_LINE];
            throw $this->error("\"$origin\" failed to @extend \"$target\". The selector \"$target\" was not found.");
        }
    }

    /**
     * Flatten selectors
     *
     * @param \ScssPhp\ScssPhp\Formatter\OutputBlock $block
     * @param string                                 $parentKey
     *
     * @return void
     */
    protected function flattenSelectors(OutputBlock $block, $parentKey = null)
    {
        if ($block->selectors) {
            $selectors = [];

            foreach ($block->selectors as $s) {
                $selectors[] = $s;

                if (! \is_array($s)) {
                    continue;
                }

                // check extends
                if (! empty($this->extendsMap)) {
                    $this->matchExtends($s, $selectors);

                    // remove duplicates
                    array_walk($selectors, function (&$value) {
                        $value = serialize($value);
                    });

                    $selectors = array_unique($selectors);

                    array_walk($selectors, function (&$value) {
                        $value = unserialize($value);
                    });
                }
            }

            $block->selectors = [];
            $placeholderSelector = false;

            foreach ($selectors as $selector) {
                if ($this->hasSelectorPlaceholder($selector)) {
                    $placeholderSelector = true;
                    continue;
                }

                $block->selectors[] = $this->compileSelector($selector);
            }

            if ($placeholderSelector && 0 === \count($block->selectors) && null !== $parentKey) {
                unset($block->parent->children[$parentKey]);

                return;
            }
        }

        foreach ($block->children as $key => $child) {
            $this->flattenSelectors($child, $key);
        }
    }

    /**
     * Glue parts of :not( or :nth-child( ... that are in general split in selectors parts
     *
     * @param array $parts
     *
     * @return array
     */
    protected function glueFunctionSelectors($parts)
    {
        $new = [];

        foreach ($parts as $part) {
            if (\is_array($part)) {
                $part = $this->glueFunctionSelectors($part);
                $new[] = $part;
            } else {
                // a selector part finishing with a ) is the last part of a :not( or :nth-child(
                // and need to be joined to this
                if (
                    \count($new) && \is_string($new[\count($new) - 1]) &&
                    \strlen($part) && substr($part, -1) === ')' && strpos($part, '(') === false
                ) {
                    while (\count($new) > 1 && substr($new[\count($new) - 1], -1) !== '(') {
                        $part = array_pop($new) . $part;
                    }
                    $new[\count($new) - 1] .= $part;
                } else {
                    $new[] = $part;
                }
            }
        }

        return $new;
    }

    /**
     * Match extends
     *
     * @param array   $selector
     * @param array   $out
     * @param integer $from
     * @param boolean $initial
     *
     * @return void
     */
    protected function matchExtends($selector, &$out, $from = 0, $initial = true)
    {
        static $partsPile = [];
        $selector = $this->glueFunctionSelectors($selector);

        if (\count($selector) == 1 && \in_array(reset($selector), $partsPile)) {
            return;
        }

        $outRecurs = [];

        foreach ($selector as $i => $part) {
            if ($i < $from) {
                continue;
            }

            // check that we are not building an infinite loop of extensions
            // if the new part is just including a previous part don't try to extend anymore
            if (\count($part) > 1) {
                foreach ($partsPile as $previousPart) {
                    if (! \count(array_diff($previousPart, $part))) {
                        continue 2;
                    }
                }
            }

            $partsPile[] = $part;

            if ($this->matchExtendsSingle($part, $origin, $initial)) {
                $after       = \array_slice($selector, $i + 1);
                $before      = \array_slice($selector, 0, $i);
                list($before, $nonBreakableBefore) = $this->extractRelationshipFromFragment($before);

                foreach ($origin as $new) {
                    $k = 0;

                    // remove shared parts
                    if (\count($new) > 1) {
                        while ($k < $i && isset($new[$k]) && $selector[$k] === $new[$k]) {
                            $k++;
                        }
                    }

                    if (\count($nonBreakableBefore) && $k === \count($new)) {
                        $k--;
                    }

                    $replacement = [];
                    $tempReplacement = $k > 0 ? \array_slice($new, $k) : $new;

                    for ($l = \count($tempReplacement) - 1; $l >= 0; $l--) {
                        $slice = [];

                        foreach ($tempReplacement[$l] as $chunk) {
                            if (! \in_array($chunk, $slice)) {
                                $slice[] = $chunk;
                            }
                        }

                        array_unshift($replacement, $slice);

                        if (! $this->isImmediateRelationshipCombinator(end($slice))) {
                            break;
                        }
                    }

                    $afterBefore = $l != 0 ? \array_slice($tempReplacement, 0, $l) : [];

                    // Merge shared direct relationships.
                    $mergedBefore = $this->mergeDirectRelationships($afterBefore, $nonBreakableBefore);

                    $result = array_merge(
                        $before,
                        $mergedBefore,
                        $replacement,
                        $after
                    );

                    if ($result === $selector) {
                        continue;
                    }

                    $this->pushOrMergeExtentedSelector($out, $result);

                    // recursively check for more matches
                    $startRecurseFrom = \count($before) + min(\count($nonBreakableBefore), \count($mergedBefore));

                    if (\count($origin) > 1) {
                        $this->matchExtends($result, $out, $startRecurseFrom, false);
                    } else {
                        $this->matchExtends($result, $outRecurs, $startRecurseFrom, false);
                    }

                    // selector sequence merging
                    if (! empty($before) && \count($new) > 1) {
                        $preSharedParts = $k > 0 ? \array_slice($before, 0, $k) : [];
                        $postSharedParts = $k > 0 ? \array_slice($before, $k) : $before;

                        list($betweenSharedParts, $nonBreakabl2) = $this->extractRelationshipFromFragment($afterBefore);

                        $result2 = array_merge(
                            $preSharedParts,
                            $betweenSharedParts,
                            $postSharedParts,
                            $nonBreakabl2,
                            $nonBreakableBefore,
                            $replacement,
                            $after
                        );

                        $this->pushOrMergeExtentedSelector($out, $result2);
                    }
                }
            }
            array_pop($partsPile);
        }

        while (\count($outRecurs)) {
            $result = array_shift($outRecurs);
            $this->pushOrMergeExtentedSelector($out, $result);
        }
    }

    /**
     * Test a part for being a pseudo selector
     *
     * @param string $part
     * @param array  $matches
     *
     * @return boolean
     */
    protected function isPseudoSelector($part, &$matches)
    {
        if (
            strpos($part, ':') === 0 &&
            preg_match(",^::?([\w-]+)\((.+)\)$,", $part, $matches)
        ) {
            return true;
        }

        return false;
    }

    /**
     * Push extended selector except if
     *  - this is a pseudo selector
     *  - same as previous
     *  - in a white list
     * in this case we merge the pseudo selector content
     *
     * @param array $out
     * @param array $extended
     *
     * @return void
     */
    protected function pushOrMergeExtentedSelector(&$out, $extended)
    {
        if (\count($out) && \count($extended) === 1 && \count(reset($extended)) === 1) {
            $single = reset($extended);
            $part = reset($single);

            if (
                $this->isPseudoSelector($part, $matchesExtended) &&
                \in_array($matchesExtended[1], [ 'slotted' ])
            ) {
                $prev = end($out);
                $prev = $this->glueFunctionSelectors($prev);

                if (\count($prev) === 1 && \count(reset($prev)) === 1) {
                    $single = reset($prev);
                    $part = reset($single);

                    if (
                        $this->isPseudoSelector($part, $matchesPrev) &&
                        $matchesPrev[1] === $matchesExtended[1]
                    ) {
                        $extended = explode($matchesExtended[1] . '(', $matchesExtended[0], 2);
                        $extended[1] = $matchesPrev[2] . ', ' . $extended[1];
                        $extended = implode($matchesExtended[1] . '(', $extended);
                        $extended = [ [ $extended ]];
                        array_pop($out);
                    }
                }
            }
        }
        $out[] = $extended;
    }

    /**
     * Match extends single
     *
     * @param array   $rawSingle
     * @param array   $outOrigin
     * @param boolean $initial
     *
     * @return boolean
     */
    protected function matchExtendsSingle($rawSingle, &$outOrigin, $initial = true)
    {
        $counts = [];
        $single = [];

        // simple usual cases, no need to do the whole trick
        if (\in_array($rawSingle, [['>'],['+'],['~']])) {
            return false;
        }

        foreach ($rawSingle as $part) {
            // matches Number
            if (! \is_string($part)) {
                return false;
            }

            if (! preg_match('/^[\[.:#%]/', $part) && \count($single)) {
                $single[\count($single) - 1] .= $part;
            } else {
                $single[] = $part;
            }
        }

        $extendingDecoratedTag = false;

        if (\count($single) > 1) {
            $matches = null;
            $extendingDecoratedTag = preg_match('/^[a-z0-9]+$/i', $single[0], $matches) ? $matches[0] : false;
        }

        $outOrigin = [];
        $found = false;

        foreach ($single as $k => $part) {
            if (isset($this->extendsMap[$part])) {
                foreach ($this->extendsMap[$part] as $idx) {
                    $counts[$idx] = isset($counts[$idx]) ? $counts[$idx] + 1 : 1;
                }
            }

            if (
                $initial &&
                $this->isPseudoSelector($part, $matches) &&
                ! \in_array($matches[1], [ 'not' ])
            ) {
                $buffer    = $matches[2];
                $parser    = $this->parserFactory(__METHOD__);

                if ($parser->parseSelector($buffer, $subSelectors, false)) {
                    foreach ($subSelectors as $ksub => $subSelector) {
                        $subExtended = [];
                        $this->matchExtends($subSelector, $subExtended, 0, false);

                        if ($subExtended) {
                            $subSelectorsExtended = $subSelectors;
                            $subSelectorsExtended[$ksub] = $subExtended;

                            foreach ($subSelectorsExtended as $ksse => $sse) {
                                $subSelectorsExtended[$ksse] = $this->collapseSelectors($sse);
                            }

                            $subSelectorsExtended = implode(', ', $subSelectorsExtended);
                            $singleExtended = $single;
                            $singleExtended[$k] = str_replace('(' . $buffer . ')', "($subSelectorsExtended)", $part);
                            $outOrigin[] = [ $singleExtended ];
                            $found = true;
                        }
                    }
                }
            }
        }

        foreach ($counts as $idx => $count) {
            list($target, $origin, /* $block */) = $this->extends[$idx];

            $origin = $this->glueFunctionSelectors($origin);

            // check count
            if ($count !== \count($target)) {
                continue;
            }

            $this->extends[$idx][3] = true;

            $rem = array_diff($single, $target);

            foreach ($origin as $j => $new) {
                // prevent infinite loop when target extends itself
                if ($this->isSelfExtend($single, $origin) && ! $initial) {
                    return false;
                }

                $replacement = end($new);

                // Extending a decorated tag with another tag is not possible.
                if (
                    $extendingDecoratedTag && $replacement[0] != $extendingDecoratedTag &&
                    preg_match('/^[a-z0-9]+$/i', $replacement[0])
                ) {
                    unset($origin[$j]);
                    continue;
                }

                $combined = $this->combineSelectorSingle($replacement, $rem);

                if (\count(array_diff($combined, $origin[$j][\count($origin[$j]) - 1]))) {
                    $origin[$j][\count($origin[$j]) - 1] = $combined;
                }
            }

            $outOrigin = array_merge($outOrigin, $origin);

            $found = true;
        }

        return $found;
    }

    /**
     * Extract a relationship from the fragment.
     *
     * When extracting the last portion of a selector we will be left with a
     * fragment which may end with a direction relationship combinator. This
     * method will extract the relationship fragment and return it along side
     * the rest.
     *
     * @param array $fragment The selector fragment maybe ending with a direction relationship combinator.
     *
     * @return array The selector without the relationship fragment if any, the relationship fragment.
     */
    protected function extractRelationshipFromFragment(array $fragment)
    {
        $parents = [];
        $children = [];

        $j = $i = \count($fragment);

        for (;;) {
            $children = $j != $i ? \array_slice($fragment, $j, $i - $j) : [];
            $parents  = \array_slice($fragment, 0, $j);
            $slice    = end($parents);

            if (empty($slice) || ! $this->isImmediateRelationshipCombinator($slice[0])) {
                break;
            }

            $j -= 2;
        }

        return [$parents, $children];
    }

    /**
     * Combine selector single
     *
     * @param array $base
     * @param array $other
     *
     * @return array
     */
    protected function combineSelectorSingle($base, $other)
    {
        $tag    = [];
        $out    = [];
        $wasTag = false;
        $pseudo = [];

        while (\count($other) && strpos(end($other), ':') === 0) {
            array_unshift($pseudo, array_pop($other));
        }

        foreach ([array_reverse($base), array_reverse($other)] as $single) {
            $rang = count($single);

            foreach ($single as $part) {
                if (preg_match('/^[\[:]/', $part)) {
                    $out[] = $part;
                    $wasTag = false;
                } elseif (preg_match('/^[\.#]/', $part)) {
                    array_unshift($out, $part);
                    $wasTag = false;
                } elseif (preg_match('/^[^_-]/', $part) && $rang === 1) {
                    $tag[] = $part;
                    $wasTag = true;
                } elseif ($wasTag) {
                    $tag[\count($tag) - 1] .= $part;
                } else {
                    array_unshift($out, $part);
                }
                $rang--;
            }
        }

        if (\count($tag)) {
            array_unshift($out, $tag[0]);
        }

        while (\count($pseudo)) {
            $out[] = array_shift($pseudo);
        }

        return $out;
    }

    /**
     * Compile media
     *
     * @param \ScssPhp\ScssPhp\Block $media
     *
     * @return void
     */
    protected function compileMedia(Block $media)
    {
        $this->pushEnv($media);

        $mediaQueries = $this->compileMediaQuery($this->multiplyMedia($this->env));

        if (! empty($mediaQueries)) {
            $previousScope = $this->scope;
            $parentScope = $this->mediaParent($this->scope);

            foreach ($mediaQueries as $mediaQuery) {
                $this->scope = $this->makeOutputBlock(Type::T_MEDIA, [$mediaQuery]);

                $parentScope->children[] = $this->scope;
                $parentScope = $this->scope;
            }

            // top level properties in a media cause it to be wrapped
            $needsWrap = false;

            foreach ($media->children as $child) {
                $type = $child[0];

                if (
                    $type !== Type::T_BLOCK &&
                    $type !== Type::T_MEDIA &&
                    $type !== Type::T_DIRECTIVE &&
                    $type !== Type::T_IMPORT
                ) {
                    $needsWrap = true;
                    break;
                }
            }

            if ($needsWrap) {
                $wrapped = new Block();
                $wrapped->sourceName   = $media->sourceName;
                $wrapped->sourceIndex  = $media->sourceIndex;
                $wrapped->sourceLine   = $media->sourceLine;
                $wrapped->sourceColumn = $media->sourceColumn;
                $wrapped->selectors    = [];
                $wrapped->comments     = [];
                $wrapped->parent       = $media;
                $wrapped->children     = $media->children;

                $media->children = [[Type::T_BLOCK, $wrapped]];
            }

            $this->compileChildrenNoReturn($media->children, $this->scope);

            $this->scope = $previousScope;
        }

        $this->popEnv();
    }

    /**
     * Media parent
     *
     * @param \ScssPhp\ScssPhp\Formatter\OutputBlock $scope
     *
     * @return \ScssPhp\ScssPhp\Formatter\OutputBlock
     */
    protected function mediaParent(OutputBlock $scope)
    {
        while (! empty($scope->parent)) {
            if (! empty($scope->type) && $scope->type !== Type::T_MEDIA) {
                break;
            }

            $scope = $scope->parent;
        }

        return $scope;
    }

    /**
     * Compile directive
     *
     * @param \ScssPhp\ScssPhp\Block|array $directive
     * @param \ScssPhp\ScssPhp\Formatter\OutputBlock $out
     *
     * @return void
     */
    protected function compileDirective($directive, OutputBlock $out)
    {
        if (\is_array($directive)) {
            $directiveName = $this->compileDirectiveName($directive[0]);
            $s = '@' . $directiveName;

            if (! empty($directive[1])) {
                $s .= ' ' . $this->compileValue($directive[1]);
            }
            // sass-spec compliance on newline after directives, a bit tricky :/
            $appendNewLine = (! empty($directive[2]) || strpos($s, "\n")) ? "\n" : "";
            if (\is_array($directive[0]) && empty($directive[1])) {
                $appendNewLine = "\n";
            }

            if (empty($directive[3])) {
                $this->appendRootDirective($s . ';' . $appendNewLine, $out, [Type::T_COMMENT, Type::T_DIRECTIVE]);
            } else {
                $this->appendOutputLine($out, Type::T_DIRECTIVE, $s . ';');
            }
        } else {
            $directive->name = $this->compileDirectiveName($directive->name);
            $s = '@' . $directive->name;

            if (! empty($directive->value)) {
                $s .= ' ' . $this->compileValue($directive->value);
            }

            if ($directive->name === 'keyframes' || substr($directive->name, -10) === '-keyframes') {
                $this->compileKeyframeBlock($directive, [$s]);
            } else {
                $this->compileNestedBlock($directive, [$s]);
            }
        }
    }

    /**
     * directive names can include some interpolation
     *
     * @param string|array $directiveName
     * @return string
     * @throws CompilerException
     */
    protected function compileDirectiveName($directiveName)
    {
        if (is_string($directiveName)) {
            return $directiveName;
        }

        return $this->compileValue($directiveName);
    }

    /**
     * Compile at-root
     *
     * @param \ScssPhp\ScssPhp\Block $block
     *
     * @return void
     */
    protected function compileAtRoot(Block $block)
    {
        $env     = $this->pushEnv($block);
        $envs    = $this->compactEnv($env);
        list($with, $without) = $this->compileWith(isset($block->with) ? $block->with : null);

        // wrap inline selector
        if ($block->selector) {
            $wrapped = new Block();
            $wrapped->sourceName   = $block->sourceName;
            $wrapped->sourceIndex  = $block->sourceIndex;
            $wrapped->sourceLine   = $block->sourceLine;
            $wrapped->sourceColumn = $block->sourceColumn;
            $wrapped->selectors    = $block->selector;
            $wrapped->comments     = [];
            $wrapped->parent       = $block;
            $wrapped->children     = $block->children;
            $wrapped->selfParent   = $block->selfParent;

            $block->children = [[Type::T_BLOCK, $wrapped]];
            $block->selector = null;
        }

        $selfParent = $block->selfParent;
        assert($selfParent !== null, 'at-root blocks must have a selfParent set.');

        if (
            ! $selfParent->selectors &&
            isset($block->parent) && $block->parent &&
            isset($block->parent->selectors) && $block->parent->selectors
        ) {
            $selfParent = $block->parent;
        }

        $this->env = $this->filterWithWithout($envs, $with, $without);

        $saveScope   = $this->scope;
        $this->scope = $this->filterScopeWithWithout($saveScope, $with, $without);

        // propagate selfParent to the children where they still can be useful
        $this->compileChildrenNoReturn($block->children, $this->scope, $selfParent);

        $this->scope = $this->completeScope($this->scope, $saveScope);
        $this->scope = $saveScope;
        $this->env   = $this->extractEnv($envs);

        $this->popEnv();
    }

    /**
     * Filter at-root scope depending of with/without option
     *
     * @param \ScssPhp\ScssPhp\Formatter\OutputBlock $scope
     * @param array                                  $with
     * @param array                                  $without
     *
     * @return OutputBlock
     */
    protected function filterScopeWithWithout($scope, $with, $without)
    {
        $filteredScopes = [];
        $childStash = [];

        if ($scope->type === Type::T_ROOT) {
            return $scope;
        }

        // start from the root
        while ($scope->parent && $scope->parent->type !== Type::T_ROOT) {
            array_unshift($childStash, $scope);
            $scope = $scope->parent;
        }

        for (;;) {
            if (! $scope) {
                break;
            }

            if ($this->isWith($scope, $with, $without)) {
                $s = clone $scope;
                $s->children = [];
                $s->lines    = [];
                $s->parent   = null;

                if ($s->type !== Type::T_MEDIA && $s->type !== Type::T_DIRECTIVE) {
                    $s->selectors = [];
                }

                $filteredScopes[] = $s;
            }

            if (\count($childStash)) {
                $scope = array_shift($childStash);
            } elseif ($scope->children) {
                $scope = end($scope->children);
            } else {
                $scope = null;
            }
        }

        if (! \count($filteredScopes)) {
            return $this->rootBlock;
        }

        $newScope = array_shift($filteredScopes);
        $newScope->parent = $this->rootBlock;

        $this->rootBlock->children[] = $newScope;

        $p = &$newScope;

        while (\count($filteredScopes)) {
            $s = array_shift($filteredScopes);
            $s->parent = $p;
            $p->children[] = $s;
            $newScope = &$p->children[0];
            $p = &$p->children[0];
        }

        return $newScope;
    }

    /**
     * found missing selector from a at-root compilation in the previous scope
     * (if at-root is just enclosing a property, the selector is in the parent tree)
     *
     * @param \ScssPhp\ScssPhp\Formatter\OutputBlock $scope
     * @param \ScssPhp\ScssPhp\Formatter\OutputBlock $previousScope
     *
     * @return OutputBlock
     */
    protected function completeScope($scope, $previousScope)
    {
        if (! $scope->type && (! $scope->selectors || ! \count($scope->selectors)) && \count($scope->lines)) {
            $scope->selectors = $this->findScopeSelectors($previousScope, $scope->depth);
        }

        if ($scope->children) {
            foreach ($scope->children as $k => $c) {
                $scope->children[$k] = $this->completeScope($c, $previousScope);
            }
        }

        return $scope;
    }

    /**
     * Find a selector by the depth node in the scope
     *
     * @param \ScssPhp\ScssPhp\Formatter\OutputBlock $scope
     * @param integer                                $depth
     *
     * @return array
     */
    protected function findScopeSelectors($scope, $depth)
    {
        if ($scope->depth === $depth && $scope->selectors) {
            return $scope->selectors;
        }

        if ($scope->children) {
            foreach (array_reverse($scope->children) as $c) {
                if ($s = $this->findScopeSelectors($c, $depth)) {
                    return $s;
                }
            }
        }

        return [];
    }

    /**
     * Compile @at-root's with: inclusion / without: exclusion into 2 lists uses to filter scope/env later
     *
     * @param array $withCondition
     *
     * @return array
     */
    protected function compileWith($withCondition)
    {
        // just compile what we have in 2 lists
        $with = [];
        $without = ['rule' => true];

        if ($withCondition) {
            if ($withCondition[0] === Type::T_INTERPOLATE) {
                $w = $this->compileValue($withCondition);

                $buffer = "($w)";
                $parser = $this->parserFactory(__METHOD__);

                if ($parser->parseValue($buffer, $reParsedWith)) {
                    $withCondition = $reParsedWith;
                }
            }

            if ($this->mapHasKey($withCondition, static::$with)) {
                $without = []; // cancel the default
                $list = $this->coerceList($this->libMapGet([$withCondition, static::$with]));

                foreach ($list[2] as $item) {
                    $keyword = $this->compileStringContent($this->coerceString($item));

                    $with[$keyword] = true;
                }
            }

            if ($this->mapHasKey($withCondition, static::$without)) {
                $without = []; // cancel the default
                $list = $this->coerceList($this->libMapGet([$withCondition, static::$without]));

                foreach ($list[2] as $item) {
                    $keyword = $this->compileStringContent($this->coerceString($item));

                    $without[$keyword] = true;
                }
            }
        }

        return [$with, $without];
    }

    /**
     * Filter env stack
     *
     * @param Environment[] $envs
     * @param array $with
     * @param array $without
     *
     * @return Environment
     *
     * @phpstan-param  non-empty-array<Environment> $envs
     */
    protected function filterWithWithout($envs, $with, $without)
    {
        $filtered = [];

        foreach ($envs as $e) {
            if ($e->block && ! $this->isWith($e->block, $with, $without)) {
                $ec = clone $e;
                $ec->block     = null;
                $ec->selectors = [];

                $filtered[] = $ec;
            } else {
                $filtered[] = $e;
            }
        }

        return $this->extractEnv($filtered);
    }

    /**
     * Filter WITH rules
     *
     * @param \ScssPhp\ScssPhp\Block|\ScssPhp\ScssPhp\Formatter\OutputBlock $block
     * @param array                                                         $with
     * @param array                                                         $without
     *
     * @return boolean
     */
    protected function isWith($block, $with, $without)
    {
        if (isset($block->type)) {
            if ($block->type === Type::T_MEDIA) {
                return $this->testWithWithout('media', $with, $without);
            }

            if ($block->type === Type::T_DIRECTIVE) {
                if (isset($block->name)) {
                    return $this->testWithWithout($this->compileDirectiveName($block->name), $with, $without);
                } elseif (isset($block->selectors) && preg_match(',@(\w+),ims', json_encode($block->selectors), $m)) {
                    return $this->testWithWithout($m[1], $with, $without);
                } else {
                    return $this->testWithWithout('???', $with, $without);
                }
            }
        } elseif (isset($block->selectors)) {
            // a selector starting with number is a keyframe rule
            if (\count($block->selectors)) {
                $s = reset($block->selectors);

                while (\is_array($s)) {
                    $s = reset($s);
                }

                if (\is_object($s) && $s instanceof Number) {
                    return $this->testWithWithout('keyframes', $with, $without);
                }
            }

            return $this->testWithWithout('rule', $with, $without);
        }

        return true;
    }

    /**
     * Test a single type of block against with/without lists
     *
     * @param string $what
     * @param array  $with
     * @param array  $without
     *
     * @return boolean
     *   true if the block should be kept, false to reject
     */
    protected function testWithWithout($what, $with, $without)
    {
        // if without, reject only if in the list (or 'all' is in the list)
        if (\count($without)) {
            return (isset($without[$what]) || isset($without['all'])) ? false : true;
        }

        // otherwise reject all what is not in the with list
        return (isset($with[$what]) || isset($with['all'])) ? true : false;
    }


    /**
     * Compile keyframe block
     *
     * @param \ScssPhp\ScssPhp\Block $block
     * @param string[]               $selectors
     *
     * @return void
     */
    protected function compileKeyframeBlock(Block $block, $selectors)
    {
        $env = $this->pushEnv($block);

        $envs = $this->compactEnv($env);

        $this->env = $this->extractEnv(array_filter($envs, function (Environment $e) {
            return ! isset($e->block->selectors);
        }));

        $this->scope = $this->makeOutputBlock($block->type, $selectors);
        $this->scope->depth = 1;
        $this->scope->parent->children[] = $this->scope;

        $this->compileChildrenNoReturn($block->children, $this->scope);

        $this->scope = $this->scope->parent;
        $this->env   = $this->extractEnv($envs);

        $this->popEnv();
    }

    /**
     * Compile nested properties lines
     *
     * @param \ScssPhp\ScssPhp\Block                 $block
     * @param \ScssPhp\ScssPhp\Formatter\OutputBlock $out
     *
     * @return void
     */
    protected function compileNestedPropertiesBlock(Block $block, OutputBlock $out)
    {
        $prefix = $this->compileValue($block->prefix) . '-';

        $nested = $this->makeOutputBlock($block->type);
        $nested->parent = $out;

        if ($block->hasValue) {
            $nested->depth = $out->depth + 1;
        }

        $out->children[] = $nested;

        foreach ($block->children as $child) {
            switch ($child[0]) {
                case Type::T_ASSIGN:
                    array_unshift($child[1][2], $prefix);
                    break;

                case Type::T_NESTED_PROPERTY:
                    array_unshift($child[1]->prefix[2], $prefix);
                    break;
            }

            $this->compileChild($child, $nested);
        }
    }

    /**
     * Compile nested block
     *
     * @param \ScssPhp\ScssPhp\Block $block
     * @param string[]               $selectors
     *
     * @return void
     */
    protected function compileNestedBlock(Block $block, $selectors)
    {
        $this->pushEnv($block);

        $this->scope = $this->makeOutputBlock($block->type, $selectors);
        $this->scope->parent->children[] = $this->scope;

        // wrap assign children in a block
        // except for @font-face
        if ($block->type !== Type::T_DIRECTIVE || $this->compileDirectiveName($block->name) !== 'font-face') {
            // need wrapping?
            $needWrapping = false;

            foreach ($block->children as $child) {
                if ($child[0] === Type::T_ASSIGN) {
                    $needWrapping = true;
                    break;
                }
            }

            if ($needWrapping) {
                $wrapped = new Block();
                $wrapped->sourceName   = $block->sourceName;
                $wrapped->sourceIndex  = $block->sourceIndex;
                $wrapped->sourceLine   = $block->sourceLine;
                $wrapped->sourceColumn = $block->sourceColumn;
                $wrapped->selectors    = [];
                $wrapped->comments     = [];
                $wrapped->parent       = $block;
                $wrapped->children     = $block->children;
                $wrapped->selfParent   = $block->selfParent;

                $block->children = [[Type::T_BLOCK, $wrapped]];
            }
        }

        $this->compileChildrenNoReturn($block->children, $this->scope);

        $this->scope = $this->scope->parent;

        $this->popEnv();
    }

    /**
     * Recursively compiles a block.
     *
     * A block is analogous to a CSS block in most cases. A single SCSS document
     * is encapsulated in a block when parsed, but it does not have parent tags
     * so all of its children appear on the root level when compiled.
     *
     * Blocks are made up of selectors and children.
     *
     * The children of a block are just all the blocks that are defined within.
     *
     * Compiling the block involves pushing a fresh environment on the stack,
     * and iterating through the props, compiling each one.
     *
     * @see Compiler::compileChild()
     *
     * @param \ScssPhp\ScssPhp\Block $block
     *
     * @return void
     */
    protected function compileBlock(Block $block)
    {
        $env = $this->pushEnv($block);
        $env->selectors = $this->evalSelectors($block->selectors);

        $out = $this->makeOutputBlock(null);

        $this->scope->children[] = $out;

        if (\count($block->children)) {
            $out->selectors = $this->multiplySelectors($env, $block->selfParent);

            // propagate selfParent to the children where they still can be useful
            $selfParentSelectors = null;

            if (isset($block->selfParent->selectors)) {
                $selfParentSelectors = $block->selfParent->selectors;
                $block->selfParent->selectors = $out->selectors;
            }

            $this->compileChildrenNoReturn($block->children, $out, $block->selfParent);

            // and revert for the following children of the same block
            if ($selfParentSelectors) {
                $block->selfParent->selectors = $selfParentSelectors;
            }
        }

        $this->popEnv();
    }


    /**
     * Compile the value of a comment that can have interpolation
     *
     * @param array   $value
     * @param boolean $pushEnv
     *
     * @return string
     */
    protected function compileCommentValue($value, $pushEnv = false)
    {
        $c = $value[1];

        if (isset($value[2])) {
            if ($pushEnv) {
                $this->pushEnv();
            }

            try {
                $c = $this->compileValue($value[2]);
            } catch (SassScriptException $e) {
                $this->logger->warn('Ignoring interpolation errors in multiline comments is deprecated and will be removed in ScssPhp 2.0. ' . $this->addLocationToMessage($e->getMessage()), true);
                // ignore error in comment compilation which are only interpolation
            } catch (SassException $e) {
                $this->logger->warn('Ignoring interpolation errors in multiline comments is deprecated and will be removed in ScssPhp 2.0. ' . $e->getMessage(), true);
                // ignore error in comment compilation which are only interpolation
            }

            if ($pushEnv) {
                $this->popEnv();
            }
        }

        return $c;
    }

    /**
     * Compile root level comment
     *
     * @param array $block
     *
     * @return void
     */
    protected function compileComment($block)
    {
        $out = $this->makeOutputBlock(Type::T_COMMENT);
        $out->lines[] = $this->compileCommentValue($block, true);

        $this->scope->children[] = $out;
    }

    /**
     * Evaluate selectors
     *
     * @param array $selectors
     *
     * @return array
     */
    protected function evalSelectors($selectors)
    {
        $this->shouldEvaluate = false;

        $selectors = array_map([$this, 'evalSelector'], $selectors);

        // after evaluating interpolates, we might need a second pass
        if ($this->shouldEvaluate) {
            $selectors = $this->replaceSelfSelector($selectors, '&');
            $buffer    = $this->collapseSelectors($selectors);
            $parser    = $this->parserFactory(__METHOD__);

            try {
                $isValid = $parser->parseSelector($buffer, $newSelectors, true);
            } catch (ParserException $e) {
                throw $this->error($e->getMessage());
            }

            if ($isValid) {
                $selectors = array_map([$this, 'evalSelector'], $newSelectors);
            }
        }

        return $selectors;
    }

    /**
     * Evaluate selector
     *
     * @param array $selector
     *
     * @return array
     */
    protected function evalSelector($selector)
    {
        return array_map([$this, 'evalSelectorPart'], $selector);
    }

    /**
     * Evaluate selector part; replaces all the interpolates, stripping quotes
     *
     * @param array $part
     *
     * @return array
     */
    protected function evalSelectorPart($part)
    {
        foreach ($part as &$p) {
            if (\is_array($p) && ($p[0] === Type::T_INTERPOLATE || $p[0] === Type::T_STRING)) {
                $p = $this->compileValue($p);

                // force re-evaluation if self char or non standard char
                if (preg_match(',[^\w-],', $p)) {
                    $this->shouldEvaluate = true;
                }
            } elseif (
                \is_string($p) && \strlen($p) >= 2 &&
                ($first = $p[0]) && ($first === '"' || $first === "'") &&
                substr($p, -1) === $first
            ) {
                $p = substr($p, 1, -1);
            }
        }

        return $this->flattenSelectorSingle($part);
    }

    /**
     * Collapse selectors
     *
     * @param array $selectors
     *
     * @return string
     */
    protected function collapseSelectors($selectors)
    {
        $parts = [];

        foreach ($selectors as $selector) {
            $output = [];

            foreach ($selector as $node) {
                $compound = '';

                array_walk_recursive(
                    $node,
                    function ($value, $key) use (&$compound) {
                        $compound .= $value;
                    }
                );

                $output[] = $compound;
            }

            $parts[] = implode(' ', $output);
        }

        return implode(', ', $parts);
    }

    /**
     * Collapse selectors
     *
     * @param array $selectors
     *
     * @return array
     */
    private function collapseSelectorsAsList($selectors)
    {
        $parts = [];

        foreach ($selectors as $selector) {
            $output = [];
            $glueNext = false;

            foreach ($selector as $node) {
                $compound = '';

                array_walk_recursive(
                    $node,
                    function ($value, $key) use (&$compound) {
                        $compound .= $value;
                    }
                );

                if ($this->isImmediateRelationshipCombinator($compound)) {
                    if (\count($output)) {
                        $output[\count($output) - 1] .= ' ' . $compound;
                    } else {
                        $output[] = $compound;
                    }

                    $glueNext = true;
                } elseif ($glueNext) {
                    $output[\count($output) - 1] .= ' ' . $compound;
                    $glueNext = false;
                } else {
                    $output[] = $compound;
                }
            }

            foreach ($output as &$o) {
                $o = [Type::T_STRING, '', [$o]];
            }

            $parts[] = [Type::T_LIST, ' ', $output];
        }

        return [Type::T_LIST, ',', $parts];
    }

    /**
     * Parse down the selector and revert [self] to "&" before a reparsing
     *
     * @param array       $selectors
     * @param string|null $replace
     *
     * @return array
     */
    protected function replaceSelfSelector($selectors, $replace = null)
    {
        foreach ($selectors as &$part) {
            if (\is_array($part)) {
                if ($part === [Type::T_SELF]) {
                    if (\is_null($replace)) {
                        $replace = $this->reduce([Type::T_SELF]);
                        $replace = $this->compileValue($replace);
                    }
                    $part = $replace;
                } else {
                    $part = $this->replaceSelfSelector($part, $replace);
                }
            }
        }

        return $selectors;
    }

    /**
     * Flatten selector single; joins together .classes and #ids
     *
     * @param array $single
     *
     * @return array
     */
    protected function flattenSelectorSingle($single)
    {
        $joined = [];

        foreach ($single as $part) {
            if (
                empty($joined) ||
                ! \is_string($part) ||
                preg_match('/[\[.:#%]/', $part)
            ) {
                $joined[] = $part;
                continue;
            }

            if (\is_array(end($joined))) {
                $joined[] = $part;
            } else {
                $joined[\count($joined) - 1] .= $part;
            }
        }

        return $joined;
    }

    /**
     * Compile selector to string; self(&) should have been replaced by now
     *
     * @param string|array $selector
     *
     * @return string
     */
    protected function compileSelector($selector)
    {
        if (! \is_array($selector)) {
            return $selector; // media and the like
        }

        return implode(
            ' ',
            array_map(
                [$this, 'compileSelectorPart'],
                $selector
            )
        );
    }

    /**
     * Compile selector part
     *
     * @param array $piece
     *
     * @return string
     */
    protected function compileSelectorPart($piece)
    {
        foreach ($piece as &$p) {
            if (! \is_array($p)) {
                continue;
            }

            switch ($p[0]) {
                case Type::T_SELF:
                    $p = '&';
                    break;

                default:
                    $p = $this->compileValue($p);
                    break;
            }
        }

        return implode($piece);
    }

    /**
     * Has selector placeholder?
     *
     * @param array $selector
     *
     * @return boolean
     */
    protected function hasSelectorPlaceholder($selector)
    {
        if (! \is_array($selector)) {
            return false;
        }

        foreach ($selector as $parts) {
            foreach ($parts as $part) {
                if (\strlen($part) && '%' === $part[0]) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param string $name
     *
     * @return void
     */
    protected function pushCallStack($name = '')
    {
        $this->callStack[] = [
          'n' => $name,
          Parser::SOURCE_INDEX => $this->sourceIndex,
          Parser::SOURCE_LINE => $this->sourceLine,
          Parser::SOURCE_COLUMN => $this->sourceColumn
        ];

        // infinite calling loop
        if (\count($this->callStack) > 25000) {
            // not displayed but you can var_dump it to deep debug
            $msg = $this->callStackMessage(true, 100);
            $msg = 'Infinite calling loop';

            throw $this->error($msg);
        }
    }

    /**
     * @return void
     */
    protected function popCallStack()
    {
        array_pop($this->callStack);
    }

    /**
     * Compile children and return result
     *
     * @param array                                  $stms
     * @param \ScssPhp\ScssPhp\Formatter\OutputBlock $out
     * @param string                                 $traceName
     *
     * @return array|Number|null
     */
    protected function compileChildren($stms, OutputBlock $out, $traceName = '')
    {
        $this->pushCallStack($traceName);

        foreach ($stms as $stm) {
            $ret = $this->compileChild($stm, $out);

            if (isset($ret)) {
                $this->popCallStack();

                return $ret;
            }
        }

        $this->popCallStack();

        return null;
    }

    /**
     * Compile children and throw exception if unexpected `@return`
     *
     * @param array                                  $stms
     * @param \ScssPhp\ScssPhp\Formatter\OutputBlock $out
     * @param \ScssPhp\ScssPhp\Block                 $selfParent
     * @param string                                 $traceName
     *
     * @return void
     *
     * @throws \Exception
     */
    protected function compileChildrenNoReturn($stms, OutputBlock $out, $selfParent = null, $traceName = '')
    {
        $this->pushCallStack($traceName);

        foreach ($stms as $stm) {
            if ($selfParent && isset($stm[1]) && \is_object($stm[1]) && $stm[1] instanceof Block) {
                $stm[1]->selfParent = $selfParent;
                $ret = $this->compileChild($stm, $out);
                $stm[1]->selfParent = null;
            } elseif ($selfParent && \in_array($stm[0], [Type::T_INCLUDE, Type::T_EXTEND])) {
                $stm['selfParent'] = $selfParent;
                $ret = $this->compileChild($stm, $out);
                unset($stm['selfParent']);
            } else {
                $ret = $this->compileChild($stm, $out);
            }

            if (isset($ret)) {
                throw $this->error('@return may only be used within a function');
            }
        }

        $this->popCallStack();
    }


    /**
     * evaluate media query : compile internal value keeping the structure unchanged
     *
     * @param array $queryList
     *
     * @return array
     */
    protected function evaluateMediaQuery($queryList)
    {
        static $parser = null;

        $outQueryList = [];

        foreach ($queryList as $kql => $query) {
            $shouldReparse = false;

            foreach ($query as $kq => $q) {
                for ($i = 1; $i < \count($q); $i++) {
                    $value = $this->compileValue($q[$i]);

                    // the parser had no mean to know if media type or expression if it was an interpolation
                    // so you need to reparse if the T_MEDIA_TYPE looks like anything else a media type
                    if (
                        $q[0] == Type::T_MEDIA_TYPE &&
                        (strpos($value, '(') !== false ||
                        strpos($value, ')') !== false ||
                        strpos($value, ':') !== false ||
                        strpos($value, ',') !== false)
                    ) {
                        $shouldReparse = true;
                    }

                    $queryList[$kql][$kq][$i] = [Type::T_KEYWORD, $value];
                }
            }

            if ($shouldReparse) {
                if (\is_null($parser)) {
                    $parser = $this->parserFactory(__METHOD__);
                }

                $queryString = $this->compileMediaQuery([$queryList[$kql]]);
                $queryString = reset($queryString);

                if (strpos($queryString, '@media ') === 0) {
                    $queryString = substr($queryString, 7);
                    $queries = [];

                    if ($parser->parseMediaQueryList($queryString, $queries)) {
                        $queries = $this->evaluateMediaQuery($queries[2]);

                        while (\count($queries)) {
                            $outQueryList[] = array_shift($queries);
                        }

                        continue;
                    }
                }
            }

            $outQueryList[] = $queryList[$kql];
        }

        return $outQueryList;
    }

    /**
     * Compile media query
     *
     * @param array $queryList
     *
     * @return string[]
     */
    protected function compileMediaQuery($queryList)
    {
        $start   = '@media ';
        $default = trim($start);
        $out     = [];
        $current = '';

        foreach ($queryList as $query) {
            $type = null;
            $parts = [];

            $mediaTypeOnly = true;

            foreach ($query as $q) {
                if ($q[0] !== Type::T_MEDIA_TYPE) {
                    $mediaTypeOnly = false;
                    break;
                }
            }

            foreach ($query as $q) {
                switch ($q[0]) {
                    case Type::T_MEDIA_TYPE:
                        $newType = array_map([$this, 'compileValue'], \array_slice($q, 1));

                        // combining not and anything else than media type is too risky and should be avoided
                        if (! $mediaTypeOnly) {
                            if (\in_array(Type::T_NOT, $newType) || ($type && \in_array(Type::T_NOT, $type) )) {
                                if ($type) {
                                    array_unshift($parts, implode(' ', array_filter($type)));
                                }

                                if (! empty($parts)) {
                                    if (\strlen($current)) {
                                        $current .= $this->formatter->tagSeparator;
                                    }

                                    $current .= implode(' and ', $parts);
                                }

                                if ($current) {
                                    $out[] = $start . $current;
                                }

                                $current = '';
                                $type    = null;
                                $parts   = [];
                            }
                        }

                        if ($newType === ['all'] && $default) {
                            $default = $start . 'all';
                        }

                        // all can be safely ignored and mixed with whatever else
                        if ($newType !== ['all']) {
                            if ($type) {
                                $type = $this->mergeMediaTypes($type, $newType);

                                if (empty($type)) {
                                    // merge failed : ignore this query that is not valid, skip to the next one
                                    $parts = [];
                                    $default = ''; // if everything fail, no @media at all
                                    continue 3;
                                }
                            } else {
                                $type = $newType;
                            }
                        }
                        break;

                    case Type::T_MEDIA_EXPRESSION:
                        if (isset($q[2])) {
                            $parts[] = '('
                                . $this->compileValue($q[1])
                                . $this->formatter->assignSeparator
                                . $this->compileValue($q[2])
                                . ')';
                        } else {
                            $parts[] = '('
                                . $this->compileValue($q[1])
                                . ')';
                        }
                        break;

                    case Type::T_MEDIA_VALUE:
                        $parts[] = $this->compileValue($q[1]);
                        break;
                }
            }

            if ($type) {
                array_unshift($parts, implode(' ', array_filter($type)));
            }

            if (! empty($parts)) {
                if (\strlen($current)) {
                    $current .= $this->formatter->tagSeparator;
                }

                $current .= implode(' and ', $parts);
            }
        }

        if ($current) {
            $out[] = $start . $current;
        }

        // no @media type except all, and no conflict?
        if (! $out && $default) {
            $out[] = $default;
        }

        return $out;
    }

    /**
     * Merge direct relationships between selectors
     *
     * @param array $selectors1
     * @param array $selectors2
     *
     * @return array
     */
    protected function mergeDirectRelationships($selectors1, $selectors2)
    {
        if (empty($selectors1) || empty($selectors2)) {
            return array_merge($selectors1, $selectors2);
        }

        $part1 = end($selectors1);
        $part2 = end($selectors2);

        if (! $this->isImmediateRelationshipCombinator($part1[0]) && $part1 !== $part2) {
            return array_merge($selectors1, $selectors2);
        }

        $merged = [];

        do {
            $part1 = array_pop($selectors1);
            $part2 = array_pop($selectors2);

            if (! $this->isImmediateRelationshipCombinator($part1[0]) && $part1 !== $part2) {
                if ($this->isImmediateRelationshipCombinator(reset($merged)[0])) {
                    array_unshift($merged, [$part1[0] . $part2[0]]);
                    $merged = array_merge($selectors1, $selectors2, $merged);
                } else {
                    $merged = array_merge($selectors1, [$part1], $selectors2, [$part2], $merged);
                }

                break;
            }

            array_unshift($merged, $part1);
        } while (! empty($selectors1) && ! empty($selectors2));

        return $merged;
    }

    /**
     * Merge media types
     *
     * @param array $type1
     * @param array $type2
     *
     * @return array|null
     */
    protected function mergeMediaTypes($type1, $type2)
    {
        if (empty($type1)) {
            return $type2;
        }

        if (empty($type2)) {
            return $type1;
        }

        if (\count($type1) > 1) {
            $m1 = strtolower($type1[0]);
            $t1 = strtolower($type1[1]);
        } else {
            $m1 = '';
            $t1 = strtolower($type1[0]);
        }

        if (\count($type2) > 1) {
            $m2 = strtolower($type2[0]);
            $t2 = strtolower($type2[1]);
        } else {
            $m2 = '';
            $t2 = strtolower($type2[0]);
        }

        if (($m1 === Type::T_NOT) ^ ($m2 === Type::T_NOT)) {
            if ($t1 === $t2) {
                return null;
            }

            return [
                $m1 === Type::T_NOT ? $m2 : $m1,
                $m1 === Type::T_NOT ? $t2 : $t1,
            ];
        }

        if ($m1 === Type::T_NOT && $m2 === Type::T_NOT) {
            // CSS has no way of representing "neither screen nor print"
            if ($t1 !== $t2) {
                return null;
            }

            return [Type::T_NOT, $t1];
        }

        if ($t1 !== $t2) {
            return null;
        }

        // t1 == t2, neither m1 nor m2 are "not"
        return [empty($m1) ? $m2 : $m1, $t1];
    }

    /**
     * Compile import; returns true if the value was something that could be imported
     *
     * @param array                                  $rawPath
     * @param \ScssPhp\ScssPhp\Formatter\OutputBlock $out
     * @param boolean                                $once
     *
     * @return boolean
     */
    protected function compileImport($rawPath, OutputBlock $out, $once = false)
    {
        if ($rawPath[0] === Type::T_STRING) {
            $path = $this->compileStringContent($rawPath);

            if (strpos($path, 'url(') !== 0 && $filePath = $this->findImport($path, $this->currentDirectory)) {
                $this->registerImport($this->currentDirectory, $path, $filePath);

                if (! $once || ! \in_array($filePath, $this->importedFiles)) {
                    $this->importFile($filePath, $out);
                    $this->importedFiles[] = $filePath;
                }

                return true;
            }

            $this->appendRootDirective('@import ' . $this->compileImportPath($rawPath) . ';', $out);

            return false;
        }

        if ($rawPath[0] === Type::T_LIST) {
            // handle a list of strings
            if (\count($rawPath[2]) === 0) {
                return false;
            }

            foreach ($rawPath[2] as $path) {
                if ($path[0] !== Type::T_STRING) {
                    $this->appendRootDirective('@import ' . $this->compileImportPath($rawPath) . ';', $out);

                    return false;
                }
            }

            foreach ($rawPath[2] as $path) {
                $this->compileImport($path, $out, $once);
            }

            return true;
        }

        $this->appendRootDirective('@import ' . $this->compileImportPath($rawPath) . ';', $out);

        return false;
    }

    /**
     * @param array $rawPath
     * @return string
     * @throws CompilerException
     */
    protected function compileImportPath($rawPath)
    {
        $path = $this->compileValue($rawPath);

        // case url() without quotes : suppress \r \n remaining in the path
        // if this is a real string there can not be CR or LF char
        if (strpos($path, 'url(') === 0) {
            $path = str_replace(array("\r", "\n"), array('', ' '), $path);
        } else {
            // if this is a file name in a string, spaces should be escaped
            $path = $this->reduce($rawPath);
            $path = $this->escapeImportPathString($path);
            $path = $this->compileValue($path);
        }

        return $path;
    }

    /**
     * @param array $path
     * @return array
     * @throws CompilerException
     */
    protected function escapeImportPathString($path)
    {
        switch ($path[0]) {
            case Type::T_LIST:
                foreach ($path[2] as $k => $v) {
                    $path[2][$k] = $this->escapeImportPathString($v);
                }
                break;
            case Type::T_STRING:
                if ($path[1]) {
                    $path = $this->compileValue($path);
                    $path = str_replace(' ', '\\ ', $path);
                    $path = [Type::T_KEYWORD, $path];
                }
                break;
        }

        return $path;
    }

    /**
     * Append a root directive like @import or @charset as near as the possible from the source code
     * (keeping before comments, @import and @charset coming before in the source code)
     *
     * @param string                                 $line
     * @param \ScssPhp\ScssPhp\Formatter\OutputBlock $out
     * @param array                                  $allowed
     *
     * @return void
     */
    protected function appendRootDirective($line, $out, $allowed = [Type::T_COMMENT])
    {
        $root = $out;

        while ($root->parent) {
            $root = $root->parent;
        }

        $i = 0;

        while ($i < \count($root->children)) {
            if (! isset($root->children[$i]->type) || ! \in_array($root->children[$i]->type, $allowed)) {
                break;
            }

            $i++;
        }

        // remove incompatible children from the bottom of the list
        $saveChildren = [];

        while ($i < \count($root->children)) {
            $saveChildren[] = array_pop($root->children);
        }

        // insert the directive as a comment
        $child = $this->makeOutputBlock(Type::T_COMMENT);
        $child->lines[]      = $line;
        $child->sourceName   = $this->sourceNames[$this->sourceIndex];
        $child->sourceLine   = $this->sourceLine;
        $child->sourceColumn = $this->sourceColumn;

        $root->children[] = $child;

        // repush children
        while (\count($saveChildren)) {
            $root->children[] = array_pop($saveChildren);
        }
    }

    /**
     * Append lines to the current output block:
     * directly to the block or through a child if necessary
     *
     * @param \ScssPhp\ScssPhp\Formatter\OutputBlock $out
     * @param string                                 $type
     * @param string                                 $line
     *
     * @return void
     */
    protected function appendOutputLine(OutputBlock $out, $type, $line)
    {
        $outWrite = &$out;

        // check if it's a flat output or not
        if (\count($out->children)) {
            $lastChild = &$out->children[\count($out->children) - 1];

            if (
                $lastChild->depth === $out->depth &&
                \is_null($lastChild->selectors) &&
                ! \count($lastChild->children)
            ) {
                $outWrite = $lastChild;
            } else {
                $nextLines = $this->makeOutputBlock($type);
                $nextLines->parent = $out;
                $nextLines->depth  = $out->depth;

                $out->children[] = $nextLines;
                $outWrite = &$nextLines;
            }
        }

        $outWrite->lines[] = $line;
    }

    /**
     * Compile child; returns a value to halt execution
     *
     * @param array                                  $child
     * @param \ScssPhp\ScssPhp\Formatter\OutputBlock $out
     *
     * @return array|Number|null
     */
    protected function compileChild($child, OutputBlock $out)
    {
        if (isset($child[Parser::SOURCE_LINE])) {
            $this->sourceIndex  = isset($child[Parser::SOURCE_INDEX]) ? $child[Parser::SOURCE_INDEX] : null;
            $this->sourceLine   = isset($child[Parser::SOURCE_LINE]) ? $child[Parser::SOURCE_LINE] : -1;
            $this->sourceColumn = isset($child[Parser::SOURCE_COLUMN]) ? $child[Parser::SOURCE_COLUMN] : -1;
        } elseif (\is_array($child) && isset($child[1]->sourceLine)) {
            $this->sourceIndex  = $child[1]->sourceIndex;
            $this->sourceLine   = $child[1]->sourceLine;
            $this->sourceColumn = $child[1]->sourceColumn;
        } elseif (! empty($out->sourceLine) && ! empty($out->sourceName)) {
            $this->sourceLine   = $out->sourceLine;
            $sourceIndex  = array_search($out->sourceName, $this->sourceNames);
            $this->sourceColumn = $out->sourceColumn;

            if ($sourceIndex === false) {
                $sourceIndex = null;
            }
            $this->sourceIndex = $sourceIndex;
        }

        switch ($child[0]) {
            case Type::T_SCSSPHP_IMPORT_ONCE:
                $rawPath = $this->reduce($child[1]);

                $this->compileImport($rawPath, $out, true);
                break;

            case Type::T_IMPORT:
                $rawPath = $this->reduce($child[1]);

                $this->compileImport($rawPath, $out);
                break;

            case Type::T_DIRECTIVE:
                $this->compileDirective($child[1], $out);
                break;

            case Type::T_AT_ROOT:
                $this->compileAtRoot($child[1]);
                break;

            case Type::T_MEDIA:
                $this->compileMedia($child[1]);
                break;

            case Type::T_BLOCK:
                $this->compileBlock($child[1]);
                break;

            case Type::T_CHARSET:
                if (! $this->charsetSeen) {
                    $this->charsetSeen = true;
                    $this->appendRootDirective('@charset ' . $this->compileValue($child[1]) . ';', $out);
                }
                break;

            case Type::T_CUSTOM_PROPERTY:
                list(, $name, $value) = $child;
                $compiledName = $this->compileValue($name);

                // if the value reduces to null from something else then
                // the property should be discarded
                if ($value[0] !== Type::T_NULL) {
                    $value = $this->reduce($value);

                    if ($value[0] === Type::T_NULL || $value === static::$nullString) {
                        break;
                    }
                }

                $compiledValue = $this->compileValue($value);

                $line = $this->formatter->customProperty(
                    $compiledName,
                    $compiledValue
                );

                $this->appendOutputLine($out, Type::T_ASSIGN, $line);
                break;

            case Type::T_ASSIGN:
                list(, $name, $value) = $child;

                if ($name[0] === Type::T_VARIABLE) {
                    $flags     = isset($child[3]) ? $child[3] : [];
                    $isDefault = \in_array('!default', $flags);
                    $isGlobal  = \in_array('!global', $flags);

                    if ($isGlobal) {
                        $this->set($name[1], $this->reduce($value), false, $this->rootEnv, $value);
                        break;
                    }

                    $shouldSet = $isDefault &&
                        (\is_null($result = $this->get($name[1], false)) ||
                        $result === static::$null);

                    if (! $isDefault || $shouldSet) {
                        $this->set($name[1], $this->reduce($value), true, null, $value);
                    }
                    break;
                }

                $compiledName = $this->compileValue($name);

                // handle shorthand syntaxes : size / line-height...
                if (\in_array($compiledName, ['font', 'grid-row', 'grid-column', 'border-radius'])) {
                    if ($value[0] === Type::T_VARIABLE) {
                        // if the font value comes from variable, the content is already reduced
                        // (i.e., formulas were already calculated), so we need the original unreduced value
                        $value = $this->get($value[1], true, null, true);
                    }

                    $shorthandValue=&$value;

                    $shorthandDividerNeedsUnit = false;
                    $maxListElements           = null;
                    $maxShorthandDividers      = 1;

                    switch ($compiledName) {
                        case 'border-radius':
                            $maxListElements = 4;
                            $shorthandDividerNeedsUnit = true;
                            break;
                    }

                    if ($compiledName === 'font' && $value[0] === Type::T_LIST && $value[1] === ',') {
                        // this is the case if more than one font is given: example: "font: 400 1em/1.3 arial,helvetica"
                        // we need to handle the first list element
                        $shorthandValue=&$value[2][0];
                    }

                    if ($shorthandValue[0] === Type::T_EXPRESSION && $shorthandValue[1] === '/') {
                        $revert = true;

                        if ($shorthandDividerNeedsUnit) {
                            $divider = $shorthandValue[3];

                            if (\is_array($divider)) {
                                $divider = $this->reduce($divider, true);
                            }

                            if ($divider instanceof Number && \intval($divider->getDimension()) && $divider->unitless()) {
                                $revert = false;
                            }
                        }

                        if ($revert) {
                            $shorthandValue = $this->expToString($shorthandValue);
                        }
                    } elseif ($shorthandValue[0] === Type::T_LIST) {
                        foreach ($shorthandValue[2] as &$item) {
                            if ($item[0] === Type::T_EXPRESSION && $item[1] === '/') {
                                if ($maxShorthandDividers > 0) {
                                    $revert = true;

                                    // if the list of values is too long, this has to be a shorthand,
                                    // otherwise it could be a real division
                                    if (\is_null($maxListElements) || \count($shorthandValue[2]) <= $maxListElements) {
                                        if ($shorthandDividerNeedsUnit) {
                                            $divider = $item[3];

                                            if (\is_array($divider)) {
                                                $divider = $this->reduce($divider, true);
                                            }

                                            if ($divider instanceof Number && \intval($divider->getDimension()) && $divider->unitless()) {
                                                $revert = false;
                                            }
                                        }
                                    }

                                    if ($revert) {
                                        $item = $this->expToString($item);
                                        $maxShorthandDividers--;
                                    }
                                }
                            }
                        }
                    }
                }

                // if the value reduces to null from something else then
                // the property should be discarded
                if ($value[0] !== Type::T_NULL) {
                    $value = $this->reduce($value);

                    if ($value[0] === Type::T_NULL || $value === static::$nullString) {
                        break;
                    }
                }

                $compiledValue = $this->compileValue($value);

                // ignore empty value
                if (\strlen($compiledValue)) {
                    $line = $this->formatter->property(
                        $compiledName,
                        $compiledValue
                    );
                    $this->appendOutputLine($out, Type::T_ASSIGN, $line);
                }
                break;

            case Type::T_COMMENT:
                if ($out->type === Type::T_ROOT) {
                    $this->compileComment($child);
                    break;
                }

                $line = $this->compileCommentValue($child, true);
                $this->appendOutputLine($out, Type::T_COMMENT, $line);
                break;

            case Type::T_MIXIN:
            case Type::T_FUNCTION:
                list(, $block) = $child;
                // the block need to be able to go up to it's parent env to resolve vars
                $block->parentEnv = $this->getStoreEnv();
                $this->set(static::$namespaces[$block->type] . $block->name, $block, true);
                break;

            case Type::T_EXTEND:
                foreach ($child[1] as $sel) {
                    $replacedSel = $this->replaceSelfSelector($sel);

                    if ($replacedSel !== $sel) {
                        throw $this->error('Parent selectors aren\'t allowed here.');
                    }

                    $results = $this->evalSelectors([$sel]);

                    foreach ($results as $result) {
                        if (\count($result) !== 1) {
                            throw $this->error('complex selectors may not be extended.');
                        }

                        // only use the first one
                        $result = $result[0];
                        $selectors = $out->selectors;

                        if (! $selectors && isset($child['selfParent'])) {
                            $selectors = $this->multiplySelectors($this->env, $child['selfParent']);
                        }

                        if (\count($result) > 1) {
                            $replacement = implode(', ', $result);
                            $fname = $this->getPrettyPath($this->sourceNames[$this->sourceIndex]);
                            $line = $this->sourceLine;

                            $message = <<<EOL
on line $line of $fname:
Compound selectors may no longer be extended.
Consider `@extend $replacement` instead.
See http://bit.ly/ExtendCompound for details.
EOL;

                            $this->logger->warn($message);
                        }

                        $this->pushExtends($result, $selectors, $child);
                    }
                }
                break;

            case Type::T_IF:
                list(, $if) = $child;

                if ($this->isTruthy($this->reduce($if->cond, true))) {
                    return $this->compileChildren($if->children, $out);
                }

                foreach ($if->cases as $case) {
                    if (
                        $case->type === Type::T_ELSE ||
                        $case->type === Type::T_ELSEIF && $this->isTruthy($this->reduce($case->cond))
                    ) {
                        return $this->compileChildren($case->children, $out);
                    }
                }
                break;

            case Type::T_EACH:
                list(, $each) = $child;

                $list = $this->coerceList($this->reduce($each->list), ',', true);

                $this->pushEnv();

                foreach ($list[2] as $item) {
                    if (\count($each->vars) === 1) {
                        $this->set($each->vars[0], $item, true);
                    } else {
                        list(,, $values) = $this->coerceList($item);

                        foreach ($each->vars as $i => $var) {
                            $this->set($var, isset($values[$i]) ? $values[$i] : static::$null, true);
                        }
                    }

                    $ret = $this->compileChildren($each->children, $out);

                    if ($ret) {
                        $store = $this->env->store;
                        $this->popEnv();
                        $this->backPropagateEnv($store, $each->vars);

                        return $ret;
                    }
                }
                $store = $this->env->store;
                $this->popEnv();
                $this->backPropagateEnv($store, $each->vars);

                break;

            case Type::T_WHILE:
                list(, $while) = $child;

                while ($this->isTruthy($this->reduce($while->cond, true))) {
                    $ret = $this->compileChildren($while->children, $out);

                    if ($ret) {
                        return $ret;
                    }
                }
                break;

            case Type::T_FOR:
                list(, $for) = $child;

                $startNumber = $this->assertNumber($this->reduce($for->start, true));
                $endNumber = $this->assertNumber($this->reduce($for->end, true));

                $start = $this->assertInteger($startNumber);

                $numeratorUnits = $startNumber->getNumeratorUnits();
                $denominatorUnits = $startNumber->getDenominatorUnits();

                $end = $this->assertInteger($endNumber->coerce($numeratorUnits, $denominatorUnits));

                $d = $start < $end ? 1 : -1;

                $this->pushEnv();

                for (;;) {
                    if (
                        (! $for->until && $start - $d == $end) ||
                        ($for->until && $start == $end)
                    ) {
                        break;
                    }

                    $this->set($for->var, new Number($start, $numeratorUnits, $denominatorUnits));
                    $start += $d;

                    $ret = $this->compileChildren($for->children, $out);

                    if ($ret) {
                        $store = $this->env->store;
                        $this->popEnv();
                        $this->backPropagateEnv($store, [$for->var]);

                        return $ret;
                    }
                }

                $store = $this->env->store;
                $this->popEnv();
                $this->backPropagateEnv($store, [$for->var]);

                break;

            case Type::T_RETURN:
                return $this->reduce($child[1], true);

            case Type::T_NESTED_PROPERTY:
                $this->compileNestedPropertiesBlock($child[1], $out);
                break;

            case Type::T_INCLUDE:
                // including a mixin
                list(, $name, $argValues, $content, $argUsing) = $child;

                $mixin = $this->get(static::$namespaces['mixin'] . $name, false);

                if (! $mixin) {
                    throw $this->error("Undefined mixin $name");
                }

                $callingScope = $this->getStoreEnv();

                // push scope, apply args
                $this->pushEnv();
                $this->env->depth--;

                // Find the parent selectors in the env to be able to know what '&' refers to in the mixin
                // and assign this fake parent to childs
                $selfParent = null;

                if (isset($child['selfParent']) && isset($child['selfParent']->selectors)) {
                    $selfParent = $child['selfParent'];
                } else {
                    $parentSelectors = $this->multiplySelectors($this->env);

                    if ($parentSelectors) {
                        $parent = new Block();
                        $parent->selectors = $parentSelectors;

                        foreach ($mixin->children as $k => $child) {
                            if (isset($child[1]) && \is_object($child[1]) && $child[1] instanceof Block) {
                                $mixin->children[$k][1]->parent = $parent;
                            }
                        }
                    }
                }

                // clone the stored content to not have its scope spoiled by a further call to the same mixin
                // i.e., recursive @include of the same mixin
                if (isset($content)) {
                    $copyContent = clone $content;
                    $copyContent->scope = clone $callingScope;

                    $this->setRaw(static::$namespaces['special'] . 'content', $copyContent, $this->env);
                } else {
                    $this->setRaw(static::$namespaces['special'] . 'content', null, $this->env);
                }

                // save the "using" argument list for applying it to when "@content" is invoked
                if (isset($argUsing)) {
                    $this->setRaw(static::$namespaces['special'] . 'using', $argUsing, $this->env);
                } else {
                    $this->setRaw(static::$namespaces['special'] . 'using', null, $this->env);
                }

                if (isset($mixin->args)) {
                    $this->applyArguments($mixin->args, $argValues);
                }

                $this->env->marker = 'mixin';

                if (! empty($mixin->parentEnv)) {
                    $this->env->declarationScopeParent = $mixin->parentEnv;
                } else {
                    throw $this->error("@mixin $name() without parentEnv");
                }

                $this->compileChildrenNoReturn($mixin->children, $out, $selfParent, $this->env->marker . ' ' . $name);

                $this->popEnv();
                break;

            case Type::T_MIXIN_CONTENT:
                $env        = isset($this->storeEnv) ? $this->storeEnv : $this->env;
                $content    = $this->get(static::$namespaces['special'] . 'content', false, $env);
                $argUsing   = $this->get(static::$namespaces['special'] . 'using', false, $env);
                $argContent = $child[1];

                if (! $content) {
                    break;
                }

                $storeEnv = $this->storeEnv;
                $varsUsing = [];

                if (isset($argUsing) && isset($argContent)) {
                    // Get the arguments provided for the content with the names provided in the "using" argument list
                    $this->storeEnv = null;
                    $varsUsing = $this->applyArguments($argUsing, $argContent, false);
                }

                // restore the scope from the @content
                $this->storeEnv = $content->scope;

                // append the vars from using if any
                foreach ($varsUsing as $name => $val) {
                    $this->set($name, $val, true, $this->storeEnv);
                }

                $this->compileChildrenNoReturn($content->children, $out);

                $this->storeEnv = $storeEnv;
                break;

            case Type::T_DEBUG:
                list(, $value) = $child;

                $fname = $this->getPrettyPath($this->sourceNames[$this->sourceIndex]);
                $line  = $this->sourceLine;
                $value = $this->compileDebugValue($value);

                $this->logger->debug("$fname:$line DEBUG: $value");
                break;

            case Type::T_WARN:
                list(, $value) = $child;

                $fname = $this->getPrettyPath($this->sourceNames[$this->sourceIndex]);
                $line  = $this->sourceLine;
                $value = $this->compileDebugValue($value);

                $this->logger->warn("$value\n         on line $line of $fname");
                break;

            case Type::T_ERROR:
                list(, $value) = $child;

                $fname = $this->getPrettyPath($this->sourceNames[$this->sourceIndex]);
                $line  = $this->sourceLine;
                $value = $this->compileValue($this->reduce($value, true));

                throw $this->error("File $fname on line $line ERROR: $value\n");

            default:
                throw $this->error("unknown child type: $child[0]");
        }
    }

    /**
     * Reduce expression to string
     *
     * @param array $exp
     * @param bool $keepParens
     *
     * @return array
     */
    protected function expToString($exp, $keepParens = false)
    {
        list(, $op, $left, $right, $inParens, $whiteLeft, $whiteRight) = $exp;

        $content = [];

        if ($keepParens && $inParens) {
            $content[] = '(';
        }

        $content[] = $this->reduce($left);

        if ($whiteLeft) {
            $content[] = ' ';
        }

        $content[] = $op;

        if ($whiteRight) {
            $content[] = ' ';
        }

        $content[] = $this->reduce($right);

        if ($keepParens && $inParens) {
            $content[] = ')';
        }

        return [Type::T_STRING, '', $content];
    }

    /**
     * Is truthy?
     *
     * @param array|Number $value
     *
     * @return boolean
     */
    public function isTruthy($value)
    {
        return $value !== static::$false && $value !== static::$null;
    }

    /**
     * Is the value a direct relationship combinator?
     *
     * @param string $value
     *
     * @return boolean
     */
    protected function isImmediateRelationshipCombinator($value)
    {
        return $value === '>' || $value === '+' || $value === '~';
    }

    /**
     * Should $value cause its operand to eval
     *
     * @param array $value
     *
     * @return boolean
     */
    protected function shouldEval($value)
    {
        switch ($value[0]) {
            case Type::T_EXPRESSION:
                if ($value[1] === '/') {
                    return $this->shouldEval($value[2]) || $this->shouldEval($value[3]);
                }

                // fall-thru
            case Type::T_VARIABLE:
            case Type::T_FUNCTION_CALL:
                return true;
        }

        return false;
    }

    /**
     * Reduce value
     *
     * @param array|Number $value
     * @param boolean $inExp
     *
     * @return array|Number
     */
    protected function reduce($value, $inExp = false)
    {
        if ($value instanceof Number) {
            return $value;
        }

        switch ($value[0]) {
            case Type::T_EXPRESSION:
                list(, $op, $left, $right, $inParens) = $value;

                $opName = isset(static::$operatorNames[$op]) ? static::$operatorNames[$op] : $op;
                $inExp = $inExp || $this->shouldEval($left) || $this->shouldEval($right);

                $left = $this->reduce($left, true);

                if ($op !== 'and' && $op !== 'or') {
                    $right = $this->reduce($right, true);
                }

                // special case: looks like css shorthand
                if (
                    $opName == 'div' && ! $inParens && ! $inExp &&
                    (($right[0] !== Type::T_NUMBER && isset($right[2]) && $right[2] != '') ||
                    ($right[0] === Type::T_NUMBER && ! $right->unitless()))
                ) {
                    return $this->expToString($value);
                }

                $left  = $this->coerceForExpression($left);
                $right = $this->coerceForExpression($right);
                $ltype = $left[0];
                $rtype = $right[0];

                $ucOpName = ucfirst($opName);
                $ucLType  = ucfirst($ltype);
                $ucRType  = ucfirst($rtype);

                // this tries:
                // 1. op[op name][left type][right type]
                // 2. op[left type][right type] (passing the op as first arg
                // 3. op[op name]
                $fn = "op${ucOpName}${ucLType}${ucRType}";

                if (
                    \is_callable([$this, $fn]) ||
                    (($fn = "op${ucLType}${ucRType}") &&
                        \is_callable([$this, $fn]) &&
                        $passOp = true) ||
                    (($fn = "op${ucOpName}") &&
                        \is_callable([$this, $fn]) &&
                        $genOp = true)
                ) {
                    $shouldEval = $inParens || $inExp;

                    if (isset($passOp)) {
                        $out = $this->$fn($op, $left, $right, $shouldEval);
                    } else {
                        $out = $this->$fn($left, $right, $shouldEval);
                    }

                    if (isset($out)) {
                        return $out;
                    }
                }

                return $this->expToString($value);

            case Type::T_UNARY:
                list(, $op, $exp, $inParens) = $value;

                $inExp = $inExp || $this->shouldEval($exp);
                $exp = $this->reduce($exp);

                if ($exp instanceof Number) {
                    switch ($op) {
                        case '+':
                            return $exp;

                        case '-':
                            return $exp->unaryMinus();
                    }
                }

                if ($op === 'not') {
                    if ($inExp || $inParens) {
                        if ($exp === static::$false || $exp === static::$null) {
                            return static::$true;
                        }

                        return static::$false;
                    }

                    $op = $op . ' ';
                }

                return [Type::T_STRING, '', [$op, $exp]];

            case Type::T_VARIABLE:
                return $this->reduce($this->get($value[1]));

            case Type::T_LIST:
                foreach ($value[2] as &$item) {
                    $item = $this->reduce($item);
                }
                unset($item);

                if (isset($value[3]) && \is_array($value[3])) {
                    foreach ($value[3] as &$item) {
                        $item = $this->reduce($item);
                    }
                    unset($item);
                }

                return $value;

            case Type::T_MAP:
                foreach ($value[1] as &$item) {
                    $item = $this->reduce($item);
                }

                foreach ($value[2] as &$item) {
                    $item = $this->reduce($item);
                }

                return $value;

            case Type::T_STRING:
                foreach ($value[2] as &$item) {
                    if (\is_array($item) || $item instanceof Number) {
                        $item = $this->reduce($item);
                    }
                }

                return $value;

            case Type::T_INTERPOLATE:
                $value[1] = $this->reduce($value[1]);

                if ($inExp) {
                    return [Type::T_KEYWORD, $this->compileValue($value, false)];
                }

                return $value;

            case Type::T_FUNCTION_CALL:
                return $this->fncall($value[1], $value[2]);

            case Type::T_SELF:
                $selfParent = ! empty($this->env->block->selfParent) ? $this->env->block->selfParent : null;
                $selfSelector = $this->multiplySelectors($this->env, $selfParent);
                $selfSelector = $this->collapseSelectorsAsList($selfSelector);

                return $selfSelector;

            default:
                return $value;
        }
    }

    /**
     * Function caller
     *
     * @param string|array $functionReference
     * @param array        $argValues
     *
     * @return array|Number
     */
    protected function fncall($functionReference, $argValues)
    {
        // a string means this is a static hard reference coming from the parsing
        if (is_string($functionReference)) {
            $name = $functionReference;

            $functionReference = $this->getFunctionReference($name);
            if ($functionReference === static::$null || $functionReference[0] !== Type::T_FUNCTION_REFERENCE) {
                $functionReference = [Type::T_FUNCTION, $name, [Type::T_LIST, ',', []]];
            }
        }

        // a function type means we just want a plain css function call
        if ($functionReference[0] === Type::T_FUNCTION) {
            // for CSS functions, simply flatten the arguments into a list
            $listArgs = [];

            foreach ((array) $argValues as $arg) {
                if (empty($arg[0]) || count($argValues) === 1) {
                    $listArgs[] = $this->reduce($this->stringifyFncallArgs($arg[1]));
                }
            }

            return [Type::T_FUNCTION, $functionReference[1], [Type::T_LIST, ',', $listArgs]];
        }

        if ($functionReference === static::$null || $functionReference[0] !== Type::T_FUNCTION_REFERENCE) {
            return static::$defaultValue;
        }


        switch ($functionReference[1]) {
            // SCSS @function
            case 'scss':
                return $this->callScssFunction($functionReference[3], $argValues);

            // native PHP functions
            case 'user':
            case 'native':
                list(,,$name, $fn, $prototype) = $functionReference;

                // special cases of css valid functions min/max
                $name = strtolower($name);
                if (\in_array($name, ['min', 'max']) && count($argValues) >= 1) {
                    $cssFunction = $this->cssValidArg(
                        [Type::T_FUNCTION_CALL, $name, $argValues],
                        ['min', 'max', 'calc', 'env', 'var']
                    );
                    if ($cssFunction !== false) {
                        return $cssFunction;
                    }
                }
                $returnValue = $this->callNativeFunction($name, $fn, $prototype, $argValues);

                if (! isset($returnValue)) {
                    return $this->fncall([Type::T_FUNCTION, $name, [Type::T_LIST, ',', []]], $argValues);
                }

                return $returnValue;

            default:
                return static::$defaultValue;
        }
    }

    /**
     * @param array|Number $arg
     * @param string[]     $allowed_function
     * @param bool         $inFunction
     *
     * @return array|Number|false
     */
    protected function cssValidArg($arg, $allowed_function = [], $inFunction = false)
    {
        if ($arg instanceof Number) {
            return $this->stringifyFncallArgs($arg);
        }

        switch ($arg[0]) {
            case Type::T_INTERPOLATE:
                return [Type::T_KEYWORD, $this->CompileValue($arg)];

            case Type::T_FUNCTION:
                if (! \in_array($arg[1], $allowed_function)) {
                    return false;
                }
                if ($arg[2][0] === Type::T_LIST) {
                    foreach ($arg[2][2] as $k => $subarg) {
                        $arg[2][2][$k] = $this->cssValidArg($subarg, $allowed_function, $arg[1]);
                        if ($arg[2][2][$k] === false) {
                            return false;
                        }
                    }
                }
                return $arg;

            case Type::T_FUNCTION_CALL:
                if (! \in_array($arg[1], $allowed_function)) {
                    return false;
                }
                $cssArgs = [];
                foreach ($arg[2] as $argValue) {
                    if ($argValue === static::$null) {
                        return false;
                    }
                    $cssArg = $this->cssValidArg($argValue[1], $allowed_function, $arg[1]);
                    if (empty($argValue[0]) && $cssArg !== false) {
                        $cssArgs[] = [$argValue[0], $cssArg];
                    } else {
                        return false;
                    }
                }

                return $this->fncall([Type::T_FUNCTION, $arg[1], [Type::T_LIST, ',', []]], $cssArgs);

            case Type::T_STRING:
            case Type::T_KEYWORD:
                if (!$inFunction or !\in_array($inFunction, ['calc', 'env', 'var'])) {
                    return false;
                }
                return $this->stringifyFncallArgs($arg);

            case Type::T_LIST:
                if (!$inFunction) {
                    return false;
                }
                if (empty($arg['enclosing']) and $arg[1] === '') {
                    foreach ($arg[2] as $k => $subarg) {
                        $arg[2][$k] = $this->cssValidArg($subarg, $allowed_function, $inFunction);
                        if ($arg[2][$k] === false) {
                            return false;
                        }
                    }
                    $arg[0] = Type::T_STRING;
                    return $arg;
                }
                return false;

            case Type::T_EXPRESSION:
                if (! \in_array($arg[1], ['+', '-', '/', '*'])) {
                    return false;
                }
                $arg[2] = $this->cssValidArg($arg[2], $allowed_function, $inFunction);
                $arg[3] = $this->cssValidArg($arg[3], $allowed_function, $inFunction);
                if ($arg[2] === false || $arg[3] === false) {
                    return false;
                }
                return $this->expToString($arg, true);

            case Type::T_VARIABLE:
            case Type::T_SELF:
            default:
                return false;
        }
    }


    /**
     * Reformat fncall arguments to proper css function output
     *
     * @param array|Number $arg
     *
     * @return array|Number
     */
    protected function stringifyFncallArgs($arg)
    {
        if ($arg instanceof Number) {
            return $arg;
        }

        switch ($arg[0]) {
            case Type::T_LIST:
                foreach ($arg[2] as $k => $v) {
                    $arg[2][$k] = $this->stringifyFncallArgs($v);
                }
                break;

            case Type::T_EXPRESSION:
                if ($arg[1] === '/') {
                    $arg[2] = $this->stringifyFncallArgs($arg[2]);
                    $arg[3] = $this->stringifyFncallArgs($arg[3]);
                    $arg[5] = $arg[6] = false; // no space around /
                    $arg = $this->expToString($arg);
                }
                break;

            case Type::T_FUNCTION_CALL:
                $name = strtolower($arg[1]);

                if (in_array($name, ['max', 'min', 'calc'])) {
                    $args = $arg[2];
                    $arg = $this->fncall([Type::T_FUNCTION, $name, [Type::T_LIST, ',', []]], $args);
                }
                break;
        }

        return $arg;
    }

    /**
     * Find a function reference
     * @param string $name
     * @param bool $safeCopy
     * @return array
     */
    protected function getFunctionReference($name, $safeCopy = false)
    {
        // SCSS @function
        if ($func = $this->get(static::$namespaces['function'] . $name, false)) {
            if ($safeCopy) {
                $func = clone $func;
            }

            return [Type::T_FUNCTION_REFERENCE, 'scss', $name, $func];
        }

        // native PHP functions

        // try to find a native lib function
        $normalizedName = $this->normalizeName($name);
        $libName = null;

        if (isset($this->userFunctions[$normalizedName])) {
            // see if we can find a user function
            list($f, $prototype) = $this->userFunctions[$normalizedName];

            return [Type::T_FUNCTION_REFERENCE, 'user', $name, $f, $prototype];
        }

        if (($f = $this->getBuiltinFunction($normalizedName)) && \is_callable($f)) {
            $libName   = $f[1];
            $prototype = isset(static::$$libName) ? static::$$libName : null;

            if (\get_class($this) !== __CLASS__ && !isset($this->warnedChildFunctions[$libName])) {
                $r = new \ReflectionMethod($this, $libName);
                $declaringClass = $r->getDeclaringClass()->name;

                $needsWarning = $this->warnedChildFunctions[$libName] = $declaringClass !== __CLASS__;

                if ($needsWarning) {
                    if (method_exists(__CLASS__, $libName)) {
                        @trigger_error(sprintf('Overriding the "%s" core function by extending the Compiler is deprecated and will be unsupported in 2.0. Remove the "%s::%s" method.', $normalizedName, $declaringClass, $libName), E_USER_DEPRECATED);
                    } else {
                        @trigger_error(sprintf('Registering custom functions by extending the Compiler and using the lib* discovery mechanism is deprecated and will be removed in 2.0. Replace the "%s::%s" method with registering the "%s" function through "Compiler::registerFunction".', $declaringClass, $libName, $normalizedName), E_USER_DEPRECATED);
                    }
                }
            }

            return [Type::T_FUNCTION_REFERENCE, 'native', $name, $f, $prototype];
        }

        return static::$null;
    }


    /**
     * Normalize name
     *
     * @param string $name
     *
     * @return string
     */
    protected function normalizeName($name)
    {
        return str_replace('-', '_', $name);
    }

    /**
     * Normalize value
     *
     * @internal
     *
     * @param array|Number $value
     *
     * @return array|Number
     */
    public function normalizeValue($value)
    {
        $value = $this->coerceForExpression($this->reduce($value));

        if ($value instanceof Number) {
            return $value;
        }

        switch ($value[0]) {
            case Type::T_LIST:
                $value = $this->extractInterpolation($value);

                if ($value[0] !== Type::T_LIST) {
                    return [Type::T_KEYWORD, $this->compileValue($value)];
                }

                foreach ($value[2] as $key => $item) {
                    $value[2][$key] = $this->normalizeValue($item);
                }

                if (! empty($value['enclosing'])) {
                    unset($value['enclosing']);
                }

                return $value;

            case Type::T_STRING:
                return [$value[0], '"', [$this->compileStringContent($value)]];

            case Type::T_INTERPOLATE:
                return [Type::T_KEYWORD, $this->compileValue($value)];

            default:
                return $value;
        }
    }

    /**
     * Add numbers
     *
     * @param Number $left
     * @param Number $right
     *
     * @return Number
     */
    protected function opAddNumberNumber(Number $left, Number $right)
    {
        return $left->plus($right);
    }

    /**
     * Multiply numbers
     *
     * @param Number $left
     * @param Number $right
     *
     * @return Number
     */
    protected function opMulNumberNumber(Number $left, Number $right)
    {
        return $left->times($right);
    }

    /**
     * Subtract numbers
     *
     * @param Number $left
     * @param Number $right
     *
     * @return Number
     */
    protected function opSubNumberNumber(Number $left, Number $right)
    {
        return $left->minus($right);
    }

    /**
     * Divide numbers
     *
     * @param Number $left
     * @param Number $right
     *
     * @return Number
     */
    protected function opDivNumberNumber(Number $left, Number $right)
    {
        return $left->dividedBy($right);
    }

    /**
     * Mod numbers
     *
     * @param Number $left
     * @param Number $right
     *
     * @return Number
     */
    protected function opModNumberNumber(Number $left, Number $right)
    {
        return $left->modulo($right);
    }

    /**
     * Add strings
     *
     * @param array $left
     * @param array $right
     *
     * @return array|null
     */
    protected function opAdd($left, $right)
    {
        if ($strLeft = $this->coerceString($left)) {
            if ($right[0] === Type::T_STRING) {
                $right[1] = '';
            }

            $strLeft[2][] = $right;

            return $strLeft;
        }

        if ($strRight = $this->coerceString($right)) {
            if ($left[0] === Type::T_STRING) {
                $left[1] = '';
            }

            array_unshift($strRight[2], $left);

            return $strRight;
        }

        return null;
    }

    /**
     * Boolean and
     *
     * @param array|Number $left
     * @param array|Number  $right
     * @param boolean $shouldEval
     *
     * @return array|Number|null
     */
    protected function opAnd($left, $right, $shouldEval)
    {
        $truthy = ($left === static::$null || $right === static::$null) ||
                  ($left === static::$false || $left === static::$true) &&
                  ($right === static::$false || $right === static::$true);

        if (! $shouldEval) {
            if (! $truthy) {
                return null;
            }
        }

        if ($left !== static::$false && $left !== static::$null) {
            return $this->reduce($right, true);
        }

        return $left;
    }

    /**
     * Boolean or
     *
     * @param array|Number $left
     * @param array|Number $right
     * @param boolean $shouldEval
     *
     * @return array|Number|null
     */
    protected function opOr($left, $right, $shouldEval)
    {
        $truthy = ($left === static::$null || $right === static::$null) ||
                  ($left === static::$false || $left === static::$true) &&
                  ($right === static::$false || $right === static::$true);

        if (! $shouldEval) {
            if (! $truthy) {
                return null;
            }
        }

        if ($left !== static::$false && $left !== static::$null) {
            return $left;
        }

        return $this->reduce($right, true);
    }

    /**
     * Compare colors
     *
     * @param string $op
     * @param array  $left
     * @param array  $right
     *
     * @return array
     */
    protected function opColorColor($op, $left, $right)
    {
        if ($op !== '==' && $op !== '!=') {
            $warning = "Color arithmetic is deprecated and will be an error in future versions.\n"
                . "Consider using Sass's color functions instead.";
            $fname = $this->getPrettyPath($this->sourceNames[$this->sourceIndex]);
            $line  = $this->sourceLine;

            Warn::deprecation("$warning\n         on line $line of $fname");
        }

        $out = [Type::T_COLOR];

        foreach ([1, 2, 3] as $i) {
            $lval = isset($left[$i]) ? $left[$i] : 0;
            $rval = isset($right[$i]) ? $right[$i] : 0;

            switch ($op) {
                case '+':
                    $out[] = $lval + $rval;
                    break;

                case '-':
                    $out[] = $lval - $rval;
                    break;

                case '*':
                    $out[] = $lval * $rval;
                    break;

                case '%':
                    if ($rval == 0) {
                        throw $this->error("color: Can't take modulo by zero");
                    }

                    $out[] = $lval % $rval;
                    break;

                case '/':
                    if ($rval == 0) {
                        throw $this->error("color: Can't divide by zero");
                    }

                    $out[] = (int) ($lval / $rval);
                    break;

                case '==':
                    return $this->opEq($left, $right);

                case '!=':
                    return $this->opNeq($left, $right);

                default:
                    throw $this->error("color: unknown op $op");
            }
        }

        if (isset($left[4])) {
            $out[4] = $left[4];
        } elseif (isset($right[4])) {
            $out[4] = $right[4];
        }

        return $this->fixColor($out);
    }

    /**
     * Compare color and number
     *
     * @param string $op
     * @param array  $left
     * @param Number  $right
     *
     * @return array
     */
    protected function opColorNumber($op, $left, Number $right)
    {
        if ($op === '==') {
            return static::$false;
        }

        if ($op === '!=') {
            return static::$true;
        }

        $value = $right->getDimension();

        return $this->opColorColor(
            $op,
            $left,
            [Type::T_COLOR, $value, $value, $value]
        );
    }

    /**
     * Compare number and color
     *
     * @param string $op
     * @param Number  $left
     * @param array  $right
     *
     * @return array
     */
    protected function opNumberColor($op, Number $left, $right)
    {
        if ($op === '==') {
            return static::$false;
        }

        if ($op === '!=') {
            return static::$true;
        }

        $value = $left->getDimension();

        return $this->opColorColor(
            $op,
            [Type::T_COLOR, $value, $value, $value],
            $right
        );
    }

    /**
     * Compare number1 == number2
     *
     * @param array|Number $left
     * @param array|Number $right
     *
     * @return array
     */
    protected function opEq($left, $right)
    {
        if (($lStr = $this->coerceString($left)) && ($rStr = $this->coerceString($right))) {
            $lStr[1] = '';
            $rStr[1] = '';

            $left = $this->compileValue($lStr);
            $right = $this->compileValue($rStr);
        }

        return $this->toBool($left === $right);
    }

    /**
     * Compare number1 != number2
     *
     * @param array|Number $left
     * @param array|Number $right
     *
     * @return array
     */
    protected function opNeq($left, $right)
    {
        if (($lStr = $this->coerceString($left)) && ($rStr = $this->coerceString($right))) {
            $lStr[1] = '';
            $rStr[1] = '';

            $left = $this->compileValue($lStr);
            $right = $this->compileValue($rStr);
        }

        return $this->toBool($left !== $right);
    }

    /**
     * Compare number1 == number2
     *
     * @param Number $left
     * @param Number $right
     *
     * @return array
     */
    protected function opEqNumberNumber(Number $left, Number $right)
    {
        return $this->toBool($left->equals($right));
    }

    /**
     * Compare number1 != number2
     *
     * @param Number $left
     * @param Number $right
     *
     * @return array
     */
    protected function opNeqNumberNumber(Number $left, Number $right)
    {
        return $this->toBool(!$left->equals($right));
    }

    /**
     * Compare number1 >= number2
     *
     * @param Number $left
     * @param Number $right
     *
     * @return array
     */
    protected function opGteNumberNumber(Number $left, Number $right)
    {
        return $this->toBool($left->greaterThanOrEqual($right));
    }

    /**
     * Compare number1 > number2
     *
     * @param Number $left
     * @param Number $right
     *
     * @return array
     */
    protected function opGtNumberNumber(Number $left, Number $right)
    {
        return $this->toBool($left->greaterThan($right));
    }

    /**
     * Compare number1 <= number2
     *
     * @param Number $left
     * @param Number $right
     *
     * @return array
     */
    protected function opLteNumberNumber(Number $left, Number $right)
    {
        return $this->toBool($left->lessThanOrEqual($right));
    }

    /**
     * Compare number1 < number2
     *
     * @param Number $left
     * @param Number $right
     *
     * @return array
     */
    protected function opLtNumberNumber(Number $left, Number $right)
    {
        return $this->toBool($left->lessThan($right));
    }

    /**
     * Cast to boolean
     *
     * @api
     *
     * @param bool $thing
     *
     * @return array
     */
    public function toBool($thing)
    {
        return $thing ? static::$true : static::$false;
    }

    /**
     * Escape non printable chars in strings output as in dart-sass
     *
     * @internal
     *
     * @param string $string
     * @param bool   $inKeyword
     *
     * @return string
     */
    public function escapeNonPrintableChars($string, $inKeyword = false)
    {
        static $replacement = [];
        if (empty($replacement[$inKeyword])) {
            for ($i = 0; $i < 32; $i++) {
                if ($i !== 9 || $inKeyword) {
                    $replacement[$inKeyword][chr($i)] = '\\' . dechex($i) . ($inKeyword ? ' ' : chr(0));
                }
            }
        }
        $string = str_replace(array_keys($replacement[$inKeyword]), array_values($replacement[$inKeyword]), $string);
        // chr(0) is not a possible char from the input, so any chr(0) comes from our escaping replacement
        if (strpos($string, chr(0)) !== false) {
            if (substr($string, -1) === chr(0)) {
                $string = substr($string, 0, -1);
            }
            $string = str_replace(
                [chr(0) . '\\',chr(0) . ' '],
                [ '\\', ' '],
                $string
            );
            if (strpos($string, chr(0)) !== false) {
                $parts = explode(chr(0), $string);
                $string = array_shift($parts);
                while (count($parts)) {
                    $next = array_shift($parts);
                    if (strpos("0123456789abcdefABCDEF" . chr(9), $next[0]) !== false) {
                        $string .= " ";
                    }
                    $string .= $next;
                }
            }
        }

        return $string;
    }

    /**
     * Compiles a primitive value into a CSS property value.
     *
     * Values in scssphp are typed by being wrapped in arrays, their format is
     * typically:
     *
     *     array(type, contents [, additional_contents]*)
     *
     * The input is expected to be reduced. This function will not work on
     * things like expressions and variables.
     *
     * @api
     *
     * @param array|Number $value
     * @param bool         $quote
     *
     * @return string
     */
    public function compileValue($value, $quote = true)
    {
        $value = $this->reduce($value);

        if ($value instanceof Number) {
            return $value->output($this);
        }

        switch ($value[0]) {
            case Type::T_KEYWORD:
                return $this->escapeNonPrintableChars($value[1], true);

            case Type::T_COLOR:
                // [1] - red component (either number for a %)
                // [2] - green component
                // [3] - blue component
                // [4] - optional alpha component
                list(, $r, $g, $b) = $value;

                $r = $this->compileRGBAValue($r);
                $g = $this->compileRGBAValue($g);
                $b = $this->compileRGBAValue($b);

                if (\count($value) === 5) {
                    $alpha = $this->compileRGBAValue($value[4], true);

                    if (! is_numeric($alpha) || $alpha < 1) {
                        $colorName = Colors::RGBaToColorName($r, $g, $b, $alpha);

                        if (! \is_null($colorName)) {
                            return $colorName;
                        }

                        if (is_numeric($alpha)) {
                            $a = new Number($alpha, '');
                        } else {
                            $a = $alpha;
                        }

                        return 'rgba(' . $r . ', ' . $g . ', ' . $b . ', ' . $a . ')';
                    }
                }

                if (! is_numeric($r) || ! is_numeric($g) || ! is_numeric($b)) {
                    return 'rgb(' . $r . ', ' . $g . ', ' . $b . ')';
                }

                $colorName = Colors::RGBaToColorName($r, $g, $b);

                if (! \is_null($colorName)) {
                    return $colorName;
                }

                $h = sprintf('#%02x%02x%02x', $r, $g, $b);

                // Converting hex color to short notation (e.g. #003399 to #039)
                if ($h[1] === $h[2] && $h[3] === $h[4] && $h[5] === $h[6]) {
                    $h = '#' . $h[1] . $h[3] . $h[5];
                }

                return $h;

            case Type::T_STRING:
                $content = $this->compileStringContent($value, $quote);

                if ($value[1] && $quote) {
                    $content = str_replace('\\', '\\\\', $content);

                    $content = $this->escapeNonPrintableChars($content);

                    // force double quote as string quote for the output in certain cases
                    if (
                        $value[1] === "'" &&
                        (strpos($content, '"') === false or strpos($content, "'") !== false) &&
                        strpbrk($content, '{}\\\'') !== false
                    ) {
                        $value[1] = '"';
                    } elseif (
                        $value[1] === '"' &&
                        (strpos($content, '"') !== false and strpos($content, "'") === false)
                    ) {
                        $value[1] = "'";
                    }

                    $content = str_replace($value[1], '\\' . $value[1], $content);
                }

                return $value[1] . $content . $value[1];

            case Type::T_FUNCTION:
                $args = ! empty($value[2]) ? $this->compileValue($value[2], $quote) : '';

                return "$value[1]($args)";

            case Type::T_FUNCTION_REFERENCE:
                $name = ! empty($value[2]) ? $value[2] : '';

                return "get-function(\"$name\")";

            case Type::T_LIST:
                $value = $this->extractInterpolation($value);

                if ($value[0] !== Type::T_LIST) {
                    return $this->compileValue($value, $quote);
                }

                list(, $delim, $items) = $value;
                $pre = $post = '';

                if (! empty($value['enclosing'])) {
                    switch ($value['enclosing']) {
                        case 'parent':
                            //$pre = '(';
                            //$post = ')';
                            break;
                        case 'forced_parent':
                            $pre = '(';
                            $post = ')';
                            break;
                        case 'bracket':
                        case 'forced_bracket':
                            $pre = '[';
                            $post = ']';
                            break;
                    }
                }

                $prefix_value = '';

                if ($delim !== ' ') {
                    $prefix_value = ' ';
                }

                $filtered = [];

                $same_string_quote = null;
                foreach ($items as $item) {
                    if (\is_null($same_string_quote)) {
                        $same_string_quote = false;
                        if ($item[0] === Type::T_STRING) {
                            $same_string_quote = $item[1];
                            foreach ($items as $ii) {
                                if ($ii[0] !== Type::T_STRING) {
                                    $same_string_quote = false;
                                    break;
                                }
                            }
                        }
                    }
                    if ($item[0] === Type::T_NULL) {
                        continue;
                    }
                    if ($same_string_quote === '"' && $item[0] === Type::T_STRING && $item[1]) {
                        $item[1] = $same_string_quote;
                    }

                    $compiled = $this->compileValue($item, $quote);

                    if ($prefix_value && \strlen($compiled)) {
                        $compiled = $prefix_value . $compiled;
                    }

                    $filtered[] = $compiled;
                }

                return $pre . substr(implode("$delim", $filtered), \strlen($prefix_value)) . $post;

            case Type::T_MAP:
                $keys     = $value[1];
                $values   = $value[2];
                $filtered = [];

                for ($i = 0, $s = \count($keys); $i < $s; $i++) {
                    $filtered[$this->compileValue($keys[$i], $quote)] = $this->compileValue($values[$i], $quote);
                }

                array_walk($filtered, function (&$value, $key) {
                    $value = $key . ': ' . $value;
                });

                return '(' . implode(', ', $filtered) . ')';

            case Type::T_INTERPOLATED:
                // node created by extractInterpolation
                list(, $interpolate, $left, $right) = $value;
                list(,, $whiteLeft, $whiteRight) = $interpolate;

                $delim = $left[1];

                if ($delim && $delim !== ' ' && ! $whiteLeft) {
                    $delim .= ' ';
                }

                $left = \count($left[2]) > 0
                    ?  $this->compileValue($left, $quote) . $delim . $whiteLeft
                    : '';

                $delim = $right[1];

                if ($delim && $delim !== ' ') {
                    $delim .= ' ';
                }

                $right = \count($right[2]) > 0 ?
                    $whiteRight . $delim . $this->compileValue($right, $quote) : '';

                return $left . $this->compileValue($interpolate, $quote) . $right;

            case Type::T_INTERPOLATE:
                // strip quotes if it's a string
                $reduced = $this->reduce($value[1]);

                if ($reduced instanceof Number) {
                    return $this->compileValue($reduced, $quote);
                }

                switch ($reduced[0]) {
                    case Type::T_LIST:
                        $reduced = $this->extractInterpolation($reduced);

                        if ($reduced[0] !== Type::T_LIST) {
                            break;
                        }

                        list(, $delim, $items) = $reduced;

                        if ($delim !== ' ') {
                            $delim .= ' ';
                        }

                        $filtered = [];

                        foreach ($items as $item) {
                            if ($item[0] === Type::T_NULL) {
                                continue;
                            }

                            if ($item[0] === Type::T_STRING) {
                                $filtered[] = $this->compileStringContent($item, $quote);
                            } elseif ($item[0] === Type::T_KEYWORD) {
                                $filtered[] = $item[1];
                            } else {
                                $filtered[] = $this->compileValue($item, $quote);
                            }
                        }

                        $reduced = [Type::T_KEYWORD, implode("$delim", $filtered)];
                        break;

                    case Type::T_STRING:
                        $reduced = [Type::T_STRING, '', [$this->compileStringContent($reduced)]];
                        break;

                    case Type::T_NULL:
                        $reduced = [Type::T_KEYWORD, ''];
                }

                return $this->compileValue($reduced, $quote);

            case Type::T_NULL:
                return 'null';

            case Type::T_COMMENT:
                return $this->compileCommentValue($value);

            default:
                throw $this->error('unknown value type: ' . json_encode($value));
        }
    }

    /**
     * @param array|Number $value
     *
     * @return string
     */
    protected function compileDebugValue($value)
    {
        $value = $this->reduce($value, true);

        if ($value instanceof Number) {
            return $this->compileValue($value);
        }

        switch ($value[0]) {
            case Type::T_STRING:
                return $this->compileStringContent($value);

            default:
                return $this->compileValue($value);
        }
    }

    /**
     * Flatten list
     *
     * @param array $list
     *
     * @return string
     *
     * @deprecated
     */
    protected function flattenList($list)
    {
        @trigger_error(sprintf('The "%s" method is deprecated.', __METHOD__), E_USER_DEPRECATED);

        return $this->compileValue($list);
    }

    /**
     * Gets the text of a Sass string
     *
     * Calling this method on anything else than a SassString is unsupported. Use {@see assertString} first
     * to ensure that the value is indeed a string.
     *
     * @param array $value
     *
     * @return string
     */
    public function getStringText(array $value)
    {
        if ($value[0] !== Type::T_STRING) {
            throw new \InvalidArgumentException('The argument is not a sass string. Did you forgot to use "assertString"?');
        }

        return $this->compileStringContent($value);
    }

    /**
     * Compile string content
     *
     * @param array $string
     * @param bool  $quote
     *
     * @return string
     */
    protected function compileStringContent($string, $quote = true)
    {
        $parts = [];

        foreach ($string[2] as $part) {
            if (\is_array($part) || $part instanceof Number) {
                $parts[] = $this->compileValue($part, $quote);
            } else {
                $parts[] = $part;
            }
        }

        return implode($parts);
    }

    /**
     * Extract interpolation; it doesn't need to be recursive, compileValue will handle that
     *
     * @param array $list
     *
     * @return array
     */
    protected function extractInterpolation($list)
    {
        $items = $list[2];

        foreach ($items as $i => $item) {
            if ($item[0] === Type::T_INTERPOLATE) {
                $before = [Type::T_LIST, $list[1], \array_slice($items, 0, $i)];
                $after  = [Type::T_LIST, $list[1], \array_slice($items, $i + 1)];

                return [Type::T_INTERPOLATED, $item, $before, $after];
            }
        }

        return $list;
    }

    /**
     * Find the final set of selectors
     *
     * @param \ScssPhp\ScssPhp\Compiler\Environment $env
     * @param \ScssPhp\ScssPhp\Block                $selfParent
     *
     * @return array
     */
    protected function multiplySelectors(Environment $env, $selfParent = null)
    {
        $envs            = $this->compactEnv($env);
        $selectors       = [];
        $parentSelectors = [[]];

        $selfParentSelectors = null;

        if (! \is_null($selfParent) && $selfParent->selectors) {
            $selfParentSelectors = $this->evalSelectors($selfParent->selectors);
        }

        while ($env = array_pop($envs)) {
            if (empty($env->selectors)) {
                continue;
            }

            $selectors = $env->selectors;

            do {
                $stillHasSelf  = false;
                $prevSelectors = $selectors;
                $selectors     = [];

                foreach ($parentSelectors as $parent) {
                    foreach ($prevSelectors as $selector) {
                        if ($selfParentSelectors) {
                            foreach ($selfParentSelectors as $selfParent) {
                                // if no '&' in the selector, each call will give same result, only add once
                                $s = $this->joinSelectors($parent, $selector, $stillHasSelf, $selfParent);
                                $selectors[serialize($s)] = $s;
                            }
                        } else {
                            $s = $this->joinSelectors($parent, $selector, $stillHasSelf);
                            $selectors[serialize($s)] = $s;
                        }
                    }
                }
            } while ($stillHasSelf);

            $parentSelectors = $selectors;
        }

        $selectors = array_values($selectors);

        // case we are just starting a at-root : nothing to multiply but parentSelectors
        if (! $selectors && $selfParentSelectors) {
            $selectors = $selfParentSelectors;
        }

        return $selectors;
    }

    /**
     * Join selectors; looks for & to replace, or append parent before child
     *
     * @param array   $parent
     * @param array   $child
     * @param boolean $stillHasSelf
     * @param array   $selfParentSelectors

     * @return array
     */
    protected function joinSelectors($parent, $child, &$stillHasSelf, $selfParentSelectors = null)
    {
        $setSelf = false;
        $out = [];

        foreach ($child as $part) {
            $newPart = [];

            foreach ($part as $p) {
                // only replace & once and should be recalled to be able to make combinations
                if ($p === static::$selfSelector && $setSelf) {
                    $stillHasSelf = true;
                }

                if ($p === static::$selfSelector && ! $setSelf) {
                    $setSelf = true;

                    if (\is_null($selfParentSelectors)) {
                        $selfParentSelectors = $parent;
                    }

                    foreach ($selfParentSelectors as $i => $parentPart) {
                        if ($i > 0) {
                            $out[] = $newPart;
                            $newPart = [];
                        }

                        foreach ($parentPart as $pp) {
                            if (\is_array($pp)) {
                                $flatten = [];

                                array_walk_recursive($pp, function ($a) use (&$flatten) {
                                    $flatten[] = $a;
                                });

                                $pp = implode($flatten);
                            }

                            $newPart[] = $pp;
                        }
                    }
                } else {
                    $newPart[] = $p;
                }
            }

            $out[] = $newPart;
        }

        return $setSelf ? $out : array_merge($parent, $child);
    }

    /**
     * Multiply media
     *
     * @param \ScssPhp\ScssPhp\Compiler\Environment $env
     * @param array                                 $childQueries
     *
     * @return array
     */
    protected function multiplyMedia(Environment $env = null, $childQueries = null)
    {
        if (
            ! isset($env) ||
            ! empty($env->block->type) && $env->block->type !== Type::T_MEDIA
        ) {
            return $childQueries;
        }

        // plain old block, skip
        if (empty($env->block->type)) {
            return $this->multiplyMedia($env->parent, $childQueries);
        }

        $parentQueries = isset($env->block->queryList)
            ? $env->block->queryList
            : [[[Type::T_MEDIA_VALUE, $env->block->value]]];

        $store = [$this->env, $this->storeEnv];

        $this->env      = $env;
        $this->storeEnv = null;
        $parentQueries  = $this->evaluateMediaQuery($parentQueries);

        list($this->env, $this->storeEnv) = $store;

        if (\is_null($childQueries)) {
            $childQueries = $parentQueries;
        } else {
            $originalQueries = $childQueries;
            $childQueries = [];

            foreach ($parentQueries as $parentQuery) {
                foreach ($originalQueries as $childQuery) {
                    $childQueries[] = array_merge(
                        $parentQuery,
                        [[Type::T_MEDIA_TYPE, [Type::T_KEYWORD, 'all']]],
                        $childQuery
                    );
                }
            }
        }

        return $this->multiplyMedia($env->parent, $childQueries);
    }

    /**
     * Convert env linked list to stack
     *
     * @param Environment $env
     *
     * @return Environment[]
     *
     * @phpstan-return non-empty-array<Environment>
     */
    protected function compactEnv(Environment $env)
    {
        for ($envs = []; $env; $env = $env->parent) {
            $envs[] = $env;
        }

        return $envs;
    }

    /**
     * Convert env stack to singly linked list
     *
     * @param Environment[] $envs
     *
     * @return Environment
     *
     * @phpstan-param  non-empty-array<Environment> $envs
     */
    protected function extractEnv($envs)
    {
        for ($env = null; $e = array_pop($envs);) {
            $e->parent = $env;
            $env = $e;
        }

        return $env;
    }

    /**
     * Push environment
     *
     * @param \ScssPhp\ScssPhp\Block $block
     *
     * @return \ScssPhp\ScssPhp\Compiler\Environment
     */
    protected function pushEnv(Block $block = null)
    {
        $env = new Environment();
        $env->parent = $this->env;
        $env->parentStore = $this->storeEnv;
        $env->store  = [];
        $env->block  = $block;
        $env->depth  = isset($this->env->depth) ? $this->env->depth + 1 : 0;

        $this->env = $env;
        $this->storeEnv = null;

        return $env;
    }

    /**
     * Pop environment
     *
     * @return void
     */
    protected function popEnv()
    {
        $this->storeEnv = $this->env->parentStore;
        $this->env = $this->env->parent;
    }

    /**
     * Propagate vars from a just poped Env (used in @each and @for)
     *
     * @param array         $store
     * @param null|string[] $excludedVars
     *
     * @return void
     */
    protected function backPropagateEnv($store, $excludedVars = null)
    {
        foreach ($store as $key => $value) {
            if (empty($excludedVars) || ! \in_array($key, $excludedVars)) {
                $this->set($key, $value, true);
            }
        }
    }

    /**
     * Get store environment
     *
     * @return \ScssPhp\ScssPhp\Compiler\Environment
     */
    protected function getStoreEnv()
    {
        return isset($this->storeEnv) ? $this->storeEnv : $this->env;
    }

    /**
     * Set variable
     *
     * @param string                                $name
     * @param mixed                                 $value
     * @param boolean                               $shadow
     * @param \ScssPhp\ScssPhp\Compiler\Environment $env
     * @param mixed                                 $valueUnreduced
     *
     * @return void
     */
    protected function set($name, $value, $shadow = false, Environment $env = null, $valueUnreduced = null)
    {
        $name = $this->normalizeName($name);

        if (! isset($env)) {
            $env = $this->getStoreEnv();
        }

        if ($shadow) {
            $this->setRaw($name, $value, $env, $valueUnreduced);
        } else {
            $this->setExisting($name, $value, $env, $valueUnreduced);
        }
    }

    /**
     * Set existing variable
     *
     * @param string                                $name
     * @param mixed                                 $value
     * @param \ScssPhp\ScssPhp\Compiler\Environment $env
     * @param mixed                                 $valueUnreduced
     *
     * @return void
     */
    protected function setExisting($name, $value, Environment $env, $valueUnreduced = null)
    {
        $storeEnv = $env;
        $specialContentKey = static::$namespaces['special'] . 'content';

        $hasNamespace = $name[0] === '^' || $name[0] === '@' || $name[0] === '%';

        $maxDepth = 10000;

        for (;;) {
            if ($maxDepth-- <= 0) {
                break;
            }

            if (\array_key_exists($name, $env->store)) {
                break;
            }

            if (! $hasNamespace && isset($env->marker)) {
                if (! empty($env->store[$specialContentKey])) {
                    $env = $env->store[$specialContentKey]->scope;
                    continue;
                }

                if (! empty($env->declarationScopeParent)) {
                    $env = $env->declarationScopeParent;
                    continue;
                } else {
                    $env = $storeEnv;
                    break;
                }
            }

            if (isset($env->parentStore)) {
                $env = $env->parentStore;
            } elseif (isset($env->parent)) {
                $env = $env->parent;
            } else {
                $env = $storeEnv;
                break;
            }
        }

        $env->store[$name] = $value;

        if ($valueUnreduced) {
            $env->storeUnreduced[$name] = $valueUnreduced;
        }
    }

    /**
     * Set raw variable
     *
     * @param string                                $name
     * @param mixed                                 $value
     * @param \ScssPhp\ScssPhp\Compiler\Environment $env
     * @param mixed                                 $valueUnreduced
     *
     * @return void
     */
    protected function setRaw($name, $value, Environment $env, $valueUnreduced = null)
    {
        $env->store[$name] = $value;

        if ($valueUnreduced) {
            $env->storeUnreduced[$name] = $valueUnreduced;
        }
    }

    /**
     * Get variable
     *
     * @internal
     *
     * @param string                                $name
     * @param boolean                               $shouldThrow
     * @param \ScssPhp\ScssPhp\Compiler\Environment $env
     * @param boolean                               $unreduced
     *
     * @return mixed|null
     */
    public function get($name, $shouldThrow = true, Environment $env = null, $unreduced = false)
    {
        $normalizedName = $this->normalizeName($name);
        $specialContentKey = static::$namespaces['special'] . 'content';

        if (! isset($env)) {
            $env = $this->getStoreEnv();
        }

        $hasNamespace = $normalizedName[0] === '^' || $normalizedName[0] === '@' || $normalizedName[0] === '%';

        $maxDepth = 10000;

        for (;;) {
            if ($maxDepth-- <= 0) {
                break;
            }

            if (\array_key_exists($normalizedName, $env->store)) {
                if ($unreduced && isset($env->storeUnreduced[$normalizedName])) {
                    return $env->storeUnreduced[$normalizedName];
                }

                return $env->store[$normalizedName];
            }

            if (! $hasNamespace && isset($env->marker)) {
                if (! empty($env->store[$specialContentKey])) {
                    $env = $env->store[$specialContentKey]->scope;
                    continue;
                }

                if (! empty($env->declarationScopeParent)) {
                    $env = $env->declarationScopeParent;
                } else {
                    $env = $this->rootEnv;
                }
                continue;
            }

            if (isset($env->parentStore)) {
                $env = $env->parentStore;
            } elseif (isset($env->parent)) {
                $env = $env->parent;
            } else {
                break;
            }
        }

        if ($shouldThrow) {
            throw $this->error("Undefined variable \$$name" . ($maxDepth <= 0 ? ' (infinite recursion)' : ''));
        }

        // found nothing
        return null;
    }

    /**
     * Has variable?
     *
     * @param string                                $name
     * @param \ScssPhp\ScssPhp\Compiler\Environment $env
     *
     * @return boolean
     */
    protected function has($name, Environment $env = null)
    {
        return ! \is_null($this->get($name, false, $env));
    }

    /**
     * Inject variables
     *
     * @param array $args
     *
     * @return void
     */
    protected function injectVariables(array $args)
    {
        if (empty($args)) {
            return;
        }

        $parser = $this->parserFactory(__METHOD__);

        foreach ($args as $name => $strValue) {
            if ($name[0] === '$') {
                $name = substr($name, 1);
            }

            if (!\is_string($strValue) || ! $parser->parseValue($strValue, $value)) {
                $value = $this->coerceValue($strValue);
            }

            $this->set($name, $value);
        }
    }

    /**
     * Replaces variables.
     *
     * @param array<string, mixed> $variables
     *
     * @return void
     */
    public function replaceVariables(array $variables)
    {
        $this->registeredVars = [];
        $this->addVariables($variables);
    }

    /**
     * Replaces variables.
     *
     * @param array<string, mixed> $variables
     *
     * @return void
     */
    public function addVariables(array $variables)
    {
        $triggerWarning = false;

        foreach ($variables as $name => $value) {
            if (!$value instanceof Number && !\is_array($value)) {
                $triggerWarning = true;
            }

            $this->registeredVars[$name] = $value;
        }

        if ($triggerWarning) {
            @trigger_error('Passing raw values to as custom variables to the Compiler is deprecated. Use "\ScssPhp\ScssPhp\ValueConverter::parseValue" or "\ScssPhp\ScssPhp\ValueConverter::fromPhp" to convert them instead.', E_USER_DEPRECATED);
        }
    }

    /**
     * Set variables
     *
     * @api
     *
     * @param array $variables
     *
     * @return void
     *
     * @deprecated Use "addVariables" or "replaceVariables" instead.
     */
    public function setVariables(array $variables)
    {
        @trigger_error('The method "setVariables" of the Compiler is deprecated. Use the "addVariables" method for the equivalent behavior or "replaceVariables" if merging with previous variables was not desired.');

        $this->addVariables($variables);
    }

    /**
     * Unset variable
     *
     * @api
     *
     * @param string $name
     *
     * @return void
     */
    public function unsetVariable($name)
    {
        unset($this->registeredVars[$name]);
    }

    /**
     * Returns list of variables
     *
     * @api
     *
     * @return array
     */
    public function getVariables()
    {
        return $this->registeredVars;
    }

    /**
     * Adds to list of parsed files
     *
     * @internal
     *
     * @param string|null $path
     *
     * @return void
     */
    public function addParsedFile($path)
    {
        if (! \is_null($path) && is_file($path)) {
            $this->parsedFiles[realpath($path)] = filemtime($path);
        }
    }

    /**
     * Returns list of parsed files
     *
     * @deprecated
     * @return array<string, int>
     */
    public function getParsedFiles()
    {
        @trigger_error('The method "getParsedFiles" of the Compiler is deprecated. Use the "getIncludedFiles" method on the CompilationResult instance returned by compileString() instead. Be careful that the signature of the method is different.', E_USER_DEPRECATED);
        return $this->parsedFiles;
    }

    /**
     * Add import path
     *
     * @api
     *
     * @param string|callable $path
     *
     * @return void
     */
    public function addImportPath($path)
    {
        if (! \in_array($path, $this->importPaths)) {
            $this->importPaths[] = $path;
        }
    }

    /**
     * Set import paths
     *
     * @api
     *
     * @param string|array<string|callable> $path
     *
     * @return void
     */
    public function setImportPaths($path)
    {
        $paths = (array) $path;
        $actualImportPaths = array_filter($paths, function ($path) {
            return $path !== '';
        });

        $this->legacyCwdImportPath = \count($actualImportPaths) !== \count($paths);

        if ($this->legacyCwdImportPath) {
            @trigger_error('Passing an empty string in the import paths to refer to the current working directory is deprecated. If that\'s the intended behavior, the value of "getcwd()" should be used directly instead. If this was used for resolving relative imports of the input alongside "chdir" with the source directory, the path of the input file should be passed to "compileString()" instead.', E_USER_DEPRECATED);
        }

        $this->importPaths = $actualImportPaths;
    }

    /**
     * Set number precision
     *
     * @api
     *
     * @param integer $numberPrecision
     *
     * @return void
     *
     * @deprecated The number precision is not configurable anymore. The default is enough for all browsers.
     */
    public function setNumberPrecision($numberPrecision)
    {
        @trigger_error('The number precision is not configurable anymore. '
            . 'The default is enough for all browsers.', E_USER_DEPRECATED);
    }

    /**
     * Sets the output style.
     *
     * @api
     *
     * @param string $style One of the OutputStyle constants
     *
     * @return void
     *
     * @phpstan-param OutputStyle::* $style
     */
    public function setOutputStyle($style)
    {
        switch ($style) {
            case OutputStyle::EXPANDED:
                $this->formatter = Expanded::class;
                break;

            case OutputStyle::COMPRESSED:
                $this->formatter = Compressed::class;
                break;

            default:
                throw new \InvalidArgumentException(sprintf('Invalid output style "%s".', $style));
        }
    }

    /**
     * Set formatter
     *
     * @api
     *
     * @param string $formatterName
     *
     * @return void
     *
     * @deprecated Use {@see setOutputStyle} instead.
     */
    public function setFormatter($formatterName)
    {
        if (!\in_array($formatterName, [Expanded::class, Compressed::class], true)) {
            @trigger_error('Formatters other than Expanded and Compressed are deprecated.', E_USER_DEPRECATED);
        }
        @trigger_error('The method "setFormatter" is deprecated. Use "setOutputStyle" instead.', E_USER_DEPRECATED);

        $this->formatter = $formatterName;
    }

    /**
     * Set line number style
     *
     * @api
     *
     * @param string $lineNumberStyle
     *
     * @return void
     *
     * @deprecated The line number output is not supported anymore. Use source maps instead.
     */
    public function setLineNumberStyle($lineNumberStyle)
    {
        @trigger_error('The line number output is not supported anymore. '
                       . 'Use source maps instead.', E_USER_DEPRECATED);
    }

    /**
     * Enable/disable source maps
     *
     * @api
     *
     * @param integer $sourceMap
     *
     * @return void
     *
     * @phpstan-param self::SOURCE_MAP_* $sourceMap
     */
    public function setSourceMap($sourceMap)
    {
        $this->sourceMap = $sourceMap;
    }

    /**
     * Set source map options
     *
     * @api
     *
     * @param array $sourceMapOptions
     *
     * @phpstan-param  array{sourceRoot?: string, sourceMapFilename?: string|null, sourceMapURL?: string|null, sourceMapWriteTo?: string|null, outputSourceFiles?: bool, sourceMapRootpath?: string, sourceMapBasepath?: string} $sourceMapOptions
     *
     * @return void
     */
    public function setSourceMapOptions($sourceMapOptions)
    {
        $this->sourceMapOptions = $sourceMapOptions;
    }

    /**
     * Register function
     *
     * @api
     *
     * @param string        $name
     * @param callable      $callback
     * @param string[]|null $argumentDeclaration
     *
     * @return void
     */
    public function registerFunction($name, $callback, $argumentDeclaration = null)
    {
        if (self::isNativeFunction($name)) {
            @trigger_error(sprintf('The "%s" function is a core sass function. Overriding it with a custom implementation through "%s" is deprecated and won\'t be supported in ScssPhp 2.0 anymore.', $name, __METHOD__), E_USER_DEPRECATED);
        }

        if ($argumentDeclaration === null) {
            @trigger_error('Omitting the argument declaration when registering custom function is deprecated and won\'t be supported in ScssPhp 2.0 anymore.', E_USER_DEPRECATED);
        }

        $this->userFunctions[$this->normalizeName($name)] = [$callback, $argumentDeclaration];
    }

    /**
     * Unregister function
     *
     * @api
     *
     * @param string $name
     *
     * @return void
     */
    public function unregisterFunction($name)
    {
        unset($this->userFunctions[$this->normalizeName($name)]);
    }

    /**
     * Add feature
     *
     * @api
     *
     * @param string $name
     *
     * @return void
     *
     * @deprecated Registering additional features is deprecated.
     */
    public function addFeature($name)
    {
        @trigger_error('Registering additional features is deprecated.', E_USER_DEPRECATED);

        $this->registeredFeatures[$name] = true;
    }

    /**
     * Import file
     *
     * @param string                                 $path
     * @param \ScssPhp\ScssPhp\Formatter\OutputBlock $out
     *
     * @return void
     */
    protected function importFile($path, OutputBlock $out)
    {
        $this->pushCallStack('import ' . $this->getPrettyPath($path));
        // see if tree is cached
        $realPath = realpath($path);

        if (substr($path, -5) === '.sass') {
            $this->sourceIndex = \count($this->sourceNames);
            $this->sourceNames[] = $path;
            $this->sourceLine = 1;
            $this->sourceColumn = 1;

            throw $this->error('The Sass indented syntax is not implemented.');
        }

        if (isset($this->importCache[$realPath])) {
            $this->handleImportLoop($realPath);

            $tree = $this->importCache[$realPath];
        } else {
            $code   = file_get_contents($path);
            $parser = $this->parserFactory($path);
            $tree   = $parser->parse($code);

            $this->importCache[$realPath] = $tree;
        }

        $currentDirectory = $this->currentDirectory;
        $this->currentDirectory = dirname($path);

        $this->compileChildrenNoReturn($tree->children, $out);
        $this->currentDirectory = $currentDirectory;
        $this->popCallStack();
    }

    /**
     * Save the imported files with their resolving path context
     *
     * @param string|null $currentDirectory
     * @param string      $path
     * @param string      $filePath
     *
     * @return void
     */
    private function registerImport($currentDirectory, $path, $filePath)
    {
        $this->resolvedImports[] = ['currentDir' => $currentDirectory, 'path' => $path, 'filePath' => $filePath];
    }

    /**
     * Detects whether the import is a CSS import.
     *
     * For legacy reasons, custom importers are called for those, allowing them
     * to replace them with an actual Sass import. However this behavior is
     * deprecated. Custom importers are expected to return null when they receive
     * a CSS import.
     *
     * @param string $url
     *
     * @return bool
     */
    public static function isCssImport($url)
    {
        return 1 === preg_match('~\.css$|^https?://|^//~', $url);
    }

    /**
     * Return the file path for an import url if it exists
     *
     * @internal
     *
     * @param string      $url
     * @param string|null $currentDir
     *
     * @return string|null
     */
    public function findImport($url, $currentDir = null)
    {
        // Vanilla css and external requests. These are not meant to be Sass imports.
        // Callback importers are still called for BC.
        if (self::isCssImport($url)) {
            foreach ($this->importPaths as $dir) {
                if (\is_string($dir)) {
                    continue;
                }

                if (\is_callable($dir)) {
                    // check custom callback for import path
                    $file = \call_user_func($dir, $url);

                    if (! \is_null($file)) {
                        if (\is_array($dir)) {
                            $callableDescription = (\is_object($dir[0]) ? \get_class($dir[0]) : $dir[0]).'::'.$dir[1];
                        } elseif ($dir instanceof \Closure) {
                            $r = new \ReflectionFunction($dir);
                            if (false !== strpos($r->name, '{closure}')) {
                                $callableDescription = sprintf('closure{%s:%s}', $r->getFileName(), $r->getStartLine());
                            } elseif ($class = $r->getClosureScopeClass()) {
                                $callableDescription = $class->name.'::'.$r->name;
                            } else {
                                $callableDescription = $r->name;
                            }
                        } elseif (\is_object($dir)) {
                            $callableDescription = \get_class($dir) . '::__invoke';
                        } else {
                            $callableDescription = 'callable'; // Fallback if we don't have a dedicated description
                        }
                        @trigger_error(sprintf('Returning a file to import for CSS or external references in custom importer callables is deprecated and will not be supported anymore in ScssPhp 2.0. This behavior is not compliant with the Sass specification. Update your "%s" importer.', $callableDescription), E_USER_DEPRECATED);

                        return $file;
                    }
                }
            }
            return null;
        }

        if (!\is_null($currentDir)) {
            $relativePath = $this->resolveImportPath($url, $currentDir);

            if (!\is_null($relativePath)) {
                return $relativePath;
            }
        }

        foreach ($this->importPaths as $dir) {
            if (\is_string($dir)) {
                $path = $this->resolveImportPath($url, $dir);

                if (!\is_null($path)) {
                    return $path;
                }
            } elseif (\is_callable($dir)) {
                // check custom callback for import path
                $file = \call_user_func($dir, $url);

                if (! \is_null($file)) {
                    return $file;
                }
            }
        }

        if ($this->legacyCwdImportPath) {
            $path = $this->resolveImportPath($url, getcwd());

            if (!\is_null($path)) {
                @trigger_error('Resolving imports relatively to the current working directory is deprecated. If that\'s the intended behavior, the value of "getcwd()" should be added as an import path explicitly instead. If this was used for resolving relative imports of the input alongside "chdir" with the source directory, the path of the input file should be passed to "compileString()" instead.', E_USER_DEPRECATED);

                return $path;
            }
        }

        throw $this->error("`$url` file not found for @import");
    }

    /**
     * @param string $url
     * @param string $baseDir
     *
     * @return string|null
     */
    private function resolveImportPath($url, $baseDir)
    {
        $path = Path::join($baseDir, $url);

        $hasExtension = preg_match('/.s[ac]ss$/', $url);

        if ($hasExtension) {
            return $this->checkImportPathConflicts($this->tryImportPath($path));
        }

        $result = $this->checkImportPathConflicts($this->tryImportPathWithExtensions($path));

        if (!\is_null($result)) {
            return $result;
        }

        return $this->tryImportPathAsDirectory($path);
    }

    /**
     * @param string[] $paths
     *
     * @return string|null
     */
    private function checkImportPathConflicts(array $paths)
    {
        if (\count($paths) === 0) {
            return null;
        }

        if (\count($paths) === 1) {
            return $paths[0];
        }

        $formattedPrettyPaths = [];

        foreach ($paths as $path) {
            $formattedPrettyPaths[] = '  ' . $this->getPrettyPath($path);
        }

        throw $this->error("It's not clear which file to import. Found:\n" . implode("\n", $formattedPrettyPaths));
    }

    /**
     * @param string $path
     *
     * @return string[]
     */
    private function tryImportPathWithExtensions($path)
    {
        $result = array_merge(
            $this->tryImportPath($path.'.sass'),
            $this->tryImportPath($path.'.scss')
        );

        if ($result) {
            return $result;
        }

        return $this->tryImportPath($path.'.css');
    }

    /**
     * @param string $path
     *
     * @return string[]
     */
    private function tryImportPath($path)
    {
        $partial = dirname($path).'/_'.basename($path);

        $candidates = [];

        if (is_file($partial)) {
            $candidates[] = $partial;
        }

        if (is_file($path)) {
            $candidates[] = $path;
        }

        return $candidates;
    }

    /**
     * @param string $path
     *
     * @return string|null
     */
    private function tryImportPathAsDirectory($path)
    {
        if (!is_dir($path)) {
            return null;
        }

        return $this->checkImportPathConflicts($this->tryImportPathWithExtensions($path.'/index'));
    }

    /**
     * @param string|null $path
     *
     * @return string
     */
    private function getPrettyPath($path)
    {
        if ($path === null) {
            return '(unknown file)';
        }

        $normalizedPath = $path;
        $normalizedRootDirectory = $this->rootDirectory.'/';

        if (\DIRECTORY_SEPARATOR === '\\') {
            $normalizedRootDirectory = str_replace('\\', '/', $normalizedRootDirectory);
            $normalizedPath = str_replace('\\', '/', $path);
        }

        if (0 === strpos($normalizedPath, $normalizedRootDirectory)) {
            return substr($normalizedPath, \strlen($normalizedRootDirectory));
        }

        return $path;
    }

    /**
     * Set encoding
     *
     * @api
     *
     * @param string|null $encoding
     *
     * @return void
     *
     * @deprecated Non-compliant support for other encodings than UTF-8 is deprecated.
     */
    public function setEncoding($encoding)
    {
        if (!$encoding || strtolower($encoding) === 'utf-8') {
            @trigger_error(sprintf('The "%s" method is deprecated.', __METHOD__), E_USER_DEPRECATED);
        } else {
            @trigger_error(sprintf('The "%s" method is deprecated. Parsing will only support UTF-8 in ScssPhp 2.0. The non-UTF-8 parsing of ScssPhp 1.x is not spec compliant.', __METHOD__), E_USER_DEPRECATED);
        }

        $this->encoding = $encoding;
    }

    /**
     * Ignore errors?
     *
     * @api
     *
     * @param boolean $ignoreErrors
     *
     * @return \ScssPhp\ScssPhp\Compiler
     *
     * @deprecated Ignoring Sass errors is not longer supported.
     */
    public function setIgnoreErrors($ignoreErrors)
    {
        @trigger_error('Ignoring Sass errors is not longer supported.', E_USER_DEPRECATED);

        return $this;
    }

    /**
     * Get source position
     *
     * @api
     *
     * @return array
     *
     * @deprecated
     */
    public function getSourcePosition()
    {
        @trigger_error(sprintf('The "%s" method is deprecated.', __METHOD__), E_USER_DEPRECATED);

        $sourceFile = isset($this->sourceNames[$this->sourceIndex]) ? $this->sourceNames[$this->sourceIndex] : '';

        return [$sourceFile, $this->sourceLine, $this->sourceColumn];
    }

    /**
     * Throw error (exception)
     *
     * @api
     *
     * @param string $msg Message with optional sprintf()-style vararg parameters
     *
     * @throws \ScssPhp\ScssPhp\Exception\CompilerException
     *
     * @deprecated use "error" and throw the exception in the caller instead.
     */
    public function throwError($msg)
    {
        @trigger_error(
            'The method "throwError" is deprecated. Use "error" and throw the exception in the caller instead',
            E_USER_DEPRECATED
        );

        throw $this->error(...func_get_args());
    }

    /**
     * Build an error (exception)
     *
     * @internal
     *
     * @param string $msg Message with optional sprintf()-style vararg parameters
     *
     * @return CompilerException
     */
    public function error($msg, ...$args)
    {
        if ($args) {
            $msg = sprintf($msg, ...$args);
        }

        if (! $this->ignoreCallStackMessage) {
            $msg = $this->addLocationToMessage($msg);
        }

        return new CompilerException($msg);
    }

    /**
     * @param string $msg
     *
     * @return string
     */
    private function addLocationToMessage($msg)
    {
        $line   = $this->sourceLine;
        $column = $this->sourceColumn;

        $loc = isset($this->sourceNames[$this->sourceIndex])
            ? $this->getPrettyPath($this->sourceNames[$this->sourceIndex]) . " on line $line, at column $column"
            : "line: $line, column: $column";

        $msg = "$msg: $loc";

        $callStackMsg = $this->callStackMessage();

        if ($callStackMsg) {
            $msg .= "\nCall Stack:\n" . $callStackMsg;
        }

        return $msg;
    }

    /**
     * @param string $functionName
     * @param array $ExpectedArgs
     * @param int $nbActual
     * @return CompilerException
     *
     * @deprecated
     */
    public function errorArgsNumber($functionName, $ExpectedArgs, $nbActual)
    {
        @trigger_error(sprintf('The "%s" method is deprecated.', __METHOD__), E_USER_DEPRECATED);

        $nbExpected = \count($ExpectedArgs);

        if ($nbActual > $nbExpected) {
            return $this->error(
                'Error: Only %d arguments allowed in %s(), but %d were passed.',
                $nbExpected,
                $functionName,
                $nbActual
            );
        } else {
            $missing = [];

            while (count($ExpectedArgs) && count($ExpectedArgs) > $nbActual) {
                array_unshift($missing, array_pop($ExpectedArgs));
            }

            return $this->error(
                'Error: %s() argument%s %s missing.',
                $functionName,
                count($missing) > 1 ? 's' : '',
                implode(', ', $missing)
            );
        }
    }

    /**
     * Beautify call stack for output
     *
     * @param boolean  $all
     * @param int|null $limit
     *
     * @return string
     */
    protected function callStackMessage($all = false, $limit = null)
    {
        $callStackMsg = [];
        $ncall = 0;

        if ($this->callStack) {
            foreach (array_reverse($this->callStack) as $call) {
                if ($all || (isset($call['n']) && $call['n'])) {
                    $msg = '#' . $ncall++ . ' ' . $call['n'] . ' ';
                    $msg .= (isset($this->sourceNames[$call[Parser::SOURCE_INDEX]])
                          ? $this->getPrettyPath($this->sourceNames[$call[Parser::SOURCE_INDEX]])
                          : '(unknown file)');
                    $msg .= ' on line ' . $call[Parser::SOURCE_LINE];

                    $callStackMsg[] = $msg;

                    if (! \is_null($limit) && $ncall > $limit) {
                        break;
                    }
                }
            }
        }

        return implode("\n", $callStackMsg);
    }

    /**
     * Handle import loop
     *
     * @param string $name
     *
     * @throws \Exception
     */
    protected function handleImportLoop($name)
    {
        for ($env = $this->env; $env; $env = $env->parent) {
            if (! $env->block) {
                continue;
            }

            $file = $this->sourceNames[$env->block->sourceIndex];

            if ($file === null) {
                continue;
            }

            if (realpath($file) === $name) {
                throw $this->error('An @import loop has been found: %s imports %s', $file, basename($file));
            }
        }
    }

    /**
     * Call SCSS @function
     *
     * @param Object $func
     * @param array  $argValues
     *
     * @return array|Number
     */
    protected function callScssFunction($func, $argValues)
    {
        if (! $func) {
            return static::$defaultValue;
        }
        $name = $func->name;

        $this->pushEnv();

        // set the args
        if (isset($func->args)) {
            $this->applyArguments($func->args, $argValues);
        }

        // throw away lines and children
        $tmp = new OutputBlock();
        $tmp->lines    = [];
        $tmp->children = [];

        $this->env->marker = 'function';

        if (! empty($func->parentEnv)) {
            $this->env->declarationScopeParent = $func->parentEnv;
        } else {
            throw $this->error("@function $name() without parentEnv");
        }

        $ret = $this->compileChildren($func->children, $tmp, $this->env->marker . ' ' . $name);

        $this->popEnv();

        return ! isset($ret) ? static::$defaultValue : $ret;
    }

    /**
     * Call built-in and registered (PHP) functions
     *
     * @param string $name
     * @param callable $function
     * @param array  $prototype
     * @param array  $args
     *
     * @return array|Number|null
     */
    protected function callNativeFunction($name, $function, $prototype, $args)
    {
        $libName = (is_array($function) ? end($function) : null);
        $sorted_kwargs = $this->sortNativeFunctionArgs($libName, $prototype, $args);

        if (\is_null($sorted_kwargs)) {
            return null;
        }
        @list($sorted, $kwargs) = $sorted_kwargs;

        if ($name !== 'if') {
            foreach ($sorted as &$val) {
                if ($val !== null) {
                    $val = $this->reduce($val, true);
                }
            }
        }

        $returnValue = \call_user_func($function, $sorted, $kwargs);

        if (! isset($returnValue)) {
            return null;
        }

        if (\is_array($returnValue) || $returnValue instanceof Number) {
            return $returnValue;
        }

        @trigger_error(sprintf('Returning a PHP value from the "%s" custom function is deprecated. A sass value must be returned instead.', $name), E_USER_DEPRECATED);

        return $this->coerceValue($returnValue);
    }

    /**
     * Get built-in function
     *
     * @param string $name Normalized name
     *
     * @return array
     */
    protected function getBuiltinFunction($name)
    {
        $libName = self::normalizeNativeFunctionName($name);
        return [$this, $libName];
    }

    /**
     * Normalize native function name
     *
     * @internal
     *
     * @param string $name
     *
     * @return string
     */
    public static function normalizeNativeFunctionName($name)
    {
        $name = str_replace("-", "_", $name);
        $libName = 'lib' . preg_replace_callback(
            '/_(.)/',
            function ($m) {
                return ucfirst($m[1]);
            },
            ucfirst($name)
        );
        return $libName;
    }

    /**
     * Check if a function is a native built-in scss function, for css parsing
     *
     * @internal
     *
     * @param string $name
     *
     * @return bool
     */
    public static function isNativeFunction($name)
    {
        return method_exists(Compiler::class, self::normalizeNativeFunctionName($name));
    }

    /**
     * Sorts keyword arguments
     *
     * @param string $functionName
     * @param array|null  $prototypes
     * @param array  $args
     *
     * @return array|null
     */
    protected function sortNativeFunctionArgs($functionName, $prototypes, $args)
    {
        static $parser = null;

        if (! isset($prototypes)) {
            $keyArgs = [];
            $posArgs = [];

            if (\is_array($args) && \count($args) && \end($args) === static::$null) {
                array_pop($args);
            }

            // separate positional and keyword arguments
            foreach ($args as $arg) {
                list($key, $value) = $arg;

                if (empty($key) or empty($key[1])) {
                    $posArgs[] = empty($arg[2]) ? $value : $arg;
                } else {
                    $keyArgs[$key[1]] = $value;
                }
            }

            return [$posArgs, $keyArgs];
        }

        // specific cases ?
        if (\in_array($functionName, ['libRgb', 'libRgba', 'libHsl', 'libHsla'])) {
            // notation 100 127 255 / 0 is in fact a simple list of 4 values
            foreach ($args as $k => $arg) {
                if ($arg[1][0] === Type::T_LIST && \count($arg[1][2]) === 3) {
                    $args[$k][1][2] = $this->extractSlashAlphaInColorFunction($arg[1][2]);
                }
            }
        }

        list($positionalArgs, $namedArgs, $names, $separator, $hasSplat) = $this->evaluateArguments($args, false);

        if (! \is_array(reset($prototypes))) {
            $prototypes = [$prototypes];
        }

        $parsedPrototypes = array_map([$this, 'parseFunctionPrototype'], $prototypes);
        assert(!empty($parsedPrototypes));
        $matchedPrototype = $this->selectFunctionPrototype($parsedPrototypes, \count($positionalArgs), $names);

        $this->verifyPrototype($matchedPrototype, \count($positionalArgs), $names, $hasSplat);

        $vars = $this->applyArgumentsToDeclaration($matchedPrototype, $positionalArgs, $namedArgs, $separator);

        $finalArgs = [];
        $keyArgs = [];

        foreach ($matchedPrototype['arguments'] as $argument) {
            list($normalizedName, $originalName, $default) = $argument;

            if (isset($vars[$normalizedName])) {
                $value = $vars[$normalizedName];
            } else {
                $value = $default;
            }

            // special null value as default: translate to real null here
            if ($value === [Type::T_KEYWORD, 'null']) {
                $value = null;
            }

            $finalArgs[] = $value;
            $keyArgs[$originalName] = $value;
        }

        if ($matchedPrototype['rest_argument'] !== null) {
            $value = $vars[$matchedPrototype['rest_argument']];

            $finalArgs[] = $value;
            $keyArgs[$matchedPrototype['rest_argument']] = $value;
        }

        return [$finalArgs, $keyArgs];
    }

    /**
     * Parses a function prototype to the internal representation of arguments.
     *
     * The input is an array of strings describing each argument, as supported
     * in {@see registerFunction}. Argument names don't include the `$`.
     * The output contains the list of positional argument, with their normalized
     * name (underscores are replaced by dashes), their original name (to be used
     * in case of error reporting) and their default value. The output also contains
     * the normalized name of the rest argument, or null if the function prototype
     * is not variadic.
     *
     * @param string[] $prototype
     *
     * @return array
     * @phpstan-return array{arguments: list<array{0: string, 1: string, 2: array|Number|null}>, rest_argument: string|null}
     */
    private function parseFunctionPrototype(array $prototype)
    {
        static $parser = null;

        $arguments = [];
        $restArgument = null;

        foreach ($prototype as $p) {
            if (null !== $restArgument) {
                throw new \InvalidArgumentException('The argument declaration is invalid. The rest argument must be the last one.');
            }

            $default = null;
            $p = explode(':', $p, 2);
            $name = str_replace('_', '-', $p[0]);

            if (isset($p[1])) {
                $defaultSource = trim($p[1]);

                if ($defaultSource === 'null') {
                    // differentiate this null from the static::$null
                    $default = [Type::T_KEYWORD, 'null'];
                } else {
                    if (\is_null($parser)) {
                        $parser = $this->parserFactory(__METHOD__);
                    }

                    $parser->parseValue($defaultSource, $default);
                }
            }

            if (substr($name, -3) === '...') {
                $restArgument = substr($name, 0, -3);
            } else {
                $arguments[] = [$name, $p[0], $default];
            }
        }

        return [
            'arguments' => $arguments,
            'rest_argument' => $restArgument,
        ];
    }

    /**
     * Returns the function prototype for the given positional and named arguments.
     *
     * If no exact match is found, finds the closest approximation. Note that this
     * doesn't guarantee that $positional and $names are valid for the returned
     * prototype.
     *
     * @param array[]               $prototypes
     * @param int                   $positional
     * @param array<string, string> $names A set of names, as both keys and values
     *
     * @return array
     *
     * @phpstan-param non-empty-list<array{arguments: list<array{0: string, 1: string, 2: array|Number|null}>, rest_argument: string|null}> $prototypes
     * @phpstan-return array{arguments: list<array{0: string, 1: string, 2: array|Number|null}>, rest_argument: string|null}
     */
    private function selectFunctionPrototype(array $prototypes, $positional, array $names)
    {
        $fuzzyMatch = null;
        $minMismatchDistance = null;

        foreach ($prototypes as $prototype) {
            // Ideally, find an exact match.
            if ($this->checkPrototypeMatches($prototype, $positional, $names)) {
                return $prototype;
            }

            $mismatchDistance = \count($prototype['arguments']) - $positional;

            if ($minMismatchDistance !== null) {
                if (abs($mismatchDistance) > abs($minMismatchDistance)) {
                    continue;
                }

                // If two overloads have the same mismatch distance, favor the overload
                // that has more arguments.
                if (abs($mismatchDistance) === abs($minMismatchDistance) && $mismatchDistance < 0) {
                    continue;
                }
            }

            $minMismatchDistance = $mismatchDistance;
            $fuzzyMatch = $prototype;
        }

        return $fuzzyMatch;
    }

    /**
     * Checks whether the argument invocation matches the callable prototype.
     *
     * The rules are similar to {@see verifyPrototype}. The boolean return value
     * avoids the overhead of building and catching exceptions when the reason of
     * not matching the prototype does not need to be known.
     *
     * @param array                 $prototype
     * @param int                   $positional
     * @param array<string, string> $names
     *
     * @return bool
     *
     * @phpstan-param array{arguments: list<array{0: string, 1: string, 2: array|Number|null}>, rest_argument: string|null} $prototype
     */
    private function checkPrototypeMatches(array $prototype, $positional, array $names)
    {
        $nameUsed = 0;

        foreach ($prototype['arguments'] as $i => $argument) {
            list ($name, $originalName, $default) = $argument;

            if ($i < $positional) {
                if (isset($names[$name])) {
                    return false;
                }
            } elseif (isset($names[$name])) {
                $nameUsed++;
            } elseif ($default === null) {
                return false;
            }
        }

        if ($prototype['rest_argument'] !== null) {
            return true;
        }

        if ($positional > \count($prototype['arguments'])) {
            return false;
        }

        if ($nameUsed < \count($names)) {
            return false;
        }

        return true;
    }

    /**
     * Verifies that the argument invocation is valid for the callable prototype.
     *
     * @param array                 $prototype
     * @param int                   $positional
     * @param array<string, string> $names
     * @param bool                  $hasSplat
     *
     * @return void
     *
     * @throws SassScriptException
     *
     * @phpstan-param array{arguments: list<array{0: string, 1: string, 2: array|Number|null}>, rest_argument: string|null} $prototype
     */
    private function verifyPrototype(array $prototype, $positional, array $names, $hasSplat)
    {
        $nameUsed = 0;

        foreach ($prototype['arguments'] as $i => $argument) {
            list ($name, $originalName, $default) = $argument;

            if ($i < $positional) {
                if (isset($names[$name])) {
                    throw new SassScriptException(sprintf('Argument $%s was passed both by position and by name.', $originalName));
                }
            } elseif (isset($names[$name])) {
                $nameUsed++;
            } elseif ($default === null) {
                throw new SassScriptException(sprintf('Missing argument $%s', $originalName));
            }
        }

        if ($prototype['rest_argument'] !== null) {
            return;
        }

        if ($positional > \count($prototype['arguments'])) {
            $message = sprintf(
                'Only %d %sargument%s allowed, but %d %s passed.',
                \count($prototype['arguments']),
                empty($names) ? '' : 'positional ',
                \count($prototype['arguments']) === 1 ? '' : 's',
                $positional,
                $positional === 1 ? 'was' : 'were'
            );
            if (!$hasSplat) {
                throw new SassScriptException($message);
            }

            $message = $this->addLocationToMessage($message);
            $message .= "\nThis will be an error in future versions of Sass.";
            $this->logger->warn($message, true);
        }

        if ($nameUsed < \count($names)) {
            $unknownNames = array_values(array_diff($names, array_column($prototype['arguments'], 0)));
            $lastName = array_pop($unknownNames);
            $message = sprintf(
                'No argument%s named $%s%s.',
                $unknownNames ? 's' : '',
                $unknownNames ? implode(', $', $unknownNames) . ' or $' : '',
                $lastName
            );
            throw new SassScriptException($message);
        }
    }

    /**
     * Evaluates the argument from the invocation.
     *
     * This returns several things about this invocation:
     * - the list of positional arguments
     * - the map of named arguments, indexed by normalized names
     * - the set of names used in the arguments (that's an array using the normalized names as keys for O(1) access)
     * - the separator used by the list using the splat operator, if any
     * - a boolean indicator whether any splat argument (list or map) was used, to support the incomplete error reporting.
     *
     * @param array[] $args
     * @param bool    $reduce Whether arguments should be reduced to their value
     *
     * @return array
     *
     * @throws SassScriptException
     *
     * @phpstan-return array{0: list<array|Number>, 1: array<string, array|Number>, 2: array<string, string>, 3: string|null, 4: bool}
     */
    private function evaluateArguments(array $args, $reduce = true)
    {
        // this represents trailing commas
        if (\count($args) && end($args) === static::$null) {
            array_pop($args);
        }

        $splatSeparator = null;
        $keywordArgs = [];
        $names = [];
        $positionalArgs = [];
        $hasKeywordArgument = false;
        $hasSplat = false;

        foreach ($args as $arg) {
            if (!empty($arg[0])) {
                $hasKeywordArgument = true;

                assert(\is_string($arg[0][1]));
                $name = str_replace('_', '-', $arg[0][1]);

                if (isset($keywordArgs[$name])) {
                    throw new SassScriptException(sprintf('Duplicate named argument $%s.', $arg[0][1]));
                }

                $keywordArgs[$name] = $this->maybeReduce($reduce, $arg[1]);
                $names[$name] = $name;
            } elseif (! empty($arg[2])) {
                // $arg[2] means a var followed by ... in the arg ($list... )
                $val = $this->reduce($arg[1], true);
                $hasSplat = true;

                if ($val[0] === Type::T_LIST) {
                    foreach ($val[2] as $item) {
                        if (\is_null($splatSeparator)) {
                            $splatSeparator = $val[1];
                        }

                        $positionalArgs[] = $this->maybeReduce($reduce, $item);
                    }

                    if (isset($val[3]) && \is_array($val[3])) {
                        foreach ($val[3] as $name => $item) {
                            assert(\is_string($name));

                            $normalizedName = str_replace('_', '-', $name);

                            if (isset($keywordArgs[$normalizedName])) {
                                throw new SassScriptException(sprintf('Duplicate named argument $%s.', $name));
                            }

                            $keywordArgs[$normalizedName] = $this->maybeReduce($reduce, $item);
                            $names[$normalizedName] = $normalizedName;
                            $hasKeywordArgument = true;
                        }
                    }
                } elseif ($val[0] === Type::T_MAP) {
                    foreach ($val[1] as $i => $name) {
                        $name = $this->compileStringContent($this->coerceString($name));
                        $item = $val[2][$i];

                        if (! is_numeric($name)) {
                            $normalizedName = str_replace('_', '-', $name);

                            if (isset($keywordArgs[$normalizedName])) {
                                throw new SassScriptException(sprintf('Duplicate named argument $%s.', $name));
                            }

                            $keywordArgs[$normalizedName] = $this->maybeReduce($reduce, $item);
                            $names[$normalizedName] = $normalizedName;
                            $hasKeywordArgument = true;
                        } else {
                            if (\is_null($splatSeparator)) {
                                $splatSeparator = $val[1];
                            }

                            $positionalArgs[] = $this->maybeReduce($reduce, $item);
                        }
                    }
                } elseif ($val[0] !== Type::T_NULL) { // values other than null are treated a single-element lists, while null is the empty list
                    $positionalArgs[] = $this->maybeReduce($reduce, $val);
                }
            } elseif ($hasKeywordArgument) {
                throw new SassScriptException('Positional arguments must come before keyword arguments.');
            } else {
                $positionalArgs[] = $this->maybeReduce($reduce, $arg[1]);
            }
        }

        return [$positionalArgs, $keywordArgs, $names, $splatSeparator, $hasSplat];
    }

    /**
     * @param bool         $reduce
     * @param array|Number $value
     *
     * @return array|Number
     */
    private function maybeReduce($reduce, $value)
    {
        if ($reduce) {
            return $this->reduce($value, true);
        }

        return $value;
    }

    /**
     * Apply argument values per definition
     *
     * @param array[]    $argDef
     * @param array|null $argValues
     * @param boolean $storeInEnv
     * @param boolean $reduce
     *   only used if $storeInEnv = false
     *
     * @return array<string, array|Number>
     *
     * @phpstan-param list<array{0: string, 1: array|Number|null, 2: bool}> $argDef
     *
     * @throws \Exception
     */
    protected function applyArguments($argDef, $argValues, $storeInEnv = true, $reduce = true)
    {
        $output = [];

        if (\is_null($argValues)) {
            $argValues = [];
        }

        if ($storeInEnv) {
            $storeEnv = $this->getStoreEnv();

            $env = new Environment();
            $env->store = $storeEnv->store;
        }

        $prototype = ['arguments' => [], 'rest_argument' => null];
        $originalRestArgumentName = null;

        foreach ($argDef as $i => $arg) {
            list($name, $default, $isVariable) = $arg;
            $normalizedName = str_replace('_', '-', $name);

            if ($isVariable) {
                $originalRestArgumentName = $name;
                $prototype['rest_argument'] = $normalizedName;
            } else {
                $prototype['arguments'][] = [$normalizedName, $name, !empty($default) ? $default : null];
            }
        }

        list($positionalArgs, $namedArgs, $names, $splatSeparator, $hasSplat) = $this->evaluateArguments($argValues, $reduce);

        $this->verifyPrototype($prototype, \count($positionalArgs), $names, $hasSplat);

        $vars = $this->applyArgumentsToDeclaration($prototype, $positionalArgs, $namedArgs, $splatSeparator);

        foreach ($prototype['arguments'] as $argument) {
            list($normalizedName, $name) = $argument;

            if (!isset($vars[$normalizedName])) {
                continue;
            }

            $val = $vars[$normalizedName];

            if ($storeInEnv) {
                $this->set($name, $this->reduce($val, true), true, $env);
            } else {
                $output[$name] = ($reduce ? $this->reduce($val, true) : $val);
            }
        }

        if ($prototype['rest_argument'] !== null) {
            assert($originalRestArgumentName !== null);
            $name = $originalRestArgumentName;
            $val = $vars[$prototype['rest_argument']];

            if ($storeInEnv) {
                $this->set($name, $this->reduce($val, true), true, $env);
            } else {
                $output[$name] = ($reduce ? $this->reduce($val, true) : $val);
            }
        }

        if ($storeInEnv) {
            $storeEnv->store = $env->store;
        }

        foreach ($prototype['arguments'] as $argument) {
            list($normalizedName, $name, $default) = $argument;

            if (isset($vars[$normalizedName])) {
                continue;
            }
            assert($default !== null);

            if ($storeInEnv) {
                $this->set($name, $this->reduce($default, true), true);
            } else {
                $output[$name] = ($reduce ? $this->reduce($default, true) : $default);
            }
        }

        return $output;
    }

    /**
     * Apply argument values per definition.
     *
     * This method assumes that the arguments are valid for the provided prototype.
     * The validation with {@see verifyPrototype} must have been run before calling
     * it.
     * Arguments are returned as a map from the normalized argument names to the
     * value. Additional arguments are collected in a sass argument list available
     * under the name of the rest argument in the result.
     *
     * Defaults are not applied as they are resolved in a different environment.
     *
     * @param array                       $prototype
     * @param array<array|Number>         $positionalArgs
     * @param array<string, array|Number> $namedArgs
     * @param string|null                 $splatSeparator
     *
     * @return array<string, array|Number>
     *
     * @phpstan-param array{arguments: list<array{0: string, 1: string, 2: array|Number|null}>, rest_argument: string|null} $prototype
     */
    private function applyArgumentsToDeclaration(array $prototype, array $positionalArgs, array $namedArgs, $splatSeparator)
    {
        $output = [];
        $minLength = min(\count($positionalArgs), \count($prototype['arguments']));

        for ($i = 0; $i < $minLength; $i++) {
            list($name) = $prototype['arguments'][$i];
            $val = $positionalArgs[$i];

            $output[$name] = $val;
        }

        $restNamed = $namedArgs;

        for ($i = \count($positionalArgs); $i < \count($prototype['arguments']); $i++) {
            $argument = $prototype['arguments'][$i];
            list($name) = $argument;

            if (isset($namedArgs[$name])) {
                $val = $namedArgs[$name];
                unset($restNamed[$name]);
            } else {
                continue;
            }

            $output[$name] = $val;
        }

        if ($prototype['rest_argument'] !== null) {
            $name = $prototype['rest_argument'];
            $rest = array_values(array_slice($positionalArgs, \count($prototype['arguments'])));

            $val = [Type::T_LIST, \is_null($splatSeparator) ? ',' : $splatSeparator , $rest, $restNamed];

            $output[$name] = $val;
        }

        return $output;
    }

    /**
     * Coerce a php value into a scss one
     *
     * @param mixed $value
     *
     * @return array|Number
     */
    protected function coerceValue($value)
    {
        if (\is_array($value) || $value instanceof Number) {
            return $value;
        }

        if (\is_bool($value)) {
            return $this->toBool($value);
        }

        if (\is_null($value)) {
            return static::$null;
        }

        if (is_numeric($value)) {
            return new Number($value, '');
        }

        if ($value === '') {
            return static::$emptyString;
        }

        $value = [Type::T_KEYWORD, $value];
        $color = $this->coerceColor($value);

        if ($color) {
            return $color;
        }

        return $value;
    }

    /**
     * Coerce something to map
     *
     * @param array|Number $item
     *
     * @return array|Number
     */
    protected function coerceMap($item)
    {
        if ($item[0] === Type::T_MAP) {
            return $item;
        }

        if (
            $item[0] === Type::T_LIST &&
            $item[2] === []
        ) {
            return static::$emptyMap;
        }

        return $item;
    }

    /**
     * Coerce something to list
     *
     * @param array|Number $item
     * @param string       $delim
     * @param boolean      $removeTrailingNull
     *
     * @return array
     */
    protected function coerceList($item, $delim = ',', $removeTrailingNull = false)
    {
        if ($item instanceof Number) {
            return [Type::T_LIST, $delim, [$item]];
        }

        if ($item[0] === Type::T_LIST) {
            // remove trailing null from the list
            if ($removeTrailingNull && end($item[2]) === static::$null) {
                array_pop($item[2]);
            }

            return $item;
        }

        if ($item[0] === Type::T_MAP) {
            $keys = $item[1];
            $values = $item[2];
            $list = [];

            for ($i = 0, $s = \count($keys); $i < $s; $i++) {
                $key = $keys[$i];
                $value = $values[$i];

                switch ($key[0]) {
                    case Type::T_LIST:
                    case Type::T_MAP:
                    case Type::T_STRING:
                    case Type::T_NULL:
                        break;

                    default:
                        $key = [Type::T_KEYWORD, $this->compileStringContent($this->coerceString($key))];
                        break;
                }

                $list[] = [
                    Type::T_LIST,
                    '',
                    [$key, $value]
                ];
            }

            return [Type::T_LIST, ',', $list];
        }

        return [Type::T_LIST, $delim, [$item]];
    }

    /**
     * Coerce color for expression
     *
     * @param array|Number $value
     *
     * @return array|Number
     */
    protected function coerceForExpression($value)
    {
        if ($color = $this->coerceColor($value)) {
            return $color;
        }

        return $value;
    }

    /**
     * Coerce value to color
     *
     * @param array|Number $value
     * @param bool         $inRGBFunction
     *
     * @return array|null
     */
    protected function coerceColor($value, $inRGBFunction = false)
    {
        if ($value instanceof Number) {
            return null;
        }

        switch ($value[0]) {
            case Type::T_COLOR:
                for ($i = 1; $i <= 3; $i++) {
                    if (! is_numeric($value[$i])) {
                        $cv = $this->compileRGBAValue($value[$i]);

                        if (! is_numeric($cv)) {
                            return null;
                        }

                        $value[$i] = $cv;
                    }

                    if (isset($value[4])) {
                        if (! is_numeric($value[4])) {
                            $cv = $this->compileRGBAValue($value[4], true);

                            if (! is_numeric($cv)) {
                                return null;
                            }

                            $value[4] = $cv;
                        }
                    }
                }

                return $value;

            case Type::T_LIST:
                if ($inRGBFunction) {
                    if (\count($value[2]) == 3 || \count($value[2]) == 4) {
                        $color = $value[2];
                        array_unshift($color, Type::T_COLOR);

                        return $this->coerceColor($color);
                    }
                }

                return null;

            case Type::T_KEYWORD:
                if (! \is_string($value[1])) {
                    return null;
                }

                $name = strtolower($value[1]);

                // hexa color?
                if (preg_match('/^#([0-9a-f]+)$/i', $name, $m)) {
                    $nofValues = \strlen($m[1]);

                    if (\in_array($nofValues, [3, 4, 6, 8])) {
                        $nbChannels = 3;
                        $color      = [];
                        $num        = hexdec($m[1]);

                        switch ($nofValues) {
                            case 4:
                                $nbChannels = 4;
                                // then continuing with the case 3:
                            case 3:
                                for ($i = 0; $i < $nbChannels; $i++) {
                                    $t = $num & 0xf;
                                    array_unshift($color, $t << 4 | $t);
                                    $num >>= 4;
                                }

                                break;

                            case 8:
                                $nbChannels = 4;
                                // then continuing with the case 6:
                            case 6:
                                for ($i = 0; $i < $nbChannels; $i++) {
                                    array_unshift($color, $num & 0xff);
                                    $num >>= 8;
                                }

                                break;
                        }

                        if ($nbChannels === 4) {
                            if ($color[3] === 255) {
                                $color[3] = 1; // fully opaque
                            } else {
                                $color[3] = round($color[3] / 255, Number::PRECISION);
                            }
                        }

                        array_unshift($color, Type::T_COLOR);

                        return $color;
                    }
                }

                if ($rgba = Colors::colorNameToRGBa($name)) {
                    return isset($rgba[3])
                        ? [Type::T_COLOR, $rgba[0], $rgba[1], $rgba[2], $rgba[3]]
                        : [Type::T_COLOR, $rgba[0], $rgba[1], $rgba[2]];
                }

                return null;
        }

        return null;
    }

    /**
     * @param integer|Number $value
     * @param boolean        $isAlpha
     *
     * @return integer|mixed
     */
    protected function compileRGBAValue($value, $isAlpha = false)
    {
        if ($isAlpha) {
            return $this->compileColorPartValue($value, 0, 1, false);
        }

        return $this->compileColorPartValue($value, 0, 255, true);
    }

    /**
     * @param mixed         $value
     * @param integer|float $min
     * @param integer|float $max
     * @param boolean       $isInt
     *
     * @return integer|mixed
     */
    protected function compileColorPartValue($value, $min, $max, $isInt = true)
    {
        if (! is_numeric($value)) {
            if (\is_array($value)) {
                $reduced = $this->reduce($value);

                if ($reduced instanceof Number) {
                    $value = $reduced;
                }
            }

            if ($value instanceof Number) {
                if ($value->unitless()) {
                    $num = $value->getDimension();
                } elseif ($value->hasUnit('%')) {
                    $num = $max * $value->getDimension() / 100;
                } else {
                    throw $this->error('Expected %s to have no units or "%%".', $value);
                }

                $value = $num;
            } elseif (\is_array($value)) {
                $value = $this->compileValue($value);
            }
        }

        if (is_numeric($value)) {
            if ($isInt) {
                $value = round($value);
            }

            $value = min($max, max($min, $value));

            return $value;
        }

        return $value;
    }

    /**
     * Coerce value to string
     *
     * @param array|Number $value
     *
     * @return array
     */
    protected function coerceString($value)
    {
        if ($value[0] === Type::T_STRING) {
            return $value;
        }

        return [Type::T_STRING, '', [$this->compileValue($value)]];
    }

    /**
     * Assert value is a string
     *
     * This method deals with internal implementation details of the value
     * representation where unquoted strings can sometimes be stored under
     * other types.
     * The returned value is always using the T_STRING type.
     *
     * @api
     *
     * @param array|Number $value
     * @param string|null  $varName
     *
     * @return array
     *
     * @throws SassScriptException
     */
    public function assertString($value, $varName = null)
    {
        // case of url(...) parsed a a function
        if ($value[0] === Type::T_FUNCTION) {
            $value = $this->coerceString($value);
        }

        if (! \in_array($value[0], [Type::T_STRING, Type::T_KEYWORD])) {
            $value = $this->compileValue($value);
            throw SassScriptException::forArgument("$value is not a string.", $varName);
        }

        return $this->coerceString($value);
    }

    /**
     * Coerce value to a percentage
     *
     * @param array|Number $value
     *
     * @return integer|float
     */
    protected function coercePercent($value)
    {
        if ($value instanceof Number) {
            if ($value->hasUnit('%')) {
                return $value->getDimension() / 100;
            }

            return $value->getDimension();
        }

        return 0;
    }

    /**
     * Assert value is a map
     *
     * @api
     *
     * @param array|Number $value
     * @param string|null  $varName
     *
     * @return array
     *
     * @throws SassScriptException
     */
    public function assertMap($value, $varName = null)
    {
        $value = $this->coerceMap($value);

        if ($value[0] !== Type::T_MAP) {
            $value = $this->compileValue($value);

            throw SassScriptException::forArgument("$value is not a map.", $varName);
        }

        return $value;
    }

    /**
     * Assert value is a list
     *
     * @api
     *
     * @param array|Number $value
     *
     * @return array
     *
     * @throws \Exception
     */
    public function assertList($value)
    {
        if ($value[0] !== Type::T_LIST) {
            throw $this->error('expecting list, %s received', $value[0]);
        }

        return $value;
    }

    /**
     * Gets the keywords of an argument list.
     *
     * Keys in the returned array are normalized names (underscores are replaced with dashes)
     * without the leading `$`.
     * Calling this helper with anything that an argument list received for a rest argument
     * of the function argument declaration is not supported.
     *
     * @param array|Number $value
     *
     * @return array<string, array|Number>
     */
    public function getArgumentListKeywords($value)
    {
        if ($value[0] !== Type::T_LIST || !isset($value[3]) || !\is_array($value[3])) {
            throw new \InvalidArgumentException('The argument is not a sass argument list.');
        }

        return $value[3];
    }

    /**
     * Assert value is a color
     *
     * @api
     *
     * @param array|Number $value
     * @param string|null  $varName
     *
     * @return array
     *
     * @throws SassScriptException
     */
    public function assertColor($value, $varName = null)
    {
        if ($color = $this->coerceColor($value)) {
            return $color;
        }

        $value = $this->compileValue($value);

        throw SassScriptException::forArgument("$value is not a color.", $varName);
    }

    /**
     * Assert value is a number
     *
     * @api
     *
     * @param array|Number $value
     * @param string|null  $varName
     *
     * @return Number
     *
     * @throws SassScriptException
     */
    public function assertNumber($value, $varName = null)
    {
        if (!$value instanceof Number) {
            $value = $this->compileValue($value);
            throw SassScriptException::forArgument("$value is not a number.", $varName);
        }

        return $value;
    }

    /**
     * Assert value is a integer
     *
     * @api
     *
     * @param array|Number $value
     * @param string|null  $varName
     *
     * @return integer
     *
     * @throws SassScriptException
     */
    public function assertInteger($value, $varName = null)
    {
        $value = $this->assertNumber($value, $varName)->getDimension();
        if (round($value - \intval($value), Number::PRECISION) > 0) {
            throw SassScriptException::forArgument("$value is not an integer.", $varName);
        }

        return intval($value);
    }

    /**
     * Extract the  ... / alpha on the last argument of channel arg
     * in color functions
     *
     * @param array $args
     * @return array
     */
    private function extractSlashAlphaInColorFunction($args)
    {
        $last = end($args);
        if (\count($args) === 3 && $last[0] === Type::T_EXPRESSION && $last[1] === '/') {
            array_pop($args);
            $args[] = $last[2];
            $args[] = $last[3];
        }
        return $args;
    }


    /**
     * Make sure a color's components don't go out of bounds
     *
     * @param array $c
     *
     * @return array
     */
    protected function fixColor($c)
    {
        foreach ([1, 2, 3] as $i) {
            if ($c[$i] < 0) {
                $c[$i] = 0;
            }

            if ($c[$i] > 255) {
                $c[$i] = 255;
            }

            if (!\is_int($c[$i])) {
                $c[$i] = round($c[$i]);
            }
        }

        return $c;
    }

    /**
     * Convert RGB to HSL
     *
     * @internal
     *
     * @param integer $red
     * @param integer $green
     * @param integer $blue
     *
     * @return array
     */
    public function toHSL($red, $green, $blue)
    {
        $min = min($red, $green, $blue);
        $max = max($red, $green, $blue);

        $l = $min + $max;
        $d = $max - $min;

        if ((int) $d === 0) {
            $h = $s = 0;
        } else {
            if ($l < 255) {
                $s = $d / $l;
            } else {
                $s = $d / (510 - $l);
            }

            if ($red == $max) {
                $h = 60 * ($green - $blue) / $d;
            } elseif ($green == $max) {
                $h = 60 * ($blue - $red) / $d + 120;
            } elseif ($blue == $max) {
                $h = 60 * ($red - $green) / $d + 240;
            }
        }

        return [Type::T_HSL, fmod($h, 360), $s * 100, $l / 5.1];
    }

    /**
     * Hue to RGB helper
     *
     * @param float $m1
     * @param float $m2
     * @param float $h
     *
     * @return float
     */
    protected function hueToRGB($m1, $m2, $h)
    {
        if ($h < 0) {
            $h += 1;
        } elseif ($h > 1) {
            $h -= 1;
        }

        if ($h * 6 < 1) {
            return $m1 + ($m2 - $m1) * $h * 6;
        }

        if ($h * 2 < 1) {
            return $m2;
        }

        if ($h * 3 < 2) {
            return $m1 + ($m2 - $m1) * (2 / 3 - $h) * 6;
        }

        return $m1;
    }

    /**
     * Convert HSL to RGB
     *
     * @internal
     *
     * @param int|float $hue        H from 0 to 360
     * @param int|float $saturation S from 0 to 100
     * @param int|float $lightness  L from 0 to 100
     *
     * @return array
     */
    public function toRGB($hue, $saturation, $lightness)
    {
        if ($hue < 0) {
            $hue += 360;
        }

        $h = $hue / 360;
        $s = min(100, max(0, $saturation)) / 100;
        $l = min(100, max(0, $lightness)) / 100;

        $m2 = $l <= 0.5 ? $l * ($s + 1) : $l + $s - $l * $s;
        $m1 = $l * 2 - $m2;

        $r = $this->hueToRGB($m1, $m2, $h + 1 / 3) * 255;
        $g = $this->hueToRGB($m1, $m2, $h) * 255;
        $b = $this->hueToRGB($m1, $m2, $h - 1 / 3) * 255;

        $out = [Type::T_COLOR, $r, $g, $b];

        return $out;
    }

    /**
     * Convert HWB to RGB
     * https://www.w3.org/TR/css-color-4/#hwb-to-rgb
     *
     * @api
     *
     * @param integer $hue        H from 0 to 360
     * @param integer $whiteness  W from 0 to 100
     * @param integer $blackness  B from 0 to 100
     *
     * @return array
     */
    private function HWBtoRGB($hue, $whiteness, $blackness)
    {
        $w = min(100, max(0, $whiteness)) / 100;
        $b = min(100, max(0, $blackness)) / 100;

        $sum = $w + $b;
        if ($sum > 1.0) {
            $w = $w / $sum;
            $b = $b / $sum;
        }
        $b = min(1.0 - $w, $b);

        $rgb = $this->toRGB($hue, 100, 50);
        for($i = 1; $i < 4; $i++) {
          $rgb[$i] *= (1.0 - $w - $b);
          $rgb[$i] = round($rgb[$i] + 255 * $w + 0.0001);
        }

        return $rgb;
    }

    /**
     * Convert RGB to HWB
     *
     * @api
     *
     * @param integer $red
     * @param integer $green
     * @param integer $blue
     *
     * @return array
     */
    private function RGBtoHWB($red, $green, $blue)
    {
        $min = min($red, $green, $blue);
        $max = max($red, $green, $blue);

        $d = $max - $min;

        if ((int) $d === 0) {
            $h = 0;
        } else {

            if ($red == $max) {
                $h = 60 * ($green - $blue) / $d;
            } elseif ($green == $max) {
                $h = 60 * ($blue - $red) / $d + 120;
            } elseif ($blue == $max) {
                $h = 60 * ($red - $green) / $d + 240;
            }
        }

        return [Type::T_HWB, fmod($h, 360), $min / 255 * 100, 100 - $max / 255 *100];
    }


    // Built in functions

    protected static $libCall = ['function', 'args...'];
    protected function libCall($args)
    {
        $functionReference = $args[0];

        if (in_array($functionReference[0], [Type::T_STRING, Type::T_KEYWORD])) {
            $name = $this->compileStringContent($this->coerceString($functionReference));
            $warning = "Passing a string to call() is deprecated and will be illegal\n"
                . "in Sass 4.0. Use call(function-reference($name)) instead.";
            Warn::deprecation($warning);
            $functionReference = $this->libGetFunction([$this->assertString($functionReference, 'function')]);
        }

        if ($functionReference === static::$null) {
            return static::$null;
        }

        if (! in_array($functionReference[0], [Type::T_FUNCTION_REFERENCE, Type::T_FUNCTION])) {
            throw $this->error('Function reference expected, got ' . $functionReference[0]);
        }

        $callArgs = [
            [null, $args[1], true]
        ];

        return $this->reduce([Type::T_FUNCTION_CALL, $functionReference, $callArgs]);
    }


    protected static $libGetFunction = [
        ['name'],
        ['name', 'css']
    ];
    protected function libGetFunction($args)
    {
        $name = $this->compileStringContent($this->assertString(array_shift($args), 'name'));
        $isCss = false;

        if (count($args)) {
            $isCss = array_shift($args);
            $isCss = (($isCss === static::$true) ? true : false);
        }

        if ($isCss) {
            return [Type::T_FUNCTION, $name, [Type::T_LIST, ',', []]];
        }

        return $this->getFunctionReference($name, true);
    }

    protected static $libIf = ['condition', 'if-true', 'if-false:'];
    protected function libIf($args)
    {
        list($cond, $t, $f) = $args;

        if (! $this->isTruthy($this->reduce($cond, true))) {
            return $this->reduce($f, true);
        }

        return $this->reduce($t, true);
    }

    protected static $libIndex = ['list', 'value'];
    protected function libIndex($args)
    {
        list($list, $value) = $args;

        if (
            $list[0] === Type::T_MAP ||
            $list[0] === Type::T_STRING ||
            $list[0] === Type::T_KEYWORD ||
            $list[0] === Type::T_INTERPOLATE
        ) {
            $list = $this->coerceList($list, ' ');
        }

        if ($list[0] !== Type::T_LIST) {
            return static::$null;
        }

        // Numbers are represented with value objects, for which the PHP equality operator does not
        // match the Sass rules (and we cannot overload it). As they are the only type of values
        // represented with a value object for now, they require a special case.
        if ($value instanceof Number) {
            $key = 0;
            foreach ($list[2] as $item) {
                $key++;
                $itemValue = $this->normalizeValue($item);

                if ($itemValue instanceof Number && $value->equals($itemValue)) {
                    return new Number($key, '');
                }
            }
            return static::$null;
        }

        $values = [];


        foreach ($list[2] as $item) {
            $values[] = $this->normalizeValue($item);
        }

        $key = array_search($this->normalizeValue($value), $values);

        return false === $key ? static::$null : new Number($key + 1, '');
    }

    protected static $libRgb = [
        ['color'],
        ['color', 'alpha'],
        ['channels'],
        ['red', 'green', 'blue'],
        ['red', 'green', 'blue', 'alpha'] ];
    protected function libRgb($args, $kwargs, $funcName = 'rgb')
    {
        switch (\count($args)) {
            case 1:
                if (! $color = $this->coerceColor($args[0], true)) {
                    $color = [Type::T_STRING, '', [$funcName . '(', $args[0], ')']];
                }
                break;

            case 3:
                $color = [Type::T_COLOR, $args[0], $args[1], $args[2]];

                if (! $color = $this->coerceColor($color)) {
                    $color = [Type::T_STRING, '', [$funcName . '(', $args[0], ', ', $args[1], ', ', $args[2], ')']];
                }

                return $color;

            case 2:
                if ($color = $this->coerceColor($args[0], true)) {
                    $alpha = $this->compileRGBAValue($args[1], true);

                    if (is_numeric($alpha)) {
                        $color[4] = $alpha;
                    } else {
                        $color = [Type::T_STRING, '',
                            [$funcName . '(', $color[1], ', ', $color[2], ', ', $color[3], ', ', $alpha, ')']];
                    }
                } else {
                    $color = [Type::T_STRING, '', [$funcName . '(', $args[0], ')']];
                }
                break;

            case 4:
            default:
                $color = [Type::T_COLOR, $args[0], $args[1], $args[2], $args[3]];

                if (! $color = $this->coerceColor($color)) {
                    $color = [Type::T_STRING, '',
                        [$funcName . '(', $args[0], ', ', $args[1], ', ', $args[2], ', ', $args[3], ')']];
                }
                break;
        }

        return $color;
    }

    protected static $libRgba = [
        ['color'],
        ['color', 'alpha'],
        ['channels'],
        ['red', 'green', 'blue'],
        ['red', 'green', 'blue', 'alpha'] ];
    protected function libRgba($args, $kwargs)
    {
        return $this->libRgb($args, $kwargs, 'rgba');
    }

    /**
     * Helper function for adjust_color, change_color, and scale_color
     *
     * @param array<array|Number> $args
     * @param string $operation
     * @param callable $fn
     *
     * @return array
     *
     * @phpstan-param callable(float|int, float|int|null, float|int): (float|int) $fn
     */
    protected function alterColor(array $args, $operation, $fn)
    {
        $color = $this->assertColor($args[0], 'color');

        if ($args[1][2]) {
            throw new SassScriptException('Only one positional argument is allowed. All other arguments must be passed by name.');
        }

        $kwargs = $this->getArgumentListKeywords($args[1]);

        $scale = $operation === 'scale';
        $change = $operation === 'change';

        /**
         * @param string $name
         * @param float|int $max
         * @param bool $checkPercent
         * @param bool $assertPercent
         *
         * @return float|int|null
         */
        $getParam = function ($name, $max, $checkPercent = false, $assertPercent = false) use (&$kwargs, $scale, $change) {
            if (!isset($kwargs[$name])) {
                return null;
            }

            $number = $this->assertNumber($kwargs[$name], $name);
            unset($kwargs[$name]);

            if (!$scale && $checkPercent) {
                if (!$number->hasUnit('%')) {
                    $warning = $this->error("{$name} Passing a number `$number` without unit % is deprecated.");
                    $this->logger->warn($warning->getMessage(), true);
                }
            }

            if ($scale || $assertPercent) {
                $number->assertUnit('%', $name);
            }

            if ($scale) {
                $max = 100;
            }

            return $number->valueInRange($change ? 0 : -$max, $max, $name);
        };

        $alpha = $getParam('alpha', 1);
        $red = $getParam('red', 255);
        $green = $getParam('green', 255);
        $blue = $getParam('blue', 255);

        if ($scale || !isset($kwargs['hue'])) {
            $hue = null;
        } else {
            $hueNumber = $this->assertNumber($kwargs['hue'], 'hue');
            unset($kwargs['hue']);
            $hue = $hueNumber->getDimension();
        }
        $saturation = $getParam('saturation', 100, true);
        $lightness = $getParam('lightness', 100, true);
        $whiteness = $getParam('whiteness', 100, false, true);
        $blackness = $getParam('blackness', 100, false, true);

        if (!empty($kwargs)) {
            $unknownNames = array_keys($kwargs);
            $lastName = array_pop($unknownNames);
            $message = sprintf(
                'No argument%s named $%s%s.',
                $unknownNames ? 's' : '',
                $unknownNames ? implode(', $', $unknownNames) . ' or $' : '',
                $lastName
            );
            throw new SassScriptException($message);
        }

        $hasRgb = $red !== null || $green !== null || $blue !== null;
        $hasSL = $saturation !== null || $lightness !== null;
        $hasWB = $whiteness !== null || $blackness !== null;
        $found = false;

        if ($hasRgb && ($hasSL || $hasWB || $hue !== null)) {
            throw new SassScriptException(sprintf('RGB parameters may not be passed along with %s parameters.', $hasWB ? 'HWB' : 'HSL'));
        }

        if ($hasWB && $hasSL) {
            throw new SassScriptException('HSL parameters may not be passed along with HWB parameters.');
        }

        if ($hasRgb) {
            $color[1] = round($fn($color[1], $red, 255));
            $color[2] = round($fn($color[2], $green, 255));
            $color[3] = round($fn($color[3], $blue, 255));
        } elseif ($hasWB) {
            $hwb = $this->RGBtoHWB($color[1], $color[2], $color[3]);
            if ($hue !== null) {
                $hwb[1] = $change ? $hue : $hwb[1] + $hue;
            }
            $hwb[2] = $fn($hwb[2], $whiteness, 100);
            $hwb[3] = $fn($hwb[3], $blackness, 100);

            $rgb = $this->HWBtoRGB($hwb[1], $hwb[2], $hwb[3]);

            if (isset($color[4])) {
                $rgb[4] = $color[4];
            }

            $color = $rgb;
        } elseif ($hue !== null || $hasSL) {
            $hsl = $this->toHSL($color[1], $color[2], $color[3]);

            if ($hue !== null) {
                $hsl[1] = $change ? $hue : $hsl[1] + $hue;
            }
            $hsl[2] = $fn($hsl[2], $saturation, 100);
            $hsl[3] = $fn($hsl[3], $lightness, 100);

            $rgb = $this->toRGB($hsl[1], $hsl[2], $hsl[3]);

            if (isset($color[4])) {
                $rgb[4] = $color[4];
            }

            $color = $rgb;
        }

        if ($alpha !== null) {
            $existingAlpha = isset($color[4]) ? $color[4] : 1;
            $color[4] = $fn($existingAlpha, $alpha, 1);
        }

        return $color;
    }

    protected static $libAdjustColor = ['color', 'kwargs...'];
    protected function libAdjustColor($args)
    {
        return $this->alterColor($args, 'adjust', function ($base, $alter, $max) {
            if ($alter === null) {
                return $base;
            }

            $new = $base + $alter;

            if ($new < 0) {
                return 0;
            }

            if ($new > $max) {
                return $max;
            }

            return $new;
        });
    }

    protected static $libChangeColor = ['color', 'kwargs...'];
    protected function libChangeColor($args)
    {
        return $this->alterColor($args,'change', function ($base, $alter, $max) {
            if ($alter === null) {
                return $base;
            }

            return $alter;
        });
    }

    protected static $libScaleColor = ['color', 'kwargs...'];
    protected function libScaleColor($args)
    {
        return $this->alterColor($args, 'scale', function ($base, $scale, $max) {
            if ($scale === null) {
                return $base;
            }

            $scale = $scale / 100;

            if ($scale < 0) {
                return $base * $scale + $base;
            }

            return ($max - $base) * $scale + $base;
        });
    }

    protected static $libIeHexStr = ['color'];
    protected function libIeHexStr($args)
    {
        $color = $this->coerceColor($args[0]);

        if (\is_null($color)) {
            throw $this->error('Error: argument `$color` of `ie-hex-str($color)` must be a color');
        }

        $color[4] = isset($color[4]) ? round(255 * $color[4]) : 255;

        return [Type::T_STRING, '', [sprintf('#%02X%02X%02X%02X', $color[4], $color[1], $color[2], $color[3])]];
    }

    protected static $libRed = ['color'];
    protected function libRed($args)
    {
        $color = $this->coerceColor($args[0]);

        if (\is_null($color)) {
            throw $this->error('Error: argument `$color` of `red($color)` must be a color');
        }

        return new Number((int) $color[1], '');
    }

    protected static $libGreen = ['color'];
    protected function libGreen($args)
    {
        $color = $this->coerceColor($args[0]);

        if (\is_null($color)) {
            throw $this->error('Error: argument `$color` of `green($color)` must be a color');
        }

        return new Number((int) $color[2], '');
    }

    protected static $libBlue = ['color'];
    protected function libBlue($args)
    {
        $color = $this->coerceColor($args[0]);

        if (\is_null($color)) {
            throw $this->error('Error: argument `$color` of `blue($color)` must be a color');
        }

        return new Number((int) $color[3], '');
    }

    protected static $libAlpha = ['color'];
    protected function libAlpha($args)
    {
        if ($color = $this->coerceColor($args[0])) {
            return new Number(isset($color[4]) ? $color[4] : 1, '');
        }

        // this might be the IE function, so return value unchanged
        return null;
    }

    protected static $libOpacity = ['color'];
    protected function libOpacity($args)
    {
        $value = $args[0];

        if ($value instanceof Number) {
            return null;
        }

        return $this->libAlpha($args);
    }

    // mix two colors
    protected static $libMix = [
        ['color1', 'color2', 'weight:0.5'],
        ['color-1', 'color-2', 'weight:0.5']
        ];
    protected function libMix($args)
    {
        list($first, $second, $weight) = $args;

        $first = $this->assertColor($first, 'color1');
        $second = $this->assertColor($second, 'color2');
        $weight = $this->coercePercent($this->assertNumber($weight, 'weight'));

        $firstAlpha = isset($first[4]) ? $first[4] : 1;
        $secondAlpha = isset($second[4]) ? $second[4] : 1;

        $w = $weight * 2 - 1;
        $a = $firstAlpha - $secondAlpha;

        $w1 = (($w * $a === -1 ? $w : ($w + $a) / (1 + $w * $a)) + 1) / 2.0;
        $w2 = 1.0 - $w1;

        $new = [Type::T_COLOR,
            $w1 * $first[1] + $w2 * $second[1],
            $w1 * $first[2] + $w2 * $second[2],
            $w1 * $first[3] + $w2 * $second[3],
        ];

        if ($firstAlpha != 1.0 || $secondAlpha != 1.0) {
            $new[] = $firstAlpha * $weight + $secondAlpha * (1 - $weight);
        }

        return $this->fixColor($new);
    }

    protected static $libHsl = [
        ['channels'],
        ['hue', 'saturation'],
        ['hue', 'saturation', 'lightness'],
        ['hue', 'saturation', 'lightness', 'alpha'] ];
    protected function libHsl($args, $kwargs, $funcName = 'hsl')
    {
        $args_to_check = $args;

        if (\count($args) == 1) {
            if ($args[0][0] !== Type::T_LIST || \count($args[0][2]) < 3 || \count($args[0][2]) > 4) {
                return [Type::T_STRING, '', [$funcName . '(', $args[0], ')']];
            }

            $args = $args[0][2];
            $args_to_check = $kwargs['channels'][2];
        }

        if (\count($args) === 2) {
            // if var() is used as an argument, return as a css function
            foreach ($args as $arg) {
                if ($arg[0] === Type::T_FUNCTION && in_array($arg[1], ['var'])) {
                    return null;
                }
            }

            throw new SassScriptException('Missing argument $lightness.');
        }

        foreach ($kwargs as $k => $arg) {
            if (in_array($arg[0], [Type::T_FUNCTION_CALL, Type::T_FUNCTION]) && in_array($arg[1], ['min', 'max'])) {
                return null;
            }
        }

        foreach ($args_to_check as $k => $arg) {
            if (in_array($arg[0], [Type::T_FUNCTION_CALL, Type::T_FUNCTION]) && in_array($arg[1], ['min', 'max'])) {
                if (count($kwargs) > 1 || ($k >= 2 && count($args) === 4)) {
                    return null;
                }

                $args[$k] = $this->stringifyFncallArgs($arg);
            }

            if (
                $k >= 2 && count($args) === 4 &&
                in_array($arg[0], [Type::T_FUNCTION_CALL, Type::T_FUNCTION]) &&
                in_array($arg[1], ['calc','env'])
            ) {
                return null;
            }
        }

        $hue = $this->reduce($args[0]);
        $saturation = $this->reduce($args[1]);
        $lightness = $this->reduce($args[2]);
        $alpha = null;

        if (\count($args) === 4) {
            $alpha = $this->compileColorPartValue($args[3], 0, 100, false);

            if (!$hue instanceof Number || !$saturation instanceof Number || ! $lightness instanceof Number || ! is_numeric($alpha)) {
                return [Type::T_STRING, '',
                    [$funcName . '(', $args[0], ', ', $args[1], ', ', $args[2], ', ', $args[3], ')']];
            }
        } else {
            if (!$hue instanceof Number || !$saturation instanceof Number || ! $lightness instanceof Number) {
                return [Type::T_STRING, '', [$funcName . '(', $args[0], ', ', $args[1], ', ', $args[2], ')']];
            }
        }

        $hueValue = $hue->getDimension() % 360;

        while ($hueValue < 0) {
            $hueValue += 360;
        }

        $color = $this->toRGB($hueValue, max(0, min($saturation->getDimension(), 100)), max(0, min($lightness->getDimension(), 100)));

        if (! \is_null($alpha)) {
            $color[4] = $alpha;
        }

        return $color;
    }

    protected static $libHsla = [
            ['channels'],
            ['hue', 'saturation'],
            ['hue', 'saturation', 'lightness'],
            ['hue', 'saturation', 'lightness', 'alpha']];
    protected function libHsla($args, $kwargs)
    {
        return $this->libHsl($args, $kwargs, 'hsla');
    }

    protected static $libHue = ['color'];
    protected function libHue($args)
    {
        $color = $this->assertColor($args[0], 'color');
        $hsl = $this->toHSL($color[1], $color[2], $color[3]);

        return new Number($hsl[1], 'deg');
    }

    protected static $libSaturation = ['color'];
    protected function libSaturation($args)
    {
        $color = $this->assertColor($args[0], 'color');
        $hsl = $this->toHSL($color[1], $color[2], $color[3]);

        return new Number($hsl[2], '%');
    }

    protected static $libLightness = ['color'];
    protected function libLightness($args)
    {
        $color = $this->assertColor($args[0], 'color');
        $hsl = $this->toHSL($color[1], $color[2], $color[3]);

        return new Number($hsl[3], '%');
    }

    /*
     * Todo : a integrer dans le futur module color
    protected static $libHwb = [
        ['channels'],
        ['hue', 'whiteness', 'blackness'],
        ['hue', 'whiteness', 'blackness', 'alpha'] ];
    protected function libHwb($args, $kwargs, $funcName = 'hwb')
    {
        $args_to_check = $args;

        if (\count($args) == 1) {
            if ($args[0][0] !== Type::T_LIST) {
                throw $this->error("Missing elements \$whiteness and \$blackness");
            }

            if (\trim($args[0][1])) {
                throw $this->error("\$channels must be a space-separated list.");
            }

            if (! empty($args[0]['enclosing'])) {
                throw $this->error("\$channels must be an unbracketed list.");
            }

            $args = $args[0][2];
            if (\count($args) > 3) {
                throw $this->error("hwb() : Only 3 elements are allowed but ". \count($args) . "were passed");
            }

            $args_to_check = $this->extractSlashAlphaInColorFunction($kwargs['channels'][2]);
            if (\count($args_to_check) !== \count($kwargs['channels'][2])) {
                $args = $args_to_check;
            }
        }

        if (\count($args_to_check) < 2) {
            throw $this->error("Missing elements \$whiteness and \$blackness");
        }
        if (\count($args_to_check) < 3) {
            throw $this->error("Missing element \$blackness");
        }
        if (\count($args_to_check) > 4) {
            throw $this->error("hwb() : Only 4 elements are allowed but ". \count($args) . "were passed");
        }

        foreach ($kwargs as $k => $arg) {
            if (in_array($arg[0], [Type::T_FUNCTION_CALL]) && in_array($arg[1], ['min', 'max'])) {
                return null;
            }
        }

        foreach ($args_to_check as $k => $arg) {
            if (in_array($arg[0], [Type::T_FUNCTION_CALL]) && in_array($arg[1], ['min', 'max'])) {
                if (count($kwargs) > 1 || ($k >= 2 && count($args) === 4)) {
                    return null;
                }

                $args[$k] = $this->stringifyFncallArgs($arg);
            }

            if (
                $k >= 2 && count($args) === 4 &&
                in_array($arg[0], [Type::T_FUNCTION_CALL, Type::T_FUNCTION]) &&
                in_array($arg[1], ['calc','env'])
            ) {
                return null;
            }
        }

        $hue = $this->reduce($args[0]);
        $whiteness = $this->reduce($args[1]);
        $blackness = $this->reduce($args[2]);
        $alpha = null;

        if (\count($args) === 4) {
            $alpha = $this->compileColorPartValue($args[3], 0, 1, false);

            if (! \is_numeric($alpha)) {
                $val = $this->compileValue($args[3]);
                throw $this->error("\$alpha: $val is not a number");
            }
        }

        $this->assertNumber($hue, 'hue');
        $this->assertUnit($whiteness, ['%'], 'whiteness');
        $this->assertUnit($blackness, ['%'], 'blackness');

        $this->assertRange($whiteness, 0, 100, "0% and 100%", "whiteness");
        $this->assertRange($blackness, 0, 100, "0% and 100%", "blackness");

        $w = $whiteness->getDimension();
        $b = $blackness->getDimension();

        $hueValue = $hue->getDimension() % 360;

        while ($hueValue < 0) {
            $hueValue += 360;
        }

        $color = $this->HWBtoRGB($hueValue, $w, $b);

        if (! \is_null($alpha)) {
            $color[4] = $alpha;
        }

        return $color;
    }

    protected static $libWhiteness = ['color'];
    protected function libWhiteness($args, $kwargs, $funcName = 'whiteness') {

        $color = $this->assertColor($args[0]);
        $hwb = $this->RGBtoHWB($color[1], $color[2], $color[3]);

        return new Number($hwb[2], '%');
    }

    protected static $libBlackness = ['color'];
    protected function libBlackness($args, $kwargs, $funcName = 'blackness') {

        $color = $this->assertColor($args[0]);
        $hwb = $this->RGBtoHWB($color[1], $color[2], $color[3]);

        return new Number($hwb[3], '%');
    }
    */

    protected function adjustHsl($color, $idx, $amount)
    {
        $hsl = $this->toHSL($color[1], $color[2], $color[3]);
        $hsl[$idx] += $amount;
        $out = $this->toRGB($hsl[1], $hsl[2], $hsl[3]);

        if (isset($color[4])) {
            $out[4] = $color[4];
        }

        return $out;
    }

    protected static $libAdjustHue = ['color', 'degrees'];
    protected function libAdjustHue($args)
    {
        $color = $this->assertColor($args[0], 'color');
        $degrees = $this->assertNumber($args[1], 'degrees')->getDimension();

        return $this->adjustHsl($color, 1, $degrees);
    }

    protected static $libLighten = ['color', 'amount'];
    protected function libLighten($args)
    {
        $color = $this->assertColor($args[0], 'color');
        $amount = Util::checkRange('amount', new Range(0, 100), $args[1], '%');

        return $this->adjustHsl($color, 3, $amount);
    }

    protected static $libDarken = ['color', 'amount'];
    protected function libDarken($args)
    {
        $color = $this->assertColor($args[0], 'color');
        $amount = Util::checkRange('amount', new Range(0, 100), $args[1], '%');

        return $this->adjustHsl($color, 3, -$amount);
    }

    protected static $libSaturate = [['color', 'amount'], ['amount']];
    protected function libSaturate($args)
    {
        $value = $args[0];

        if (count($args) === 1) {
            $this->assertNumber($args[0], 'amount');

            return null;
        }

        $color = $this->assertColor($value, 'color');
        $amount = 100 * $this->coercePercent($this->assertNumber($args[1], 'amount'));

        return $this->adjustHsl($color, 2, $amount);
    }

    protected static $libDesaturate = ['color', 'amount'];
    protected function libDesaturate($args)
    {
        $color = $this->assertColor($args[0], 'color');
        $amount = 100 * $this->coercePercent($this->assertNumber($args[1], 'amount'));

        return $this->adjustHsl($color, 2, -$amount);
    }

    protected static $libGrayscale = ['color'];
    protected function libGrayscale($args)
    {
        $value = $args[0];

        if ($value instanceof Number) {
            return null;
        }

        return $this->adjustHsl($this->assertColor($value, 'color'), 2, -100);
    }

    protected static $libComplement = ['color'];
    protected function libComplement($args)
    {
        return $this->adjustHsl($this->assertColor($args[0], 'color'), 1, 180);
    }

    protected static $libInvert = ['color', 'weight:1'];
    protected function libInvert($args)
    {
        $value = $args[0];

        if ($value instanceof Number) {
            return null;
        }

        $weight = $this->coercePercent($this->assertNumber($args[1], 'weight'));

        $color = $this->assertColor($value, 'color');
        $inverted = $color;
        $inverted[1] = 255 - $inverted[1];
        $inverted[2] = 255 - $inverted[2];
        $inverted[3] = 255 - $inverted[3];

        if ($weight < 1) {
            return $this->libMix([$inverted, $color, new Number($weight, '')]);
        }

        return $inverted;
    }

    // increases opacity by amount
    protected static $libOpacify = ['color', 'amount'];
    protected function libOpacify($args)
    {
        $color = $this->assertColor($args[0], 'color');
        $amount = $this->coercePercent($this->assertNumber($args[1], 'amount'));

        $color[4] = (isset($color[4]) ? $color[4] : 1) + $amount;
        $color[4] = min(1, max(0, $color[4]));

        return $color;
    }

    protected static $libFadeIn = ['color', 'amount'];
    protected function libFadeIn($args)
    {
        return $this->libOpacify($args);
    }

    // decreases opacity by amount
    protected static $libTransparentize = ['color', 'amount'];
    protected function libTransparentize($args)
    {
        $color = $this->assertColor($args[0], 'color');
        $amount = $this->coercePercent($this->assertNumber($args[1], 'amount'));

        $color[4] = (isset($color[4]) ? $color[4] : 1) - $amount;
        $color[4] = min(1, max(0, $color[4]));

        return $color;
    }

    protected static $libFadeOut = ['color', 'amount'];
    protected function libFadeOut($args)
    {
        return $this->libTransparentize($args);
    }

    protected static $libUnquote = ['string'];
    protected function libUnquote($args)
    {
        try {
            $str = $this->assertString($args[0], 'string');
        } catch (SassScriptException $e) {
            $value = $this->compileValue($args[0]);
            $fname = $this->getPrettyPath($this->sourceNames[$this->sourceIndex]);
            $line  = $this->sourceLine;

            $message = "Passing $value, a non-string value, to unquote()
will be an error in future versions of Sass.\n         on line $line of $fname";

            $this->logger->warn($message, true);

            return $args[0];
        }

        $str[1] = '';

        return $str;
    }

    protected static $libQuote = ['string'];
    protected function libQuote($args)
    {
        $value = $this->assertString($args[0], 'string');

        $value[1] = '"';

        return $value;
    }

    protected static $libPercentage = ['number'];
    protected function libPercentage($args)
    {
        $num = $this->assertNumber($args[0], 'number');
        $num->assertNoUnits('number');

        return new Number($num->getDimension() * 100, '%');
    }

    protected static $libRound = ['number'];
    protected function libRound($args)
    {
        $num = $this->assertNumber($args[0], 'number');

        return new Number(round($num->getDimension()), $num->getNumeratorUnits(), $num->getDenominatorUnits());
    }

    protected static $libFloor = ['number'];
    protected function libFloor($args)
    {
        $num = $this->assertNumber($args[0], 'number');

        return new Number(floor($num->getDimension()), $num->getNumeratorUnits(), $num->getDenominatorUnits());
    }

    protected static $libCeil = ['number'];
    protected function libCeil($args)
    {
        $num = $this->assertNumber($args[0], 'number');

        return new Number(ceil($num->getDimension()), $num->getNumeratorUnits(), $num->getDenominatorUnits());
    }

    protected static $libAbs = ['number'];
    protected function libAbs($args)
    {
        $num = $this->assertNumber($args[0], 'number');

        return new Number(abs($num->getDimension()), $num->getNumeratorUnits(), $num->getDenominatorUnits());
    }

    protected static $libMin = ['numbers...'];
    protected function libMin($args)
    {
        /**
         * @var Number|null
         */
        $min = null;

        foreach ($args[0][2] as $arg) {
            $number = $this->assertNumber($arg);

            if (\is_null($min) || $min->greaterThan($number)) {
                $min = $number;
            }
        }

        if (!\is_null($min)) {
            return $min;
        }

        throw $this->error('At least one argument must be passed.');
    }

    protected static $libMax = ['numbers...'];
    protected function libMax($args)
    {
        /**
         * @var Number|null
         */
        $max = null;

        foreach ($args[0][2] as $arg) {
            $number = $this->assertNumber($arg);

            if (\is_null($max) || $max->lessThan($number)) {
                $max = $number;
            }
        }

        if (!\is_null($max)) {
            return $max;
        }

        throw $this->error('At least one argument must be passed.');
    }

    protected static $libLength = ['list'];
    protected function libLength($args)
    {
        $list = $this->coerceList($args[0], ',', true);

        return new Number(\count($list[2]), '');
    }

    protected static $libListSeparator = ['list'];
    protected function libListSeparator($args)
    {
        if (! \in_array($args[0][0], [Type::T_LIST, Type::T_MAP])) {
            return [Type::T_KEYWORD, 'space'];
        }

        $list = $this->coerceList($args[0]);

        if (\count($list[2]) <= 1 && empty($list['enclosing'])) {
            return [Type::T_KEYWORD, 'space'];
        }

        if ($list[1] === ',') {
            return [Type::T_KEYWORD, 'comma'];
        }

        return [Type::T_KEYWORD, 'space'];
    }

    protected static $libNth = ['list', 'n'];
    protected function libNth($args)
    {
        $list = $this->coerceList($args[0], ',', false);
        $n = $this->assertNumber($args[1])->getDimension();

        if ($n > 0) {
            $n--;
        } elseif ($n < 0) {
            $n += \count($list[2]);
        }

        return isset($list[2][$n]) ? $list[2][$n] : static::$defaultValue;
    }

    protected static $libSetNth = ['list', 'n', 'value'];
    protected function libSetNth($args)
    {
        $list = $this->coerceList($args[0]);
        $n = $this->assertNumber($args[1])->getDimension();

        if ($n > 0) {
            $n--;
        } elseif ($n < 0) {
            $n += \count($list[2]);
        }

        if (! isset($list[2][$n])) {
            throw $this->error('Invalid argument for "n"');
        }

        $list[2][$n] = $args[2];

        return $list;
    }

    protected static $libMapGet = ['map', 'key'];
    protected function libMapGet($args)
    {
        $map = $this->assertMap($args[0], 'map');
        $key = $args[1];

        if (! \is_null($key)) {
            $key = $this->compileStringContent($this->coerceString($key));

            for ($i = \count($map[1]) - 1; $i >= 0; $i--) {
                if ($key === $this->compileStringContent($this->coerceString($map[1][$i]))) {
                    return $map[2][$i];
                }
            }
        }

        return static::$null;
    }

    protected static $libMapKeys = ['map'];
    protected function libMapKeys($args)
    {
        $map = $this->assertMap($args[0], 'map');
        $keys = $map[1];

        return [Type::T_LIST, ',', $keys];
    }

    protected static $libMapValues = ['map'];
    protected function libMapValues($args)
    {
        $map = $this->assertMap($args[0], 'map');
        $values = $map[2];

        return [Type::T_LIST, ',', $values];
    }

    protected static $libMapRemove = [
        ['map'],
        ['map', 'key', 'keys...'],
    ];
    protected function libMapRemove($args)
    {
        $map = $this->assertMap($args[0], 'map');

        if (\count($args) === 1) {
            return $map;
        }

        $keys = [];
        $keys[] = $this->compileStringContent($this->coerceString($args[1]));

        foreach ($args[2][2] as $key) {
            $keys[] = $this->compileStringContent($this->coerceString($key));
        }

        for ($i = \count($map[1]) - 1; $i >= 0; $i--) {
            if (in_array($this->compileStringContent($this->coerceString($map[1][$i])), $keys)) {
                array_splice($map[1], $i, 1);
                array_splice($map[2], $i, 1);
            }
        }

        return $map;
    }

    protected static $libMapHasKey = ['map', 'key'];
    protected function libMapHasKey($args)
    {
        $map = $this->assertMap($args[0], 'map');

        return $this->toBool($this->mapHasKey($map, $args[1]));
    }

    /**
     * @param array|Number $keyValue
     *
     * @return bool
     */
    private function mapHasKey(array $map, $keyValue)
    {
        $key = $this->compileStringContent($this->coerceString($keyValue));

        for ($i = \count($map[1]) - 1; $i >= 0; $i--) {
            if ($key === $this->compileStringContent($this->coerceString($map[1][$i]))) {
                return true;
            }
        }

        return false;
    }

    protected static $libMapMerge = [
        ['map1', 'map2'],
        ['map-1', 'map-2']
    ];
    protected function libMapMerge($args)
    {
        $map1 = $this->assertMap($args[0], 'map1');
        $map2 = $this->assertMap($args[1], 'map2');

        foreach ($map2[1] as $i2 => $key2) {
            $key = $this->compileStringContent($this->coerceString($key2));

            foreach ($map1[1] as $i1 => $key1) {
                if ($key === $this->compileStringContent($this->coerceString($key1))) {
                    $map1[2][$i1] = $map2[2][$i2];
                    continue 2;
                }
            }

            $map1[1][] = $map2[1][$i2];
            $map1[2][] = $map2[2][$i2];
        }

        return $map1;
    }

    protected static $libKeywords = ['args'];
    protected function libKeywords($args)
    {
        $value = $args[0];

        if ($value[0] !== Type::T_LIST || !isset($value[3]) || !\is_array($value[3])) {
            $compiledValue = $this->compileValue($value);

            throw SassScriptException::forArgument($compiledValue . ' is not an argument list.', 'args');
        }

        $keys = [];
        $values = [];

        foreach ($this->getArgumentListKeywords($value) as $name => $arg) {
            $keys[] = [Type::T_KEYWORD, $name];
            $values[] = $arg;
        }

        return [Type::T_MAP, $keys, $values];
    }

    protected static $libIsBracketed = ['list'];
    protected function libIsBracketed($args)
    {
        $list = $args[0];
        $this->coerceList($list, ' ');

        if (! empty($list['enclosing']) && $list['enclosing'] === 'bracket') {
            return self::$true;
        }

        return self::$false;
    }

    /**
     * @param array $list1
     * @param array|Number|null $sep
     *
     * @return string
     * @throws CompilerException
     */
    protected function listSeparatorForJoin($list1, $sep)
    {
        if (! isset($sep)) {
            return $list1[1];
        }

        switch ($this->compileValue($sep)) {
            case 'comma':
                return ',';

            case 'space':
                return ' ';

            default:
                return $list1[1];
        }
    }

    protected static $libJoin = ['list1', 'list2', 'separator:null', 'bracketed:auto'];
    protected function libJoin($args)
    {
        list($list1, $list2, $sep, $bracketed) = $args;

        $list1 = $this->coerceList($list1, ' ', true);
        $list2 = $this->coerceList($list2, ' ', true);
        $sep   = $this->listSeparatorForJoin($list1, $sep);

        if ($bracketed === static::$true) {
            $bracketed = true;
        } elseif ($bracketed === static::$false) {
            $bracketed = false;
        } elseif ($bracketed === [Type::T_KEYWORD, 'auto']) {
            $bracketed = 'auto';
        } elseif ($bracketed === static::$null) {
            $bracketed = false;
        } else {
            $bracketed = $this->compileValue($bracketed);
            $bracketed = ! ! $bracketed;

            if ($bracketed === true) {
                $bracketed = true;
            }
        }

        if ($bracketed === 'auto') {
            $bracketed = false;

            if (! empty($list1['enclosing']) && $list1['enclosing'] === 'bracket') {
                $bracketed = true;
            }
        }

        $res = [Type::T_LIST, $sep, array_merge($list1[2], $list2[2])];

        if (isset($list1['enclosing'])) {
            $res['enlcosing'] = $list1['enclosing'];
        }

        if ($bracketed) {
            $res['enclosing'] = 'bracket';
        }

        return $res;
    }

    protected static $libAppend = ['list', 'val', 'separator:null'];
    protected function libAppend($args)
    {
        list($list1, $value, $sep) = $args;

        $list1 = $this->coerceList($list1, ' ', true);
        $sep   = $this->listSeparatorForJoin($list1, $sep);
        $res   = [Type::T_LIST, $sep, array_merge($list1[2], [$value])];

        if (isset($list1['enclosing'])) {
            $res['enclosing'] = $list1['enclosing'];
        }

        return $res;
    }

    protected static $libZip = ['lists...'];
    protected function libZip($args)
    {
        $argLists = [];
        foreach ($args[0][2] as $arg) {
            $argLists[] = $this->coerceList($arg);
        }

        $lists = [];
        $firstList = array_shift($argLists);

        $result = [Type::T_LIST, ',', $lists];
        if (! \is_null($firstList)) {
            foreach ($firstList[2] as $key => $item) {
                $list = [Type::T_LIST, '', [$item]];

                foreach ($argLists as $arg) {
                    if (isset($arg[2][$key])) {
                        $list[2][] = $arg[2][$key];
                    } else {
                        break 2;
                    }
                }

                $lists[] = $list;
            }

            $result[2] = $lists;
        } else {
            $result['enclosing'] = 'parent';
        }

        return $result;
    }

    protected static $libTypeOf = ['value'];
    protected function libTypeOf($args)
    {
        $value = $args[0];

        return [Type::T_KEYWORD, $this->getTypeOf($value)];
    }

    /**
     * @param array|Number $value
     *
     * @return string
     */
    private function getTypeOf($value)
    {
        switch ($value[0]) {
            case Type::T_KEYWORD:
                if ($value === static::$true || $value === static::$false) {
                    return 'bool';
                }

                if ($this->coerceColor($value)) {
                    return 'color';
                }

                // fall-thru
            case Type::T_FUNCTION:
                return 'string';

            case Type::T_FUNCTION_REFERENCE:
                return 'function';

            case Type::T_LIST:
                if (isset($value[3]) && \is_array($value[3])) {
                    return 'arglist';
                }

                // fall-thru
            default:
                return $value[0];
        }
    }

    protected static $libUnit = ['number'];
    protected function libUnit($args)
    {
        $num = $this->assertNumber($args[0], 'number');

        return [Type::T_STRING, '"', [$num->unitStr()]];
    }

    protected static $libUnitless = ['number'];
    protected function libUnitless($args)
    {
        $value = $this->assertNumber($args[0], 'number');

        return $this->toBool($value->unitless());
    }

    protected static $libComparable = [
        ['number1', 'number2'],
        ['number-1', 'number-2']
    ];
    protected function libComparable($args)
    {
        list($number1, $number2) = $args;

        if (
            ! $number1 instanceof Number ||
            ! $number2 instanceof Number
        ) {
            throw $this->error('Invalid argument(s) for "comparable"');
        }

        return $this->toBool($number1->isComparableTo($number2));
    }

    protected static $libStrIndex = ['string', 'substring'];
    protected function libStrIndex($args)
    {
        $string = $this->assertString($args[0], 'string');
        $stringContent = $this->compileStringContent($string);

        $substring = $this->assertString($args[1], 'substring');
        $substringContent = $this->compileStringContent($substring);

        if (! \strlen($substringContent)) {
            $result = 0;
        } else {
            $result = Util::mbStrpos($stringContent, $substringContent);
        }

        return $result === false ? static::$null : new Number($result + 1, '');
    }

    protected static $libStrInsert = ['string', 'insert', 'index'];
    protected function libStrInsert($args)
    {
        $string = $this->assertString($args[0], 'string');
        $stringContent = $this->compileStringContent($string);

        $insert = $this->assertString($args[1], 'insert');
        $insertContent = $this->compileStringContent($insert);

        $index = $this->assertInteger($args[2], 'index');
        if ($index > 0) {
            $index = $index - 1;
        }
        if ($index < 0) {
            $index = Util::mbStrlen($stringContent) + 1 + $index;
        }

        $string[2] = [
            Util::mbSubstr($stringContent, 0, $index),
            $insertContent,
            Util::mbSubstr($stringContent, $index)
        ];

        return $string;
    }

    protected static $libStrLength = ['string'];
    protected function libStrLength($args)
    {
        $string = $this->assertString($args[0], 'string');
        $stringContent = $this->compileStringContent($string);

        return new Number(Util::mbStrlen($stringContent), '');
    }

    protected static $libStrSlice = ['string', 'start-at', 'end-at:-1'];
    protected function libStrSlice($args)
    {
        $string = $this->assertString($args[0], 'string');
        $stringContent = $this->compileStringContent($string);

        $start = $this->assertNumber($args[1], 'start-at');
        $start->assertNoUnits('start-at');
        $startInt = $this->assertInteger($start, 'start-at');
        $end = $this->assertNumber($args[2], 'end-at');
        $end->assertNoUnits('end-at');
        $endInt = $this->assertInteger($end, 'end-at');

        if ($endInt === 0) {
            return [Type::T_STRING, $string[1], []];
        }

        if ($startInt > 0) {
            $startInt--;
        }

        if ($endInt < 0) {
            $endInt = Util::mbStrlen($stringContent) + $endInt;
        } else {
            $endInt--;
        }

        if ($endInt < $startInt) {
            return [Type::T_STRING, $string[1], []];
        }

        $length = $endInt - $startInt + 1; // The end of the slice is inclusive

        $string[2] = [Util::mbSubstr($stringContent, $startInt, $length)];

        return $string;
    }

    protected static $libToLowerCase = ['string'];
    protected function libToLowerCase($args)
    {
        $string = $this->assertString($args[0], 'string');
        $stringContent = $this->compileStringContent($string);

        $string[2] = [$this->stringTransformAsciiOnly($stringContent, 'strtolower')];

        return $string;
    }

    protected static $libToUpperCase = ['string'];
    protected function libToUpperCase($args)
    {
        $string = $this->assertString($args[0], 'string');
        $stringContent = $this->compileStringContent($string);

        $string[2] = [$this->stringTransformAsciiOnly($stringContent, 'strtoupper')];

        return $string;
    }

    /**
     * Apply a filter on a string content, only on ascii chars
     * let extended chars untouched
     *
     * @param string $stringContent
     * @param callable $filter
     * @return string
     */
    protected function stringTransformAsciiOnly($stringContent, $filter)
    {
        $mblength = Util::mbStrlen($stringContent);
        if ($mblength === strlen($stringContent)) {
            return $filter($stringContent);
        }
        $filteredString = "";
        for ($i = 0; $i < $mblength; $i++) {
            $char = Util::mbSubstr($stringContent, $i, 1);
            if (strlen($char) > 1) {
                $filteredString .= $char;
            } else {
                $filteredString .= $filter($char);
            }
        }

        return $filteredString;
    }

    protected static $libFeatureExists = ['feature'];
    protected function libFeatureExists($args)
    {
        $string = $this->assertString($args[0], 'feature');
        $name = $this->compileStringContent($string);

        return $this->toBool(
            \array_key_exists($name, $this->registeredFeatures) ? $this->registeredFeatures[$name] : false
        );
    }

    protected static $libFunctionExists = ['name'];
    protected function libFunctionExists($args)
    {
        $string = $this->assertString($args[0], 'name');
        $name = $this->compileStringContent($string);

        // user defined functions
        if ($this->has(static::$namespaces['function'] . $name)) {
            return self::$true;
        }

        $name = $this->normalizeName($name);

        if (isset($this->userFunctions[$name])) {
            return self::$true;
        }

        // built-in functions
        $f = $this->getBuiltinFunction($name);

        return $this->toBool(\is_callable($f));
    }

    protected static $libGlobalVariableExists = ['name'];
    protected function libGlobalVariableExists($args)
    {
        $string = $this->assertString($args[0], 'name');
        $name = $this->compileStringContent($string);

        return $this->toBool($this->has($name, $this->rootEnv));
    }

    protected static $libMixinExists = ['name'];
    protected function libMixinExists($args)
    {
        $string = $this->assertString($args[0], 'name');
        $name = $this->compileStringContent($string);

        return $this->toBool($this->has(static::$namespaces['mixin'] . $name));
    }

    protected static $libVariableExists = ['name'];
    protected function libVariableExists($args)
    {
        $string = $this->assertString($args[0], 'name');
        $name = $this->compileStringContent($string);

        return $this->toBool($this->has($name));
    }

    protected static $libCounter = ['args...'];
    /**
     * Workaround IE7's content counter bug.
     *
     * @param array $args
     *
     * @return array
     */
    protected function libCounter($args)
    {
        $list = array_map([$this, 'compileValue'], $args[0][2]);

        return [Type::T_STRING, '', ['counter(' . implode(',', $list) . ')']];
    }

    protected static $libRandom = ['limit:null'];
    protected function libRandom($args)
    {
        if (isset($args[0]) && $args[0] !== static::$null) {
            $n = $this->assertInteger($args[0], 'limit');

            if ($n < 1) {
                throw new SassScriptException("\$limit: Must be greater than 0, was $n.");
            }

            return new Number(mt_rand(1, $n), '');
        }

        $max = mt_getrandmax();
        return new Number(mt_rand(0, $max - 1) / $max, '');
    }

    protected static $libUniqueId = [];
    protected function libUniqueId()
    {
        static $id;

        if (! isset($id)) {
            $id = PHP_INT_SIZE === 4
                ? mt_rand(0, pow(36, 5)) . str_pad(mt_rand(0, pow(36, 5)) % 10000000, 7, '0', STR_PAD_LEFT)
                : mt_rand(0, pow(36, 8));
        }

        $id += mt_rand(0, 10) + 1;

        return [Type::T_STRING, '', ['u' . str_pad(base_convert($id, 10, 36), 8, '0', STR_PAD_LEFT)]];
    }

    /**
     * @param array|Number $value
     * @param bool         $force_enclosing_display
     *
     * @return array
     */
    protected function inspectFormatValue($value, $force_enclosing_display = false)
    {
        if ($value === static::$null) {
            $value = [Type::T_KEYWORD, 'null'];
        }

        $stringValue = [$value];

        if ($value instanceof Number) {
            return [Type::T_STRING, '', $stringValue];
        }

        if ($value[0] === Type::T_LIST) {
            if (end($value[2]) === static::$null) {
                array_pop($value[2]);
                $value[2][] = [Type::T_STRING, '', ['']];
                $force_enclosing_display = true;
            }

            if (
                ! empty($value['enclosing']) &&
                ($force_enclosing_display ||
                    ($value['enclosing'] === 'bracket') ||
                    ! \count($value[2]))
            ) {
                $value['enclosing'] = 'forced_' . $value['enclosing'];
                $force_enclosing_display = true;
            }

            foreach ($value[2] as $k => $listelement) {
                $value[2][$k] = $this->inspectFormatValue($listelement, $force_enclosing_display);
            }

            $stringValue = [$value];
        }

        return [Type::T_STRING, '', $stringValue];
    }

    protected static $libInspect = ['value'];
    protected function libInspect($args)
    {
        $value = $args[0];

        return $this->inspectFormatValue($value);
    }

    /**
     * Preprocess selector args
     *
     * @param array       $arg
     * @param string|null $varname
     * @param bool        $allowParent
     *
     * @return array
     */
    protected function getSelectorArg($arg, $varname = null, $allowParent = false)
    {
        static $parser = null;

        if (\is_null($parser)) {
            $parser = $this->parserFactory(__METHOD__);
        }

        if (! $this->checkSelectorArgType($arg)) {
            $var_value = $this->compileValue($arg);
            throw SassScriptException::forArgument("$var_value is not a valid selector: it must be a string, a list of strings, or a list of lists of strings", $varname);
        }


        if ($arg[0] === Type::T_STRING) {
            $arg[1] = '';
        }
        $arg = $this->compileValue($arg);

        $parsedSelector = [];

        if ($parser->parseSelector($arg, $parsedSelector, true)) {
            $selector = $this->evalSelectors($parsedSelector);
            $gluedSelector = $this->glueFunctionSelectors($selector);

            if (! $allowParent) {
                foreach ($gluedSelector as $selector) {
                    foreach ($selector as $s) {
                        if (in_array(static::$selfSelector, $s)) {
                            throw SassScriptException::forArgument("Parent selectors aren't allowed here.", $varname);
                        }
                    }
                }
            }

            return $gluedSelector;
        }

        throw SassScriptException::forArgument("expected more input, invalid selector.", $varname);
    }

    /**
     * Check variable type for getSelectorArg() function
     * @param array $arg
     * @param int $maxDepth
     * @return bool
     */
    protected function checkSelectorArgType($arg, $maxDepth = 2)
    {
        if ($arg[0] === Type::T_LIST && $maxDepth > 0) {
            foreach ($arg[2] as $elt) {
                if (! $this->checkSelectorArgType($elt, $maxDepth - 1)) {
                    return false;
                }
            }
            return true;
        }
        if (!in_array($arg[0], [Type::T_STRING, Type::T_KEYWORD])) {
            return false;
        }
        return true;
    }

    /**
     * Postprocess selector to output in right format
     *
     * @param array $selectors
     *
     * @return array
     */
    protected function formatOutputSelector($selectors)
    {
        $selectors = $this->collapseSelectorsAsList($selectors);

        return $selectors;
    }

    protected static $libIsSuperselector = ['super', 'sub'];
    protected function libIsSuperselector($args)
    {
        list($super, $sub) = $args;

        $super = $this->getSelectorArg($super, 'super');
        $sub = $this->getSelectorArg($sub, 'sub');

        return $this->toBool($this->isSuperSelector($super, $sub));
    }

    /**
     * Test a $super selector again $sub
     *
     * @param array $super
     * @param array $sub
     *
     * @return boolean
     */
    protected function isSuperSelector($super, $sub)
    {
        // one and only one selector for each arg
        if (! $super) {
            throw $this->error('Invalid super selector for isSuperSelector()');
        }

        if (! $sub) {
            throw $this->error('Invalid sub selector for isSuperSelector()');
        }

        if (count($sub) > 1) {
            foreach ($sub as $s) {
                if (! $this->isSuperSelector($super, [$s])) {
                    return false;
                }
            }
            return true;
        }

        if (count($super) > 1) {
            foreach ($super as $s) {
                if ($this->isSuperSelector([$s], $sub)) {
                    return true;
                }
            }
            return false;
        }

        $super = reset($super);
        $sub = reset($sub);

        $i = 0;
        $nextMustMatch = false;

        foreach ($super as $node) {
            $compound = '';

            array_walk_recursive(
                $node,
                function ($value, $key) use (&$compound) {
                    $compound .= $value;
                }
            );

            if ($this->isImmediateRelationshipCombinator($compound)) {
                if ($node !== $sub[$i]) {
                    return false;
                }

                $nextMustMatch = true;
                $i++;
            } else {
                while ($i < \count($sub) && ! $this->isSuperPart($node, $sub[$i])) {
                    if ($nextMustMatch) {
                        return false;
                    }

                    $i++;
                }

                if ($i >= \count($sub)) {
                    return false;
                }

                $nextMustMatch = false;
                $i++;
            }
        }

        return true;
    }

    /**
     * Test a part of super selector again a part of sub selector
     *
     * @param array $superParts
     * @param array $subParts
     *
     * @return boolean
     */
    protected function isSuperPart($superParts, $subParts)
    {
        $i = 0;

        foreach ($superParts as $superPart) {
            while ($i < \count($subParts) && $subParts[$i] !== $superPart) {
                $i++;
            }

            if ($i >= \count($subParts)) {
                return false;
            }

            $i++;
        }

        return true;
    }

    protected static $libSelectorAppend = ['selector...'];
    protected function libSelectorAppend($args)
    {
        // get the selector... list
        $args = reset($args);
        $args = $args[2];

        if (\count($args) < 1) {
            throw $this->error('selector-append() needs at least 1 argument');
        }

        $selectors = [];
        foreach ($args as $arg) {
            $selectors[] = $this->getSelectorArg($arg, 'selector');
        }

        return $this->formatOutputSelector($this->selectorAppend($selectors));
    }

    /**
     * Append parts of the last selector in the list to the previous, recursively
     *
     * @param array $selectors
     *
     * @return array
     *
     * @throws \ScssPhp\ScssPhp\Exception\CompilerException
     */
    protected function selectorAppend($selectors)
    {
        $lastSelectors = array_pop($selectors);

        if (! $lastSelectors) {
            throw $this->error('Invalid selector list in selector-append()');
        }

        while (\count($selectors)) {
            $previousSelectors = array_pop($selectors);

            if (! $previousSelectors) {
                throw $this->error('Invalid selector list in selector-append()');
            }

            // do the trick, happening $lastSelector to $previousSelector
            $appended = [];

            foreach ($lastSelectors as $lastSelector) {
                $previous = $previousSelectors;

                foreach ($lastSelector as $lastSelectorParts) {
                    foreach ($lastSelectorParts as $lastSelectorPart) {
                        foreach ($previous as $i => $previousSelector) {
                            foreach ($previousSelector as $j => $previousSelectorParts) {
                                $previous[$i][$j][] = $lastSelectorPart;
                            }
                        }
                    }
                }

                foreach ($previous as $ps) {
                    $appended[] = $ps;
                }
            }

            $lastSelectors = $appended;
        }

        return $lastSelectors;
    }

    protected static $libSelectorExtend = [
        ['selector', 'extendee', 'extender'],
        ['selectors', 'extendee', 'extender']
    ];
    protected function libSelectorExtend($args)
    {
        list($selectors, $extendee, $extender) = $args;

        $selectors = $this->getSelectorArg($selectors, 'selector');
        $extendee  = $this->getSelectorArg($extendee, 'extendee');
        $extender  = $this->getSelectorArg($extender, 'extender');

        if (! $selectors || ! $extendee || ! $extender) {
            throw $this->error('selector-extend() invalid arguments');
        }

        $extended = $this->extendOrReplaceSelectors($selectors, $extendee, $extender);

        return $this->formatOutputSelector($extended);
    }

    protected static $libSelectorReplace = [
        ['selector', 'original', 'replacement'],
        ['selectors', 'original', 'replacement']
    ];
    protected function libSelectorReplace($args)
    {
        list($selectors, $original, $replacement) = $args;

        $selectors   = $this->getSelectorArg($selectors, 'selector');
        $original    = $this->getSelectorArg($original, 'original');
        $replacement = $this->getSelectorArg($replacement, 'replacement');

        if (! $selectors || ! $original || ! $replacement) {
            throw $this->error('selector-replace() invalid arguments');
        }

        $replaced = $this->extendOrReplaceSelectors($selectors, $original, $replacement, true);

        return $this->formatOutputSelector($replaced);
    }

    /**
     * Extend/replace in selectors
     * used by selector-extend and selector-replace that use the same logic
     *
     * @param array   $selectors
     * @param array   $extendee
     * @param array   $extender
     * @param boolean $replace
     *
     * @return array
     */
    protected function extendOrReplaceSelectors($selectors, $extendee, $extender, $replace = false)
    {
        $saveExtends = $this->extends;
        $saveExtendsMap = $this->extendsMap;

        $this->extends = [];
        $this->extendsMap = [];

        foreach ($extendee as $es) {
            if (\count($es) !== 1) {
                throw $this->error('Can\'t extend complex selector.');
            }

            // only use the first one
            $this->pushExtends(reset($es), $extender, null);
        }

        $extended = [];

        foreach ($selectors as $selector) {
            if (! $replace) {
                $extended[] = $selector;
            }

            $n = \count($extended);

            $this->matchExtends($selector, $extended);

            // if didnt match, keep the original selector if we are in a replace operation
            if ($replace && \count($extended) === $n) {
                $extended[] = $selector;
            }
        }

        $this->extends = $saveExtends;
        $this->extendsMap = $saveExtendsMap;

        return $extended;
    }

    protected static $libSelectorNest = ['selector...'];
    protected function libSelectorNest($args)
    {
        // get the selector... list
        $args = reset($args);
        $args = $args[2];

        if (\count($args) < 1) {
            throw $this->error('selector-nest() needs at least 1 argument');
        }

        $selectorsMap = [];
        foreach ($args as $arg) {
            $selectorsMap[] = $this->getSelectorArg($arg, 'selector', true);
        }

        $envs = [];

        foreach ($selectorsMap as $selectors) {
            $env = new Environment();
            $env->selectors = $selectors;

            $envs[] = $env;
        }

        $envs            = array_reverse($envs);
        $env             = $this->extractEnv($envs);
        $outputSelectors = $this->multiplySelectors($env);

        return $this->formatOutputSelector($outputSelectors);
    }

    protected static $libSelectorParse = [
        ['selector'],
        ['selectors']
    ];
    protected function libSelectorParse($args)
    {
        $selectors = reset($args);
        $selectors = $this->getSelectorArg($selectors, 'selector');

        return $this->formatOutputSelector($selectors);
    }

    protected static $libSelectorUnify = ['selectors1', 'selectors2'];
    protected function libSelectorUnify($args)
    {
        list($selectors1, $selectors2) = $args;

        $selectors1 = $this->getSelectorArg($selectors1, 'selectors1');
        $selectors2 = $this->getSelectorArg($selectors2, 'selectors2');

        if (! $selectors1 || ! $selectors2) {
            throw $this->error('selector-unify() invalid arguments');
        }

        // only consider the first compound of each
        $compound1 = reset($selectors1);
        $compound2 = reset($selectors2);

        // unify them and that's it
        $unified = $this->unifyCompoundSelectors($compound1, $compound2);

        return $this->formatOutputSelector($unified);
    }

    /**
     * The selector-unify magic as its best
     * (at least works as expected on test cases)
     *
     * @param array $compound1
     * @param array $compound2
     *
     * @return array
     */
    protected function unifyCompoundSelectors($compound1, $compound2)
    {
        if (! \count($compound1)) {
            return $compound2;
        }

        if (! \count($compound2)) {
            return $compound1;
        }

        // check that last part are compatible
        $lastPart1 = array_pop($compound1);
        $lastPart2 = array_pop($compound2);
        $last      = $this->mergeParts($lastPart1, $lastPart2);

        if (! $last) {
            return [[]];
        }

        $unifiedCompound = [$last];
        $unifiedSelectors = [$unifiedCompound];

        // do the rest
        while (\count($compound1) || \count($compound2)) {
            $part1 = end($compound1);
            $part2 = end($compound2);

            if ($part1 && ($match2 = $this->matchPartInCompound($part1, $compound2))) {
                list($compound2, $part2, $after2) = $match2;

                if ($after2) {
                    $unifiedSelectors = $this->prependSelectors($unifiedSelectors, $after2);
                }

                $c = $this->mergeParts($part1, $part2);
                $unifiedSelectors = $this->prependSelectors($unifiedSelectors, [$c]);

                $part1 = $part2 = null;

                array_pop($compound1);
            }

            if ($part2 && ($match1 = $this->matchPartInCompound($part2, $compound1))) {
                list($compound1, $part1, $after1) = $match1;

                if ($after1) {
                    $unifiedSelectors = $this->prependSelectors($unifiedSelectors, $after1);
                }

                $c = $this->mergeParts($part2, $part1);
                $unifiedSelectors = $this->prependSelectors($unifiedSelectors, [$c]);

                $part1 = $part2 = null;

                array_pop($compound2);
            }

            $new = [];

            if ($part1 && $part2) {
                array_pop($compound1);
                array_pop($compound2);

                $s   = $this->prependSelectors($unifiedSelectors, [$part2]);
                $new = array_merge($new, $this->prependSelectors($s, [$part1]));
                $s   = $this->prependSelectors($unifiedSelectors, [$part1]);
                $new = array_merge($new, $this->prependSelectors($s, [$part2]));
            } elseif ($part1) {
                array_pop($compound1);

                $new = array_merge($new, $this->prependSelectors($unifiedSelectors, [$part1]));
            } elseif ($part2) {
                array_pop($compound2);

                $new = array_merge($new, $this->prependSelectors($unifiedSelectors, [$part2]));
            }

            if ($new) {
                $unifiedSelectors = $new;
            }
        }

        return $unifiedSelectors;
    }

    /**
     * Prepend each selector from $selectors with $parts
     *
     * @param array $selectors
     * @param array $parts
     *
     * @return array
     */
    protected function prependSelectors($selectors, $parts)
    {
        $new = [];

        foreach ($selectors as $compoundSelector) {
            array_unshift($compoundSelector, $parts);

            $new[] = $compoundSelector;
        }

        return $new;
    }

    /**
     * Try to find a matching part in a compound:
     * - with same html tag name
     * - with some class or id or something in common
     *
     * @param array $part
     * @param array $compound
     *
     * @return array|false
     */
    protected function matchPartInCompound($part, $compound)
    {
        $partTag = $this->findTagName($part);
        $before  = $compound;
        $after   = [];

        // try to find a match by tag name first
        while (\count($before)) {
            $p = array_pop($before);

            if ($partTag && $partTag !== '*' && $partTag == $this->findTagName($p)) {
                return [$before, $p, $after];
            }

            $after[] = $p;
        }

        // try again matching a non empty intersection and a compatible tagname
        $before = $compound;
        $after = [];

        while (\count($before)) {
            $p = array_pop($before);

            if ($this->checkCompatibleTags($partTag, $this->findTagName($p))) {
                if (\count(array_intersect($part, $p))) {
                    return [$before, $p, $after];
                }
            }

            $after[] = $p;
        }

        return false;
    }

    /**
     * Merge two part list taking care that
     * - the html tag is coming first - if any
     * - the :something are coming last
     *
     * @param array $parts1
     * @param array $parts2
     *
     * @return array
     */
    protected function mergeParts($parts1, $parts2)
    {
        $tag1 = $this->findTagName($parts1);
        $tag2 = $this->findTagName($parts2);
        $tag  = $this->checkCompatibleTags($tag1, $tag2);

        // not compatible tags
        if ($tag === false) {
            return [];
        }

        if ($tag) {
            if ($tag1) {
                $parts1 = array_diff($parts1, [$tag1]);
            }

            if ($tag2) {
                $parts2 = array_diff($parts2, [$tag2]);
            }
        }

        $mergedParts = array_merge($parts1, $parts2);
        $mergedOrderedParts = [];

        foreach ($mergedParts as $part) {
            if (strpos($part, ':') === 0) {
                $mergedOrderedParts[] = $part;
            }
        }

        $mergedParts = array_diff($mergedParts, $mergedOrderedParts);
        $mergedParts = array_merge($mergedParts, $mergedOrderedParts);

        if ($tag) {
            array_unshift($mergedParts, $tag);
        }

        return $mergedParts;
    }

    /**
     * Check the compatibility between two tag names:
     * if both are defined they should be identical or one has to be '*'
     *
     * @param string $tag1
     * @param string $tag2
     *
     * @return array|false
     */
    protected function checkCompatibleTags($tag1, $tag2)
    {
        $tags = [$tag1, $tag2];
        $tags = array_unique($tags);
        $tags = array_filter($tags);

        if (\count($tags) > 1) {
            $tags = array_diff($tags, ['*']);
        }

        // not compatible nodes
        if (\count($tags) > 1) {
            return false;
        }

        return $tags;
    }

    /**
     * Find the html tag name in a selector parts list
     *
     * @param string[] $parts
     *
     * @return string
     */
    protected function findTagName($parts)
    {
        foreach ($parts as $part) {
            if (! preg_match('/^[\[.:#%_-]/', $part)) {
                return $part;
            }
        }

        return '';
    }

    protected static $libSimpleSelectors = ['selector'];
    protected function libSimpleSelectors($args)
    {
        $selector = reset($args);
        $selector = $this->getSelectorArg($selector, 'selector');

        // remove selectors list layer, keeping the first one
        $selector = reset($selector);

        // remove parts list layer, keeping the first part
        $part = reset($selector);

        $listParts = [];

        foreach ($part as $p) {
            $listParts[] = [Type::T_STRING, '', [$p]];
        }

        return [Type::T_LIST, ',', $listParts];
    }

    protected static $libScssphpGlob = ['pattern'];
    protected function libScssphpGlob($args)
    {
        @trigger_error(sprintf('The "scssphp-glob" function is deprecated an will be removed in ScssPhp 2.0. Register your own alternative through "%s::registerFunction', __CLASS__), E_USER_DEPRECATED);

        $this->logger->warn('The "scssphp-glob" function is deprecated an will be removed in ScssPhp 2.0.', true);

        $string = $this->assertString($args[0], 'pattern');
        $pattern = $this->compileStringContent($string);
        $matches = glob($pattern);
        $listParts = [];

        foreach ($matches as $match) {
            if (! is_file($match)) {
                continue;
            }

            $listParts[] = [Type::T_STRING, '"', [$match]];
        }

        return [Type::T_LIST, ',', $listParts];
    }
}
