<?php

/*
 * This file is part of PHP CS Fixer.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *     Dariusz Rumiński <dariusz.ruminski@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace PhpCsFixer\Console;

use PhpCsFixer\Cache\CacheManagerInterface;
use PhpCsFixer\Cache\Directory;
use PhpCsFixer\Cache\DirectoryInterface;
use PhpCsFixer\Cache\FileCacheManager;
use PhpCsFixer\Cache\FileHandler;
use PhpCsFixer\Cache\NullCacheManager;
use PhpCsFixer\Cache\Signature;
use PhpCsFixer\ConfigInterface;
use PhpCsFixer\ConfigurationException\InvalidConfigurationException;
use PhpCsFixer\Differ\DifferInterface;
use PhpCsFixer\Differ\NullDiffer;
use PhpCsFixer\Differ\SebastianBergmannDiffer;
use PhpCsFixer\Differ\UnifiedDiffer;
use PhpCsFixer\Finder;
use PhpCsFixer\Fixer\DeprecatedFixerInterface;
use PhpCsFixer\Fixer\FixerInterface;
use PhpCsFixer\FixerFactory;
use PhpCsFixer\Linter\Linter;
use PhpCsFixer\Linter\LinterInterface;
use PhpCsFixer\Report\ReporterFactory;
use PhpCsFixer\Report\ReporterInterface;
use PhpCsFixer\RuleSet;
use PhpCsFixer\StdinFileInfo;
use PhpCsFixer\ToolInfoInterface;
use PhpCsFixer\Utils;
use PhpCsFixer\WhitespacesFixerConfig;
use PhpCsFixer\WordMatcher;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder as SymfonyFinder;

/**
 * The resolver that resolves configuration to use by command line options and config.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Katsuhiro Ogawa <ko.fivestar@gmail.com>
 * @author Dariusz Rumiński <dariusz.ruminski@gmail.com>
 *
 * @internal
 */
final class ConfigurationResolver
{
    const PATH_MODE_OVERRIDE = 'override';
    const PATH_MODE_INTERSECTION = 'intersection';

    /**
     * @var null|bool
     */
    private $allowRisky;

    /**
     * @var null|ConfigInterface
     */
    private $config;

    /**
     * @var null|string
     */
    private $configFile;

    /**
     * @var string
     */
    private $cwd;

    /**
     * @var ConfigInterface
     */
    private $defaultConfig;

    /**
     * @var null|ReporterInterface
     */
    private $reporter;

    /**
     * @var null|bool
     */
    private $isStdIn;

    /**
     * @var null|bool
     */
    private $isDryRun;

    /**
     * @var null|FixerInterface[]
     */
    private $fixers;

    /**
     * @var null|bool
     */
    private $configFinderIsOverridden;

    /**
     * @var ToolInfoInterface
     */
    private $toolInfo;

    /**
     * @var array
     */
    private $options = [
        'allow-risky' => null,
        'cache-file' => null,
        'config' => null,
        'diff' => null,
        'diff-format' => null,
        'dry-run' => null,
        'format' => null,
        'path' => [],
        'path-mode' => self::PATH_MODE_OVERRIDE,
        'rules' => null,
        'show-progress' => null,
        'stop-on-violation' => null,
        'using-cache' => null,
        'verbosity' => null,
    ];

    private $cacheFile;
    private $cacheManager;
    private $differ;
    private $directory;
    private $finder;
    private $format;
    private $linter;
    private $path;
    private $progress;
    private $ruleSet;
    private $usingCache;

    /**
     * @var FixerFactory
     */
    private $fixerFactory;

    /**
     * @param string $cwd
     */
    public function __construct(
        ConfigInterface $config,
        array $options,
        $cwd,
        ToolInfoInterface $toolInfo
    ) {
        $this->cwd = $cwd;
        $this->defaultConfig = $config;
        $this->toolInfo = $toolInfo;

        foreach ($options as $name => $value) {
            $this->setOption($name, $value);
        }
    }

