<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Routing;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Routing\RedirectableUrlMatcher;
use Symfony\Component\Config\ConfigCacheFactory;
use Symfony\Component\Config\ConfigCacheFactoryInterface;
use Symfony\Component\Config\ConfigCacheInterface;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\CompiledUrlGenerator;
use Symfony\Component\Routing\Generator\ConfigurableRequirementsInterface;
use Symfony\Component\Routing\Generator\Dumper\CompiledUrlGeneratorDumper;
use Symfony\Component\Routing\Generator\Dumper\GeneratorDumperInterface;
use Symfony\Component\Routing\Generator\Dumper\PhpGeneratorDumper;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Matcher\CompiledUrlMatcher;
use Symfony\Component\Routing\Matcher\Dumper\CompiledUrlMatcherDumper;
use Symfony\Component\Routing\Matcher\Dumper\MatcherDumperInterface;
use Symfony\Component\Routing\Matcher\Dumper\PhpMatcherDumper;
use Symfony\Component\Routing\Matcher\RequestMatcherInterface;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;

/**
 * The Router class is an example of the integration of all pieces of the
 * routing system for easier use.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class Router implements RouterInterface, RequestMatcherInterface
{
    /**
     * @var UrlMatcherInterface|null
     */
    protected $matcher;

    /**
     * @var UrlGeneratorInterface|null
     */
    protected $generator;

    /**
     * @var RequestContext
     */
    protected $context;

    /**
     * @var LoaderInterface
     */
    protected $loader;

    /**
     * @var RouteCollection|null
     */
    protected $collection;

    /**
     * @var mixed
     */
    protected $resource;

    /**
     * @var array
     */
    protected $options = [];

    /**
     * @var LoggerInterface|null
     */
    protected $logger;

    /**
     * @var string|null
     */
    protected $defaultLocale;

    /**
     * @var ConfigCacheFactoryInterface|null
     */
    private $configCacheFactory;

    /**
     * @var ExpressionFunctionProviderInterface[]
     */
    private $expressionLanguageProviders = [];

    private static $cache = [];

    /**
     * @param mixed $resource The main resource to load
     */
    public function __construct(LoaderInterface $loader, $resource, array $options = [], RequestContext $context = null, LoggerInterface $logger = null, string $defaultLocale = null)
    {
        $this->loader = $loader;
        $this->resource = $resource;
        $this->logger = $logger;
        $this->context = $context ?? new RequestContext();
        $this->setOptions($options);
        $this->defaultLocale = $defaultLocale;
    }

    /**
     * Sets options.
     *
     * Available options:
     *
     *   * cache_dir:              The cache directory (or null to disable caching)
     *   * debug:                  Whether to enable debugging or not (false by default)
     *   * generator_class:        The name of a UrlGeneratorInterface implementation
     *   * generator_dumper_class: The name of a GeneratorDumperInterface implementation
     *   * matcher_class:          The name of a UrlMatcherInterface implementation
     *   * matcher_dumper_class:   The name of a MatcherDumperInterface implementation
     *   * resource_type:          Type hint for the main resource (optional)
     *   * strict_requirements:    Configure strict requirement checking for generators
     *                             implementing ConfigurableRequirementsInterface (default is true)
     *
     * @throws \InvalidArgumentException When unsupported option is provided
     */
    public function setOptions(array $options)
    {
        $this->options = [
            'cache_dir' => null,
            'debug' => false,
            'generator_class' => CompiledUrlGenerator::class,
            'generator_base_class' => UrlGenerator::class, // deprecated
            'generator_dumper_class' => CompiledUrlGeneratorDumper::class,
            'generator_cache_class' => 'UrlGenerator', // deprecated
            'matcher_class' => CompiledUrlMatcher::class,
            'matcher_base_class' => UrlMatcher::class, // deprecated
            'matcher_dumper_class' => CompiledUrlMatcherDumper::class,
            'matcher_cache_class' => 'UrlMatcher', // deprecated
            'resource_type' => null,
            'strict_requirements' => true,
        ];

        // check option names and live merge, if errors are encountered Exception will be thrown
        $invalid = [];
        foreach ($options as $key => $value) {
            $this->checkDeprecatedOption($key);
            if (\array_key_exists($key, $this->options)) {
                $this->options[$key] = $value;
            } else {
                $invalid[] = $key;
            }
        }

        if ($invalid) {
            throw new \InvalidArgumentException(sprintf('The Router does not support the following options: "%s".', implode('", "', $invalid)));
        }
    }

    /**
     * Sets an option.
     *
     * @param string $key   The key
     * @param mixed  $value The value
     *
     * @throws \InvalidArgumentException
     */
    public function setOption($key, $value)
    {
        if (!\array_key_exists($key, $this->options)) {
            throw new \InvalidArgumentException(sprintf('The Router does not support the "%s" option.', $key));
        }

        $this->checkDeprecatedOption($key);

        $this->options[$key] = $value;
    }

    /**
     * Gets an option value.
     *
     * @param string $key The key
     *
     * @return mixed The value
     *
     * @throws \InvalidArgumentException
     */
    public function getOption($key)
    {
        if (!\array_key_exists($key, $this->options)) {
            throw new \InvalidArgumentException(sprintf('The Router does not support the "%s" option.', $key));
        }

        $this->checkDeprecatedOption($key);

        return $this->options[$key];
    }

    /**
     * {@inheritdoc}
     */
    public function getRouteCollection()
    {
        if (null === $this->collection) {
            $this->collection = $this->loader->load($this->resource, $this->options['resource_type']);
        }

        return $this->collection;
    }

    /**
     * {@inheritdoc}
     */
    public function setContext(RequestContext $context)
    {
        $this->context = $context;

        if (null !== $this->matcher) {
            $this->getMatcher()->setContext($context);
        }
        if (null !== $this->generator) {
            $this->getGenerator()->setContext($context);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * Sets the ConfigCache factory to use.
     */
    public function setConfigCacheFactory(ConfigCacheFactoryInterface $configCacheFactory)
    {
        $this->configCacheFactory = $configCacheFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function generate($name, $parameters = [], $referenceType = self::ABSOLUTE_PATH)
    {
        return $this->getGenerator()->generate($name, $parameters, $referenceType);
    }

    /**
     * {@inheritdoc}
     */
    public function match($pathinfo)
    {
        return $this->getMatcher()->match($pathinfo);
    }

    /**
     * {@inheritdoc}
     */
    public function matchRequest(Request $request)
    {
        $matcher = $this->getMatcher();
        if (!$matcher instanceof RequestMatcherInterface) {
            // fallback to the default UrlMatcherInterface
            return $matcher->match($request->getPathInfo());
        }

        return $matcher->matchRequest($request);
    }

    /**
     * Gets the UrlMatcher or RequestMatcher instance associated with this Router.
     *
     * @return UrlMatcherInterface|RequestMatcherInterface
     */
    public function getMatcher()
    {
        if (null !== $this->matcher) {
            return $this->matcher;
        }

        $compiled = is_a($this->options['matcher_class'], CompiledUrlMatcher::class, true) && (UrlMatcher::class === $this->options['matcher_base_class'] || RedirectableUrlMatcher::class === $this->options['matcher_base_class']) && is_a($this->options['matcher_dumper_class'], CompiledUrlMatcherDumper::class, true);

        if (null === $this->options['cache_dir'] || null === $this->options['matcher_cache_class']) {
            $routes = $this->getRouteCollection();
            if ($compiled) {
                $routes = (new CompiledUrlMatcherDumper($routes))->getCompiledRoutes();
            }
            $this->matcher = new $this->options['matcher_class']($routes, $this->context);
            if (method_exists($this->matcher, 'addExpressionLanguageProvider')) {
                foreach ($this->expressionLanguageProviders as $provider) {
                    $this->matcher->addExpressionLanguageProvider($provider);
                }
            }

            return $this->matcher;
        }

        $cache = $this->getConfigCacheFactory()->cache($this->options['cache_dir'].'/'.$this->options['matcher_cache_class'].'.php',
            function (ConfigCacheInterface $cache) {
                $dumper = $this->getMatcherDumperInstance();
                if (method_exists($dumper, 'addExpressionLanguageProvider')) {
                    foreach ($this->expressionLanguageProviders as $provider) {
                        $dumper->addExpressionLanguageProvider($provider);
                    }
                }

                $options = [
                    'class' => $this->options['matcher_cache_class'],
                    'base_class' => $this->options['matcher_base_class'],
                ];

                $cache->write($dumper->dump($options), $this->getRouteCollection()->getResources());
            }
        );

        if ($compiled) {
            return $this->matcher = new $this->options['matcher_class'](self::getCompiledRoutes($cache->getPath()), $this->context);
        }

        if (!class_exists($this->options['matcher_cache_class'], false)) {
            require_once $cache->getPath();
        }

        return $this->matcher = new $this->options['matcher_cache_class']($this->context);
    }

    /**
     * Gets the UrlGenerator instance associated with this Router.
     *
     * @return UrlGeneratorInterface A UrlGeneratorInterface instance
     */
    public function getGenerator()
    {
        if (null !== $this->generator) {
            return $this->generator;
        }

        $compiled = is_a($this->options['generator_class'], CompiledUrlGenerator::class, true) && UrlGenerator::class === $this->options['generator_base_class'] && is_a($this->options['generator_dumper_class'], CompiledUrlGeneratorDumper::class, true);

        if (null === $this->options['cache_dir'] || null === $this->options['generator_cache_class']) {
            $routes = $this->getRouteCollection();
            if ($compiled) {
                $routes = (new CompiledUrlGeneratorDumper($routes))->getCompiledRoutes();
            }
            $this->generator = new $this->options['generator_class']($routes, $this->context, $this->logger, $this->defaultLocale);
        } else {
            $cache = $this->getConfigCacheFactory()->cache($this->options['cache_dir'].'/'.$this->options['generator_cache_class'].'.php',
                function (ConfigCacheInterface $cache) {
                    $dumper = $this->getGeneratorDumperInstance();

                    $options = [
                        'class' => $this->options['generator_cache_class'],
                        'base_class' => $this->options['generator_base_class'],
                    ];

                    $cache->write($dumper->dump($options), $this->getRouteCollection()->getResources());
                }
            );

            if ($compiled) {
                $this->generator = new $this->options['generator_class'](self::getCompiledRoutes($cache->getPath()), $this->context, $this->logger, $this->defaultLocale);
            } else {
                if (!class_exists($this->options['generator_cache_class'], false)) {
                    require_once $cache->getPath();
                }

                $this->generator = new $this->options['generator_cache_class']($this->context, $this->logger, $this->defaultLocale);
            }
        }

        if ($this->generator instanceof ConfigurableRequirementsInterface) {
            $this->generator->setStrictRequirements($this->options['strict_requirements']);
        }

        return $this->generator;
    }

    public function addExpressionLanguageProvider(ExpressionFunctionProviderInterface $provider)
    {
        $this->expressionLanguageProviders[] = $provider;
    }

    /**
     * @return GeneratorDumperInterface
     */
    protected function getGeneratorDumperInstance()
    {
        // For BC, fallback to PhpGeneratorDumper (which is the old default value) if the old UrlGenerator is used with the new default CompiledUrlGeneratorDumper
        if (!is_a($this->options['generator_class'], CompiledUrlGenerator::class, true) && is_a($this->options['generator_dumper_class'], CompiledUrlGeneratorDumper::class, true)) {
            return new PhpGeneratorDumper($this->getRouteCollection());
        }

        return new $this->options['generator_dumper_class']($this->getRouteCollection());
    }

    /**
     * @return MatcherDumperInterface
     */
    protected function getMatcherDumperInstance()
    {
        // For BC, fallback to PhpMatcherDumper (which is the old default value) if the old UrlMatcher is used with the new default CompiledUrlMatcherDumper
        if (!is_a($this->options['matcher_class'], CompiledUrlMatcher::class, true) && is_a($this->options['matcher_dumper_class'], CompiledUrlMatcherDumper::class, true)) {
            return new PhpMatcherDumper($this->getRouteCollection());
        }

        return new $this->options['matcher_dumper_class']($this->getRouteCollection());
    }

    /**
     * Provides the ConfigCache factory implementation, falling back to a
     * default implementation if necessary.
     */
    private function getConfigCacheFactory(): ConfigCacheFactoryInterface
    {
        if (null === $this->configCacheFactory) {
            $this->configCacheFactory = new ConfigCacheFactory($this->options['debug']);
        }

        return $this->configCacheFactory;
    }

    private function checkDeprecatedOption(string $key)
    {
        switch ($key) {
            case 'generator_base_class':
            case 'generator_cache_class':
            case 'matcher_base_class':
            case 'matcher_cache_class':
                @trigger_error(sprintf('Option "%s" given to router %s is deprecated since Symfony 4.3.', $key, static::class), \E_USER_DEPRECATED);
        }
    }

    private static function getCompiledRoutes(string $path): array
    {
        if ([] === self::$cache && \function_exists('opcache_invalidate') && filter_var(ini_get('opcache.enable'), \FILTER_VALIDATE_BOOLEAN) && (!\in_array(\PHP_SAPI, ['cli', 'phpdbg'], true) || filter_var(ini_get('opcache.enable_cli'), \FILTER_VALIDATE_BOOLEAN))) {
            self::$cache = null;
        }

        if (null === self::$cache) {
            return require $path;
        }

        if (isset(self::$cache[$path])) {
            return self::$cache[$path];
        }

        return self::$cache[$path] = require $path;
    }
}
