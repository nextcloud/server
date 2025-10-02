<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Routing\Loader;

use Doctrine\Common\Annotations\Reader;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Loader\LoaderResolverInterface;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\Routing\Attribute\Route as RouteAnnotation;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * AttributeClassLoader loads routing information from a PHP class and its methods.
 *
 * You need to define an implementation for the configureRoute() method. Most of the
 * time, this method should define some PHP callable to be called for the route
 * (a controller in MVC speak).
 *
 * The #[Route] attribute can be set on the class (for global parameters),
 * and on each method.
 *
 * The #[Route] attribute main value is the route path. The attribute also
 * recognizes several parameters: requirements, options, defaults, schemes,
 * methods, host, and name. The name parameter is mandatory.
 * Here is an example of how you should be able to use it:
 *
 *     #[Route('/Blog')]
 *     class Blog
 *     {
 *         #[Route('/', name: 'blog_index')]
 *         public function index()
 *         {
 *         }
 *         #[Route('/{id}', name: 'blog_post', requirements: ["id" => '\d+'])]
 *         public function show()
 *         {
 *         }
 *     }
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Alexander M. Turek <me@derrabus.de>
 * @author Alexandre Daubois <alex.daubois@gmail.com>
 */
abstract class AttributeClassLoader implements LoaderInterface
{
    /**
     * @var Reader|null
     *
     * @deprecated in Symfony 6.4, this property will be removed in Symfony 7.
     */
    protected $reader;

    /**
     * @var string|null
     */
    protected $env;

    /**
     * @var string
     */
    protected $routeAnnotationClass = RouteAnnotation::class;

    /**
     * @var int
     */
    protected $defaultRouteIndex = 0;

    private bool $hasDeprecatedAnnotations = false;

    /**
     * @param string|null $env
     */
    public function __construct($env = null)
    {
        if ($env instanceof Reader || null === $env && \func_num_args() > 1 && null !== func_get_arg(1)) {
            trigger_deprecation('symfony/routing', '6.4', 'Passing an instance of "%s" as first and the environment as second argument to "%s" is deprecated. Pass the environment as first argument instead.', Reader::class, __METHOD__);

            $this->reader = $env;
            $env = \func_num_args() > 1 ? func_get_arg(1) : null;
        }

        if (\is_string($env) || null === $env) {
            $this->env = $env;
        } elseif ($env instanceof \Stringable || \is_scalar($env)) {
            $this->env = (string) $env;
        } else {
            throw new \TypeError(__METHOD__.sprintf(': Parameter $env was expected to be a string or null, "%s" given.', get_debug_type($env)));
        }
    }

    /**
     * Sets the annotation class to read route properties from.
     *
     * @return void
     */
    public function setRouteAnnotationClass(string $class)
    {
        $this->routeAnnotationClass = $class;
    }

    /**
     * @throws \InvalidArgumentException When route can't be parsed
     */
    public function load(mixed $class, ?string $type = null): RouteCollection
    {
        if (!class_exists($class)) {
            throw new \InvalidArgumentException(sprintf('Class "%s" does not exist.', $class));
        }

        $class = new \ReflectionClass($class);
        if ($class->isAbstract()) {
            throw new \InvalidArgumentException(sprintf('Attributes from class "%s" cannot be read as it is abstract.', $class->getName()));
        }

        $this->hasDeprecatedAnnotations = false;

        try {
            $globals = $this->getGlobals($class);
            $collection = new RouteCollection();
            $collection->addResource(new FileResource($class->getFileName()));
            if ($globals['env'] && $this->env !== $globals['env']) {
                return $collection;
            }
            $fqcnAlias = false;
            foreach ($class->getMethods() as $method) {
                $this->defaultRouteIndex = 0;
                $routeNamesBefore = array_keys($collection->all());
                foreach ($this->getAnnotations($method) as $annot) {
                    $this->addRoute($collection, $annot, $globals, $class, $method);
                    if ('__invoke' === $method->name) {
                        $fqcnAlias = true;
                    }
                }

                if (1 === $collection->count() - \count($routeNamesBefore)) {
                    $newRouteName = current(array_diff(array_keys($collection->all()), $routeNamesBefore));
                    if ($newRouteName !== $aliasName = sprintf('%s::%s', $class->name, $method->name)) {
                        $collection->addAlias($aliasName, $newRouteName);
                    }
                }
            }
            if (0 === $collection->count() && $class->hasMethod('__invoke')) {
                $globals = $this->resetGlobals();
                foreach ($this->getAnnotations($class) as $annot) {
                    $this->addRoute($collection, $annot, $globals, $class, $class->getMethod('__invoke'));
                    $fqcnAlias = true;
                }
            }
            if ($fqcnAlias && 1 === $collection->count()) {
                $invokeRouteName = key($collection->all());
                if ($invokeRouteName !== $class->name) {
                    $collection->addAlias($class->name, $invokeRouteName);
                }

                if ($invokeRouteName !== $aliasName = sprintf('%s::__invoke', $class->name)) {
                    $collection->addAlias($aliasName, $invokeRouteName);
                }
            }

            if ($this->hasDeprecatedAnnotations) {
                trigger_deprecation('symfony/routing', '6.4', 'Class "%s" uses Doctrine Annotations to configure routes, which is deprecated. Use PHP attributes instead.', $class->getName());
            }
        } finally {
            $this->hasDeprecatedAnnotations = false;
        }

        return $collection;
    }