    /**
     * @return null|string
     */
    public function getCacheFile()
    {
        if (!$this->getUsingCache()) {
            return null;
        }

        if (null === $this->cacheFile) {
            if (null === $this->options['cache-file']) {
                $this->cacheFile = $this->getConfig()->getCacheFile();
            } else {
                $this->cacheFile = $this->options['cache-file'];
            }
        }

        return $this->cacheFile;
    }

    /**
     * @return CacheManagerInterface
     */
    public function getCacheManager()
    {
        if (null === $this->cacheManager) {
            if ($this->getUsingCache() && ($this->toolInfo->isInstalledAsPhar() || $this->toolInfo->isInstalledByComposer())) {
                $this->cacheManager = new FileCacheManager(
                    new FileHandler($this->getCacheFile()),
                    new Signature(
                        PHP_VERSION,
                        $this->toolInfo->getVersion(),
                        $this->getConfig()->getIndent(),
                        $this->getConfig()->getLineEnding(),
                        $this->getRules()
                    ),
                    $this->isDryRun(),
                    $this->getDirectory()
                );
            } else {
                $this->cacheManager = new NullCacheManager();
            }
        }

        return $this->cacheManager;
    }

    /**
     * @return ConfigInterface
     */
    public function getConfig()
    {
        if (null === $this->config) {
            foreach ($this->computeConfigFiles() as $configFile) {
                if (!file_exists($configFile)) {
                    continue;
                }

                $config = self::separatedContextLessInclude($configFile);

                // verify that the config has an instance of Config
                if (!$config instanceof ConfigInterface) {
                    throw new InvalidConfigurationException(sprintf('The config file: "%s" does not return a "PhpCsFixer\ConfigInterface" instance. Got: "%s".', $configFile, \is_object($config) ? \get_class($config) : \gettype($config)));
                }

                $this->config = $config;
                $this->configFile = $configFile;

                break;
            }

            if (null === $this->config) {
                $this->config = $this->defaultConfig;
            }
        }

        return $this->config;
    }

    /**
     * @return null|string
     */
    public function getConfigFile()
    {
        if (null === $this->configFile) {
            $this->getConfig();
        }

        return $this->configFile;
    }

    /**
     * @return DifferInterface
     */
    public function getDiffer()
    {
        if (null === $this->differ) {
            $mapper = [
                'null' => static function () { return new NullDiffer(); },
                'sbd' => static function () { return new SebastianBergmannDiffer(); },
                'udiff' => static function () { return new UnifiedDiffer(); },
            ];

            if ($this->options['diff-format']) {
                $option = $this->options['diff-format'];
                if (!isset($mapper[$option])) {
                    throw new InvalidConfigurationException(sprintf(
                        '"diff-format" must be any of "%s", got "%s".',
                        implode('", "', array_keys($mapper)),
                        $option
                    ));
                }
            } else {
                $default = 'sbd'; // @TODO: 3.0 change to udiff as default

                if (getenv('PHP_CS_FIXER_FUTURE_MODE')) {
                    $default = 'udiff';
                }

                $option = $this->options['diff'] ? $default : 'null';
            }

            $this->differ = $mapper[$option]();
        }

        return $this->differ;
    }

    /**
     * @return DirectoryInterface
     */
    public function getDirectory()
    {
        if (null === $this->directory) {
            $path = $this->getCacheFile();
            if (null === $path) {
                $absolutePath = $this->cwd;
            } else {
                $filesystem = new Filesystem();

                $absolutePath = $filesystem->isAbsolutePath($path)
                    ? $path
                    : $this->cwd.\DIRECTORY_SEPARATOR.$path;
            }

            $this->directory = new Directory(\dirname($absolutePath));
        }

        return $this->directory;
    }

