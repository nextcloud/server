<?php

/*
 * This file is part of composer/semver.
 *
 * (c) Composer <https://github.com/composer>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Composer\Semver\Constraint;

trigger_error('The ' . __NAMESPACE__ . '\AbstractConstraint abstract class is deprecated, there is no replacement for it, it will be removed in the next major version.', E_USER_DEPRECATED);

/**
 * Base constraint class.
 */
abstract class AbstractConstraint implements ConstraintInterface
{
    /** @var string */
    protected $prettyString;

    /**
     * @param ConstraintInterface $provider
     *
     * @return bool
     */
    public function matches(ConstraintInterface $provider)
    {
        if ($provider instanceof $this) {
            // see note at bottom of this class declaration
            return $this->matchSpecific($provider);
        }

        // turn matching around to find a match
        return $provider->matches($this);
    }

    /**
     * @param string $prettyString
     */
    public function setPrettyString($prettyString)
    {
        $this->prettyString = $prettyString;
    }

    /**
     * @return string
     */
    public function getPrettyString()
    {
        if ($this->prettyString) {
            return $this->prettyString;
        }

        return $this->__toString();
    }

    // implementations must implement a method of this format:
    // not declared abstract here because type hinting violates parameter coherence (TODO right word?)
    // public function matchSpecific(<SpecificConstraintType> $provider);
}