    /**
     * @param RouteAnnotation $annot or an object that exposes a similar interface
     *
     * @return void
     */
    protected function addRoute(RouteCollection $collection, object $annot, array $globals, \ReflectionClass $class, \ReflectionMethod $method)
    {
        if ($annot->getEnv() && $annot->getEnv() !== $this->env) {
            return;
        }

        $name = $annot->getName() ?? $this->getDefaultRouteName($class, $method);
        $name = $globals['name'].$name;

        $requirements = $annot->getRequirements();

        foreach ($requirements as $placeholder => $requirement) {
            if (\is_int($placeholder)) {
                throw new \InvalidArgumentException(sprintf('A placeholder name must be a string (%d given). Did you forget to specify the placeholder key for the requirement "%s" of route "%s" in "%s::%s()"?', $placeholder, $requirement, $name, $class->getName(), $method->getName()));
            }
        }

        $defaults = array_replace($globals['defaults'], $annot->getDefaults());
        $requirements = array_replace($globals['requirements'], $requirements);
        $options = array_replace($globals['options'], $annot->getOptions());
        $schemes = array_unique(array_merge($globals['schemes'], $annot->getSchemes()));
        $methods = array_unique(array_merge($globals['methods'], $annot->getMethods()));

        $host = $annot->getHost() ?? $globals['host'];
        $condition = $annot->getCondition() ?? $globals['condition'];
        $priority = $annot->getPriority() ?? $globals['priority'];

        $path = $annot->getLocalizedPaths() ?: $annot->getPath();
        $prefix = $globals['localized_paths'] ?: $globals['path'];
        $paths = [];

        if (\is_array($path)) {
            if (!\is_array($prefix)) {
                foreach ($path as $locale => $localePath) {
                    $paths[$locale] = $prefix.$localePath;
                }
            } elseif ($missing = array_diff_key($prefix, $path)) {
                throw new \LogicException(sprintf('Route to "%s" is missing paths for locale(s) "%s".', $class->name.'::'.$method->name, implode('", "', array_keys($missing))));
            } else {
                foreach ($path as $locale => $localePath) {
                    if (!isset($prefix[$locale])) {
                        throw new \LogicException(sprintf('Route to "%s" with locale "%s" is missing a corresponding prefix in class "%s".', $method->name, $locale, $class->name));
                    }

                    $paths[$locale] = $prefix[$locale].$localePath;
                }
            }
        } elseif (\is_array($prefix)) {
            foreach ($prefix as $locale => $localePrefix) {
                $paths[$locale] = $localePrefix.$path;
            }
        } else {
            $paths[] = $prefix.$path;
        }

        foreach ($method->getParameters() as $param) {
            if (isset($defaults[$param->name]) || !$param->isDefaultValueAvailable()) {
                continue;
            }
            foreach ($paths as $locale => $path) {
                if (preg_match(sprintf('/\{%s(?:<.*?>)?\}/', preg_quote($param->name)), $path)) {
                    if (\is_scalar($defaultValue = $param->getDefaultValue()) || null === $defaultValue) {
                        $defaults[$param->name] = $defaultValue;
                    } elseif ($defaultValue instanceof \BackedEnum) {
                        $defaults[$param->name] = $defaultValue->value;
                    }
                    break;
                }
            }
        }

        foreach ($paths as $locale => $path) {
            $route = $this->createRoute($path, $defaults, $requirements, $options, $host, $schemes, $methods, $condition);
            $this->configureRoute($route, $class, $method, $annot);
            if (0 !== $locale) {
                $route->setDefault('_locale', $locale);
                $route->setRequirement('_locale', preg_quote($locale));
                $route->setDefault('_canonical_route', $name);
                $collection->add($name.'.'.$locale, $route, $priority);
            } else {
                $collection->add($name, $route, $priority);
            }
        }
    }