    /**
     * @return FixerInterface[] An array of FixerInterface
     */
    public function getFixers()
    {
        if (null === $this->fixers) {
            $this->fixers = $this->createFixerFactory()
                ->useRuleSet($this->getRuleSet())
                ->setWhitespacesConfig(new WhitespacesFixerConfig($this->config->getIndent(), $this->config->getLineEnding()))
                ->getFixers()
            ;

            if (false === $this->getRiskyAllowed()) {
                $riskyFixers = array_map(
                    static function (FixerInterface $fixer) {
                        return $fixer->getName();
                    },
                    array_filter(
                        $this->fixers,
                        static function (FixerInterface $fixer) {
                            return $fixer->isRisky();
                        }
                    )
                );

                if (\count($riskyFixers)) {
                    throw new InvalidConfigurationException(sprintf('The rules contain risky fixers (%s), but they are not allowed to run. Perhaps you forget to use --allow-risky=yes option?', implode(', ', $riskyFixers)));
                }
            }
        }

        return $this->fixers;
    }

    /**
     * @return LinterInterface
     */
    public function getLinter()
    {
        if (null === $this->linter) {
            $this->linter = new Linter($this->getConfig()->getPhpExecutable());
        }

        return $this->linter;
    }

    /**
     * Returns path.
     *
     * @return string[]
     */
    public function getPath()
    {
        if (null === $this->path) {
            $filesystem = new Filesystem();
            $cwd = $this->cwd;

            if (1 === \count($this->options['path']) && '-' === $this->options['path'][0]) {
                $this->path = $this->options['path'];
            } else {
                $this->path = array_map(
                    static function ($path) use ($cwd, $filesystem) {
                        $absolutePath = $filesystem->isAbsolutePath($path)
                            ? $path
                            : $cwd.\DIRECTORY_SEPARATOR.$path;

                        if (!file_exists($absolutePath)) {
                            throw new InvalidConfigurationException(sprintf(
                                'The path "%s" is not readable.',
                                $path
                            ));
                        }

                        return $absolutePath;
                    },
                    $this->options['path']
                );
            }
        }

        return $this->path;
    }

    /**
     * @throws InvalidConfigurationException
     *
     * @return string
     */
    public function getProgress()
    {
        if (null === $this->progress) {
            if (OutputInterface::VERBOSITY_VERBOSE <= $this->options['verbosity'] && 'txt' === $this->getFormat()) {
                $progressType = $this->options['show-progress'];
                $progressTypes = ['none', 'run-in', 'estimating', 'estimating-max', 'dots'];

                if (null === $progressType) {
                    $default = 'run-in';

                    if (getenv('PHP_CS_FIXER_FUTURE_MODE')) {
                        $default = 'dots';
                    }

                    $progressType = $this->getConfig()->getHideProgress() ? 'none' : $default;
                } elseif (!\in_array($progressType, $progressTypes, true)) {
                    throw new InvalidConfigurationException(sprintf(
                        'The progress type "%s" is not defined, supported are "%s".',
                        $progressType,
                        implode('", "', $progressTypes)
                    ));
                } elseif (\in_array($progressType, ['estimating', 'estimating-max', 'run-in'], true)) {
                    $message = 'Passing `estimating`, `estimating-max` or `run-in` is deprecated and will not be supported in 3.0, use `none` or `dots` instead.';

                    if (getenv('PHP_CS_FIXER_FUTURE_MODE')) {
                        throw new \InvalidArgumentException("{$message} This check was performed as `PHP_CS_FIXER_FUTURE_MODE` env var is set.");
                    }

                    @trigger_error($message, E_USER_DEPRECATED);
                }

                $this->progress = $progressType;
            } else {
                $this->progress = 'none';
            }
        }

        return $this->progress;
    }

    /**
     * @return ReporterInterface
     */
    public function getReporter()
    {
        if (null === $this->reporter) {
            $reporterFactory = ReporterFactory::create();
            $reporterFactory->registerBuiltInReporters();

            $format = $this->getFormat();

            try {
                $this->reporter = $reporterFactory->getReporter($format);
            } catch (\UnexpectedValueException $e) {
                $formats = $reporterFactory->getFormats();
                sort($formats);

                throw new InvalidConfigurationException(sprintf('The format "%s" is not defined, supported are "%s".', $format, implode('", "', $formats)));
            }
        }

        return $this->reporter;
    }

