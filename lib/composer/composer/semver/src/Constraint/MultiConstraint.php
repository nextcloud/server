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

/**
 * Defines a conjunctive or disjunctive set of constraints.
 */
class MultiConstraint implements ConstraintInterface
{
    /** @var ConstraintInterface[] */
    protected $constraints;

    /** @var string|null */
    protected $prettyString;

    /** @var bool */
    protected $conjunctive;

    /**
     * @param ConstraintInterface[] $constraints A set of constraints
     * @param bool                  $conjunctive Whether the constraints should be treated as conjunctive or disjunctive
     */
    public function __construct(array $constraints, $conjunctive = true)
    {
        $this->constraints = $constraints;
        $this->conjunctive = $conjunctive;
    }

    /**
     * @return ConstraintInterface[]
     */
    public function getConstraints()
    {
        return $this->constraints;
    }

    /**
     * @return bool
     */
    public function isConjunctive()
    {
        return $this->conjunctive;
    }

    /**
     * @return bool
     */
    public function isDisjunctive()
    {
        return !$this->conjunctive;
    }

    /**
     * @param ConstraintInterface $provider
     *
     * @return bool
     */
    public function matches(ConstraintInterface $provider)
    {
        if (false === $this->conjunctive) {
            foreach ($this->constraints as $constraint) {
                if ($constraint->matches($provider)) {
                    return true;
                }
            }

            return false;
        }

        foreach ($this->constraints as $constraint) {
            if (!$constraint->matches($provider)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param string|null $prettyString
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

        return (string) $this;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        $constraints = array();
        foreach ($this->constraints as $constraint) {
            $constraints[] = (string) $constraint;
        }

        return '[' . implode($this->conjunctive ? ' ' : ' || ', $constraints) . ']';
    }
}