    public function supports(mixed $resource, ?string $type = null): bool
    {
        if ('annotation' === $type) {
            trigger_deprecation('symfony/routing', '6.4', 'The "annotation" route type is deprecated, use the "attribute" route type instead.');
        }

        return \is_string($resource) && preg_match('/^(?:\\\\?[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)+$/', $resource) && (!$type || \in_array($type, ['annotation', 'attribute'], true));
    }

    public function setResolver(LoaderResolverInterface $resolver): void
    {
    }

    public function getResolver(): LoaderResolverInterface
    {
    }

    /**
     * Gets the default route name for a class method.
     *
     * @return string
     */
    protected function getDefaultRouteName(\ReflectionClass $class, \ReflectionMethod $method)
    {
        $name = str_replace('\\', '_', $class->name).'_'.$method->name;
        $name = \function_exists('mb_strtolower') && preg_match('//u', $name) ? mb_strtolower($name, 'UTF-8') : strtolower($name);
        if ($this->defaultRouteIndex > 0) {
            $name .= '_'.$this->defaultRouteIndex;
        }
        ++$this->defaultRouteIndex;

        return $name;
    }

    /**
     * @return array<string, mixed>
     */
    protected function getGlobals(\ReflectionClass $class)
    {
        $globals = $this->resetGlobals();

        $annot = null;
        if ($attribute = $class->getAttributes($this->routeAnnotationClass, \ReflectionAttribute::IS_INSTANCEOF)[0] ?? null) {
            $annot = $attribute->newInstance();
        }
        if (!$annot && $annot = $this->reader?->getClassAnnotation($class, $this->routeAnnotationClass)) {
            $this->hasDeprecatedAnnotations = true;
        }

        if ($annot) {
            if (null !== $annot->getName()) {
                $globals['name'] = $annot->getName();
            }

            if (null !== $annot->getPath()) {
                $globals['path'] = $annot->getPath();
            }

            $globals['localized_paths'] = $annot->getLocalizedPaths();

            if (null !== $annot->getRequirements()) {
                $globals['requirements'] = $annot->getRequirements();
            }

            if (null !== $annot->getOptions()) {
                $globals['options'] = $annot->getOptions();
            }

            if (null !== $annot->getDefaults()) {
                $globals['defaults'] = $annot->getDefaults();
            }

            if (null !== $annot->getSchemes()) {
                $globals['schemes'] = $annot->getSchemes();
            }

            if (null !== $annot->getMethods()) {
                $globals['methods'] = $annot->getMethods();
            }

            if (null !== $annot->getHost()) {
                $globals['host'] = $annot->getHost();
            }

            if (null !== $annot->getCondition()) {
                $globals['condition'] = $annot->getCondition();
            }

            $globals['priority'] = $annot->getPriority() ?? 0;
            $globals['env'] = $annot->getEnv();

            foreach ($globals['requirements'] as $placeholder => $requirement) {
                if (\is_int($placeholder)) {
                    throw new \InvalidArgumentException(sprintf('A placeholder name must be a string (%d given). Did you forget to specify the placeholder key for the requirement "%s" in "%s"?', $placeholder, $requirement, $class->getName()));
                }
            }
        }

        return $globals;
    }

    private function resetGlobals(): array
    {
        return [
            'path' => null,
            'localized_paths' => [],
            'requirements' => [],
            'options' => [],
            'defaults' => [],
            'schemes' => [],
            'methods' => [],
            'host' => '',
            'condition' => '',
            'name' => '',
            'priority' => 0,
            'env' => null,
        ];
    }

    /**
     * @return Route
     */
    protected function createRoute(string $path, array $defaults, array $requirements, array $options, ?string $host, array $schemes, array $methods, ?string $condition)
    {
        return new Route($path, $defaults, $requirements, $options, $host, $schemes, $methods, $condition);
    }

    /**
     * @return void
     */
    abstract protected function configureRoute(Route $route, \ReflectionClass $class, \ReflectionMethod $method, object $annot);

    /**
     * @return iterable<int, RouteAnnotation>
     */
    private function getAnnotations(\ReflectionClass|\ReflectionMethod $reflection): iterable
    {
        foreach ($reflection->getAttributes($this->routeAnnotationClass, \ReflectionAttribute::IS_INSTANCEOF) as $attribute) {
            yield $attribute->newInstance();
        }

        if (!$this->reader) {
            return;
        }

        $annotations = $reflection instanceof \ReflectionClass
            ? $this->reader->getClassAnnotations($reflection)
            : $this->reader->getMethodAnnotations($reflection);

        foreach ($annotations as $annotation) {
            if ($annotation instanceof $this->routeAnnotationClass) {
                $this->hasDeprecatedAnnotations = true;

                yield $annotation;
            }
        }
    }
}

if (!class_exists(AnnotationClassLoader::class, false)) {
    class_alias(AttributeClassLoader::class, AnnotationClassLoader::class);
}