    /**
     * @return bool
     */
    public function getRiskyAllowed()
    {
        if (null === $this->allowRisky) {
            if (null === $this->options['allow-risky']) {
                $this->allowRisky = $this->getConfig()->getRiskyAllowed();
            } else {
                $this->allowRisky = $this->resolveOptionBooleanValue('allow-risky');
            }
        }

        return $this->allowRisky;
    }

    /**
     * Returns rules.
     *
     * @return array
     */
    public function getRules()
    {
        return $this->getRuleSet()->getRules();
    }

    /**
     * @return bool
     */
    public function getUsingCache()
    {
        if (null === $this->usingCache) {
            if (null === $this->options['using-cache']) {
                $this->usingCache = $this->getConfig()->getUsingCache();
            } else {
                $this->usingCache = $this->resolveOptionBooleanValue('using-cache');
            }
        }

        return $this->usingCache;
    }

    public function getFinder()
    {
        if (null === $this->finder) {
            $this->finder = $this->resolveFinder();
        }

        return $this->finder;
    }

    /**
     * Returns dry-run flag.
     *
     * @return bool
     */
    public function isDryRun()
    {
        if (null === $this->isDryRun) {
            if ($this->isStdIn()) {
                // Can't write to STDIN
                $this->isDryRun = true;
            } else {
                $this->isDryRun = $this->options['dry-run'];
            }
        }

        return $this->isDryRun;
    }

    public function shouldStopOnViolation()
    {
        return $this->options['stop-on-violation'];
    }

    /**
     * @return bool
     */
    public function configFinderIsOverridden()
    {
        if (null === $this->configFinderIsOverridden) {
            $this->resolveFinder();
        }

        return $this->configFinderIsOverridden;
    }

    /**
     * Compute file candidates for config file.
     *
     * @return string[]
     */
    private function computeConfigFiles()
    {
        $configFile = $this->options['config'];

        if (null !== $configFile) {
            if (false === file_exists($configFile) || false === is_readable($configFile)) {
                throw new InvalidConfigurationException(sprintf('Cannot read config file "%s".', $configFile));
            }

            return [$configFile];
        }

        $path = $this->getPath();

        if ($this->isStdIn() || 0 === \count($path)) {
            $configDir = $this->cwd;
        } elseif (1 < \count($path)) {
            throw new InvalidConfigurationException('For multiple paths config parameter is required.');
        } elseif (is_file($path[0]) && $dirName = pathinfo($path[0], PATHINFO_DIRNAME)) {
            $configDir = $dirName;
        } else {
            $configDir = $path[0];
        }

        $candidates = [
            $configDir.\DIRECTORY_SEPARATOR.'.php_cs',
            $configDir.\DIRECTORY_SEPARATOR.'.php_cs.dist',
        ];

        if ($configDir !== $this->cwd) {
            $candidates[] = $this->cwd.\DIRECTORY_SEPARATOR.'.php_cs';
            $candidates[] = $this->cwd.\DIRECTORY_SEPARATOR.'.php_cs.dist';
        }

        return $candidates;
    }

    /**
     * @return FixerFactory
     */
    private function createFixerFactory()
    {
        if (null === $this->fixerFactory) {
            $fixerFactory = new FixerFactory();
            $fixerFactory->registerBuiltInFixers();
            $fixerFactory->registerCustomFixers($this->getConfig()->getCustomFixers());

            $this->fixerFactory = $fixerFactory;
        }

        return $this->fixerFactory;
    }

    /**
     * @return string
     */
    private function getFormat()
    {
        if (null === $this->format) {
            $this->format = null === $this->options['format']
                ? $this->getConfig()->getFormat()
                : $this->options['format'];
        }

        return $this->format;
    }

    private function getRuleSet()
    {
        if (null === $this->ruleSet) {
            $rules = $this->parseRules();
            $this->validateRules($rules);

            $this->ruleSet = new RuleSet($rules);
        }

        return $this->ruleSet;
    }

