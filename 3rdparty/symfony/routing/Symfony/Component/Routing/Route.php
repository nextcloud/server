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

/**
 * A Route describes a route and its parameters.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 *
 * @api
 */
class Route
{
    private $pattern;
    private $defaults;
    private $requirements;
    private $options;
    private $compiled;

    private static $compilers = array();

    /**
     * Constructor.
     *
     * Available options:
     *
     *  * compiler_class: A class name able to compile this route instance (RouteCompiler by default)
     *
     * @param string $pattern      The pattern to match
     * @param array  $defaults     An array of default parameter values
     * @param array  $requirements An array of requirements for parameters (regexes)
     * @param array  $options      An array of options
     *
     * @api
     */
    public function __construct($pattern, array $defaults = array(), array $requirements = array(), array $options = array())
    {
        $this->setPattern($pattern);
        $this->setDefaults($defaults);
        $this->setRequirements($requirements);
        $this->setOptions($options);
    }

    public function __clone()
    {
        $this->compiled = null;
    }

    /**
     * Returns the pattern.
     *
     * @return string The pattern
     */
    public function getPattern()
    {
        return $this->pattern;
    }

    /**
     * Sets the pattern.
     *
     * This method implements a fluent interface.
     *
     * @param string $pattern The pattern
     *
     * @return Route The current Route instance
     */
    public function setPattern($pattern)
    {
        $this->pattern = trim($pattern);

        // a route must start with a slash
        if (empty($this->pattern) || '/' !== $this->pattern[0]) {
            $this->pattern = '/'.$this->pattern;
        }

        return $this;
    }

    /**
     * Returns the options.
     *
     * @return array The options
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Sets the options.
     *
     * This method implements a fluent interface.
     *
     * @param array $options The options
     *
     * @return Route The current Route instance
     */
    public function setOptions(array $options)
    {
        $this->options = array_merge(array(
            'compiler_class' => 'Symfony\\Component\\Routing\\RouteCompiler',
        ), $options);

        return $this;
    }

    /**
     * Sets an option value.
     *
     * This method implements a fluent interface.
     *
     * @param string $name  An option name
     * @param mixed  $value The option value
     *
     * @return Route The current Route instance
     *
     * @api
     */
    public function setOption($name, $value)
    {
        $this->options[$name] = $value;

        return $this;
    }

    /**
     * Get an option value.
     *
     * @param string $name An option name
     *
     * @return mixed The option value
     */
    public function getOption($name)
    {
        return isset($this->options[$name]) ? $this->options[$name] : null;
    }

    /**
     * Returns the defaults.
     *
     * @return array The defaults
     */
    public function getDefaults()
    {
        return $this->defaults;
    }

    /**
     * Sets the defaults.
     *
     * This method implements a fluent interface.
     *
     * @param array $defaults The defaults
     *
     * @return Route The current Route instance
     */
    public function setDefaults(array $defaults)
    {
        $this->defaults = array();
        foreach ($defaults as $name => $default) {
            $this->defaults[(string) $name] = $default;
        }

        return $this;
    }

    /**
     * Gets a default value.
     *
     * @param string $name A variable name
     *
     * @return mixed The default value
     */
    public function getDefault($name)
    {
        return isset($this->defaults[$name]) ? $this->defaults[$name] : null;
    }

    /**
     * Checks if a default value is set for the given variable.
     *
     * @param string $name A variable name
     *
     * @return Boolean true if the default value is set, false otherwise
     */
    public function hasDefault($name)
    {
        return array_key_exists($name, $this->defaults);
    }

    /**
     * Sets a default value.
     *
     * @param string $name    A variable name
     * @param mixed  $default The default value
     *
     * @return Route The current Route instance
     *
     * @api
     */
    public function setDefault($name, $default)
    {
        $this->defaults[(string) $name] = $default;

        return $this;
    }

    /**
     * Returns the requirements.
     *
     * @return array The requirements
     */
    public function getRequirements()
    {
        return $this->requirements;
    }

    /**
     * Sets the requirements.
     *
     * This method implements a fluent interface.
     *
     * @param array $requirements The requirements
     *
     * @return Route The current Route instance
     */
    public function setRequirements(array $requirements)
    {
        $this->requirements = array();
        foreach ($requirements as $key => $regex) {
            $this->requirements[$key] = $this->sanitizeRequirement($key, $regex);
        }

        return $this;
    }

    /**
     * Returns the requirement for the given key.
     *
     * @param string $key The key
     *
     * @return string The regex
     */
    public function getRequirement($key)
    {
        return isset($this->requirements[$key]) ? $this->requirements[$key] : null;
    }

    /**
     * Sets a requirement for the given key.
     *
     * @param string $key   The key
     * @param string $regex The regex
     *
     * @return Route The current Route instance
     *
     * @api
     */
    public function setRequirement($key, $regex)
    {
        $this->requirements[$key] = $this->sanitizeRequirement($key, $regex);

        return $this;
    }

    /**
     * Compiles the route.
     *
     * @return CompiledRoute A CompiledRoute instance
     */
    public function compile()
    {
        if (null !== $this->compiled) {
            return $this->compiled;
        }

        $class = $this->getOption('compiler_class');

        if (!isset(self::$compilers[$class])) {
            self::$compilers[$class] = new $class;
        }

        return $this->compiled = self::$compilers[$class]->compile($this);
    }

    private function sanitizeRequirement($key, $regex)
    {
        if (is_array($regex)) {
            throw new \InvalidArgumentException(sprintf('Routing requirements must be a string, array given for "%s"', $key));
        }

        if ('^' == $regex[0]) {
            $regex = substr($regex, 1);
        }

        if ('$' == substr($regex, -1)) {
            $regex = substr($regex, 0, -1);
        }

        return $regex;
    }
}
