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
 * CompiledRoutes are returned by the RouteCompiler class.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class CompiledRoute
{
    private $route;
    private $variables;
    private $tokens;
    private $staticPrefix;
    private $regex;

    /**
     * Constructor.
     *
     * @param Route  $route        A original Route instance
     * @param string $staticPrefix The static prefix of the compiled route
     * @param string $regex        The regular expression to use to match this route
     * @param array  $tokens       An array of tokens to use to generate URL for this route
     * @param array  $variables    An array of variables
     */
    public function __construct(Route $route, $staticPrefix, $regex, array $tokens, array $variables)
    {
        $this->route = $route;
        $this->staticPrefix = $staticPrefix;
        $this->regex = $regex;
        $this->tokens = $tokens;
        $this->variables = $variables;
    }

    /**
     * Returns the Route instance.
     *
     * @return Route A Route instance
     */
    public function getRoute()
    {
        return $this->route;
    }

    /**
     * Returns the static prefix.
     *
     * @return string The static prefix
     */
    public function getStaticPrefix()
    {
        return $this->staticPrefix;
    }

    /**
     * Returns the regex.
     *
     * @return string The regex
     */
    public function getRegex()
    {
        return $this->regex;
    }

    /**
     * Returns the tokens.
     *
     * @return array The tokens
     */
    public function getTokens()
    {
        return $this->tokens;
    }

    /**
     * Returns the variables.
     *
     * @return array The variables
     */
    public function getVariables()
    {
        return $this->variables;
    }

    /**
     * Returns the pattern.
     *
     * @return string The pattern
     */
    public function getPattern()
    {
        return $this->route->getPattern();
    }

    /**
     * Returns the options.
     *
     * @return array The options
     */
    public function getOptions()
    {
        return $this->route->getOptions();
    }

    /**
     * Returns the defaults.
     *
     * @return array The defaults
     */
    public function getDefaults()
    {
        return $this->route->getDefaults();
    }

    /**
     * Returns the requirements.
     *
     * @return array The requirements
     */
    public function getRequirements()
    {
        return $this->route->getRequirements();
    }
}
