<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Routing\Attribute;

/**
 * Annotation class for @Route().
 *
 * @Annotation
 * @NamedArgumentConstructor
 * @Target({"CLASS", "METHOD"})
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Alexander M. Turek <me@derrabus.de>
 */
#[\Attribute(\Attribute::IS_REPEATABLE | \Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
class Route
{
    private ?string $path = null;
    private array $localizedPaths = [];
    private array $methods;
    private array $schemes;

    /**
     * @param array<string|\Stringable> $requirements
     * @param string[]|string           $methods
     * @param string[]|string           $schemes
     */
    public function __construct(
        string|array|null $path = null,
        private ?string $name = null,
        private array $requirements = [],
        private array $options = [],
        private array $defaults = [],
        private ?string $host = null,
        array|string $methods = [],
        array|string $schemes = [],
        private ?string $condition = null,
        private ?int $priority = null,
        ?string $locale = null,
        ?string $format = null,
        ?bool $utf8 = null,
        ?bool $stateless = null,
        private ?string $env = null
    ) {
        if (\is_array($path)) {
            $this->localizedPaths = $path;
        } else {
            $this->path = $path;
        }
        $this->setMethods($methods);
        $this->setSchemes($schemes);

        if (null !== $locale) {
            $this->defaults['_locale'] = $locale;
        }

        if (null !== $format) {
            $this->defaults['_format'] = $format;
        }

        if (null !== $utf8) {
            $this->options['utf8'] = $utf8;
        }

        if (null !== $stateless) {
            $this->defaults['_stateless'] = $stateless;
        }
    }

    /**
     * @return void
     */
    public function setPath(string $path)
    {
        $this->path = $path;
    }

    /**
     * @return string|null
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @return void
     */
    public function setLocalizedPaths(array $localizedPaths)
    {
        $this->localizedPaths = $localizedPaths;
    }

    public function getLocalizedPaths(): array
    {
        return $this->localizedPaths;
    }

    /**
     * @return void
     */
    public function setHost(string $pattern)
    {
        $this->host = $pattern;
    }

    /**
     * @return string|null
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * @return void
     */
    public function setName(string $name)
    {
        $this->name = $name;
    }

    /**
     * @return string|null
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return void
     */
    public function setRequirements(array $requirements)
    {
        $this->requirements = $requirements;
    }

    /**
     * @return array
     */
    public function getRequirements()
    {
        return $this->requirements;
    }

    /**
     * @return void
     */
    public function setOptions(array $options)
    {
        $this->options = $options;
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @return void
     */
    public function setDefaults(array $defaults)
    {
        $this->defaults = $defaults;
    }

    /**
     * @return array
     */
    public function getDefaults()
    {
        return $this->defaults;
    }

    /**
     * @return void
     */
    public function setSchemes(array|string $schemes)
    {
        $this->schemes = (array) $schemes;
    }

    /**
     * @return array
     */
    public function getSchemes()
    {
        return $this->schemes;
    }

    /**
     * @return void
     */
    public function setMethods(array|string $methods)
    {
        $this->methods = (array) $methods;
    }

    /**
     * @return array
     */
    public function getMethods()
    {
        return $this->methods;
    }

    /**
     * @return void
     */
    public function setCondition(?string $condition)
    {
        $this->condition = $condition;
    }

    /**
     * @return string|null
     */
    public function getCondition()
    {
        return $this->condition;
    }

    public function setPriority(int $priority): void
    {
        $this->priority = $priority;
    }

    public function getPriority(): ?int
    {
        return $this->priority;
    }

    public function setEnv(?string $env): void
    {
        $this->env = $env;
    }

    public function getEnv(): ?string
    {
        return $this->env;
    }
}

if (!class_exists(\Symfony\Component\Routing\Annotation\Route::class, false)) {
    class_alias(Route::class, \Symfony\Component\Routing\Annotation\Route::class);
}