    /**
     * @return bool
     */
    private function isStdIn()
    {
        if (null === $this->isStdIn) {
            $this->isStdIn = 1 === \count($this->options['path']) && '-' === $this->options['path'][0];
        }

        return $this->isStdIn;
    }

    /**
     * @param iterable $iterable
     *
     * @return \Traversable
     */
    private function iterableToTraversable($iterable)
    {
        return \is_array($iterable) ? new \ArrayIterator($iterable) : $iterable;
    }

    /**
     * Compute rules.
     *
     * @return array
     */
    private function parseRules()
    {
        if (null === $this->options['rules']) {
            return $this->getConfig()->getRules();
        }

        $rules = trim($this->options['rules']);
        if ('' === $rules) {
            throw new InvalidConfigurationException('Empty rules value is not allowed.');
        }

        if ('{' === $rules[0]) {
            $rules = json_decode($rules, true);
            if (JSON_ERROR_NONE !== json_last_error()) {
                throw new InvalidConfigurationException(sprintf('Invalid JSON rules input: "%s".', json_last_error_msg()));
            }

            return $rules;
        }

        $rules = [];

        foreach (explode(',', $this->options['rules']) as $rule) {
            $rule = trim($rule);
            if ('' === $rule) {
                throw new InvalidConfigurationException('Empty rule name is not allowed.');
            }

            if ('-' === $rule[0]) {
                $rules[substr($rule, 1)] = false;
            } else {
                $rules[$rule] = true;
            }
        }

        return $rules;
    }

    /**
     * @throws InvalidConfigurationException
     */
    private function validateRules(array $rules)
    {
        /**
         * Create a ruleset that contains all configured rules, even when they originally have been disabled.
         *
         * @see RuleSet::resolveSet()
         */
        $ruleSet = [];
        foreach ($rules as $key => $value) {
            if (\is_int($key)) {
                throw new InvalidConfigurationException(sprintf('Missing value for "%s" rule/set.', $value));
            }

            $ruleSet[$key] = true;
        }
        $ruleSet = new RuleSet($ruleSet);

        /** @var string[] $configuredFixers */
        $configuredFixers = array_keys($ruleSet->getRules());

        $fixers = $this->createFixerFactory()->getFixers();

        /** @var string[] $availableFixers */
        $availableFixers = array_map(static function (FixerInterface $fixer) {
            return $fixer->getName();
        }, $fixers);

        $unknownFixers = array_diff(
            $configuredFixers,
            $availableFixers
        );

        if (\count($unknownFixers)) {
            $matcher = new WordMatcher($availableFixers);

            $message = 'The rules contain unknown fixers: ';
            foreach ($unknownFixers as $unknownFixer) {
                $alternative = $matcher->match($unknownFixer);
                $message .= sprintf(
                    '"%s"%s, ',
                    $unknownFixer,
                    null === $alternative ? '' : ' (did you mean "'.$alternative.'"?)'
                );
            }

            throw new InvalidConfigurationException(substr($message, 0, -2).'.');
        }

        foreach ($fixers as $fixer) {
            $fixerName = $fixer->getName();
            if (isset($rules[$fixerName]) && $fixer instanceof DeprecatedFixerInterface) {
                $successors = $fixer->getSuccessorsNames();
                $messageEnd = [] === $successors
                    ? sprintf(' and will be removed in version %d.0.', Application::getMajorVersion())
                    : sprintf('. Use %s instead.', str_replace('`', '"', Utils::naturalLanguageJoinWithBackticks($successors)));

                $message = "Rule \"{$fixerName}\" is deprecated{$messageEnd}";

                if (getenv('PHP_CS_FIXER_FUTURE_MODE')) {
                    throw new \RuntimeException("{$message} This check was performed as `PHP_CS_FIXER_FUTURE_MODE` env var is set.");
                }

                @trigger_error($message, E_USER_DEPRECATED);
            }
        }
    }

