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
 * RouteCompiler compiles Route instances to CompiledRoute instances.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class RouteCompiler implements RouteCompilerInterface
{
    /**
     * Compiles the current route instance.
     *
     * @param Route $route A Route instance
     *
     * @return CompiledRoute A CompiledRoute instance
     */
    public function compile(Route $route)
    {
        $pattern = $route->getPattern();
        $len = strlen($pattern);
        $tokens = array();
        $variables = array();
        $pos = 0;
        preg_match_all('#.\{([\w\d_]+)\}#', $pattern, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER);
        foreach ($matches as $match) {
            if ($text = substr($pattern, $pos, $match[0][1] - $pos)) {
                $tokens[] = array('text', $text);
            }
            $seps = array($pattern[$pos]);
            $pos = $match[0][1] + strlen($match[0][0]);
            $var = $match[1][0];

            if ($req = $route->getRequirement($var)) {
                $regexp = $req;
            } else {
                if ($pos !== $len) {
                    $seps[] = $pattern[$pos];
                }
                $regexp = sprintf('[^%s]+?', preg_quote(implode('', array_unique($seps)), '#'));
            }

            $tokens[] = array('variable', $match[0][0][0], $regexp, $var);

            if (in_array($var, $variables)) {
                throw new \LogicException(sprintf('Route pattern "%s" cannot reference variable name "%s" more than once.', $route->getPattern(), $var));
            }

            $variables[] = $var;
        }

        if ($pos < $len) {
            $tokens[] = array('text', substr($pattern, $pos));
        }

        // find the first optional token
        $firstOptional = INF;
        for ($i = count($tokens) - 1; $i >= 0; $i--) {
            $token = $tokens[$i];
            if ('variable' === $token[0] && $route->hasDefault($token[3])) {
                $firstOptional = $i;
            } else {
                break;
            }
        }

        // compute the matching regexp
        $regexp = '';
        for ($i = 0, $nbToken = count($tokens); $i < $nbToken; $i++) {
            $regexp .= $this->computeRegexp($tokens, $i, $firstOptional);
        }

        return new CompiledRoute(
            $route,
            'text' === $tokens[0][0] ? $tokens[0][1] : '',
            sprintf("#^%s$#s", $regexp),
            array_reverse($tokens),
            $variables
        );
    }

    /**
     * Computes the regexp used to match the token.
     *
     * @param array   $tokens        The route tokens
     * @param integer $index         The index of the current token
     * @param integer $firstOptional The index of the first optional token
     *
     * @return string The regexp
     */
    private function computeRegexp(array $tokens, $index, $firstOptional)
    {
        $token = $tokens[$index];
        if ('text' === $token[0]) {
            // Text tokens
            return preg_quote($token[1], '#');
        } else {
            // Variable tokens
            if (0 === $index && 0 === $firstOptional && 1 == count($tokens)) {
                // When the only token is an optional variable token, the separator is required
                return sprintf('%s(?P<%s>%s)?', preg_quote($token[1], '#'), $token[3], $token[2]);
            } else {
                $nbTokens = count($tokens);
                $regexp = sprintf('%s(?P<%s>%s)', preg_quote($token[1], '#'), $token[3], $token[2]);
                if ($index >= $firstOptional) {
                    // Enclose each optional tokens in a subpattern to make it optional
                    $regexp = "(?:$regexp";
                    if ($nbTokens - 1 == $index) {
                        // Close the optional subpatterns
                        $regexp .= str_repeat(")?", $nbTokens - $firstOptional);
                    }
                }

                return $regexp;
            }
        }
    }
}