    /**
     * Apply path on config instance.
     */
    private function resolveFinder()
    {
        $this->configFinderIsOverridden = false;

        if ($this->isStdIn()) {
            return new \ArrayIterator([new StdinFileInfo()]);
        }

        $modes = [self::PATH_MODE_OVERRIDE, self::PATH_MODE_INTERSECTION];

        if (!\in_array(
            $this->options['path-mode'],
            $modes,
            true
        )) {
            throw new InvalidConfigurationException(sprintf(
                'The path-mode "%s" is not defined, supported are "%s".',
                $this->options['path-mode'],
                implode('", "', $modes)
            ));
        }

        $isIntersectionPathMode = self::PATH_MODE_INTERSECTION === $this->options['path-mode'];

        $paths = array_filter(array_map(
            static function ($path) {
                return realpath($path);
            },
            $this->getPath()
        ));

        if (!\count($paths)) {
            if ($isIntersectionPathMode) {
                return new \ArrayIterator([]);
            }

            return $this->iterableToTraversable($this->getConfig()->getFinder());
        }

        $pathsByType = [
            'file' => [],
            'dir' => [],
        ];

        foreach ($paths as $path) {
            if (is_file($path)) {
                $pathsByType['file'][] = $path;
            } else {
                $pathsByType['dir'][] = $path.\DIRECTORY_SEPARATOR;
            }
        }

        $nestedFinder = null;
        $currentFinder = $this->iterableToTraversable($this->getConfig()->getFinder());

        try {
            $nestedFinder = $currentFinder instanceof \IteratorAggregate ? $currentFinder->getIterator() : $currentFinder;
        } catch (\Exception $e) {
        }

        if ($isIntersectionPathMode) {
            if (null === $nestedFinder) {
                throw new InvalidConfigurationException(
                    'Cannot create intersection with not-fully defined Finder in configuration file.'
                );
            }

            return new \CallbackFilterIterator(
                new \IteratorIterator($nestedFinder),
                static function (\SplFileInfo $current) use ($pathsByType) {
                    $currentRealPath = $current->getRealPath();

                    if (\in_array($currentRealPath, $pathsByType['file'], true)) {
                        return true;
                    }

                    foreach ($pathsByType['dir'] as $path) {
                        if (0 === strpos($currentRealPath, $path)) {
                            return true;
                        }
                    }

                    return false;
                }
            );
        }

        if (null !== $this->getConfigFile() && null !== $nestedFinder) {
            $this->configFinderIsOverridden = true;
        }

        if ($currentFinder instanceof SymfonyFinder && null === $nestedFinder) {
            // finder from configuration Symfony finder and it is not fully defined, we may fulfill it
            return $currentFinder->in($pathsByType['dir'])->append($pathsByType['file']);
        }

        return Finder::create()->in($pathsByType['dir'])->append($pathsByType['file']);
    }

    /**
     * Set option that will be resolved.
     *
     * @param string $name
     * @param mixed  $value
     */
    private function setOption($name, $value)
    {
        if (!\array_key_exists($name, $this->options)) {
            throw new InvalidConfigurationException(sprintf('Unknown option name: "%s".', $name));
        }

        $this->options[$name] = $value;
    }

    /**
     * @param string $optionName
     *
     * @return bool
     */
    private function resolveOptionBooleanValue($optionName)
    {
        $value = $this->options[$optionName];
        if (\is_bool($value)) {
            return $value;
        }

        if (!\is_string($value)) {
            throw new InvalidConfigurationException(sprintf('Expected boolean or string value for option "%s".', $optionName));
        }

        if ('yes' === $value) {
            return true;
        }

        if ('no' === $value) {
            return false;
        }

        $message = sprintf('Expected "yes" or "no" for option "%s", other values are deprecated and support will be removed in 3.0. Got "%s", this implicitly set the option to "false".', $optionName, $value);

        if (getenv('PHP_CS_FIXER_FUTURE_MODE')) {
            throw new InvalidConfigurationException("{$message} This check was performed as `PHP_CS_FIXER_FUTURE_MODE` env var is set.");
        }

        @trigger_error($message, E_USER_DEPRECATED);

        return false;
    }

    private static function separatedContextLessInclude($path)
    {
        return include $path;
    }
}
