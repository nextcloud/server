<?php

namespace Aws\EndpointV2\Rule;

use Aws\EndpointV2\Ruleset\RulesetStandardLibrary;

/**
 *  A rule within a rule set. All rules contain a conditions property,
 * which can be empty, and documentation about the rule.
 */
abstract class AbstractRule
{
    private $conditions;
    private $documentation;

    public function __construct(array $definition)
    {
        $this->conditions = $definition['conditions'];
        $this->documentation = isset($definition['documentation']) ?
            $definition['documentation'] : null;
    }

    /**
     * @return array
     */
    public function getConditions()
    {
        return $this->conditions;
    }

    /**
     * @return mixed
     */
    public function getDocumentation()
    {
        return $this->documentation;
    }

    /**
     * Determines if all conditions for a given rule are met.
     *
     * @return boolean
     */
    protected function evaluateConditions(
        array &$inputParameters,
        RulesetStandardLibrary $standardLibrary
    )
    {
        foreach($this->getConditions() as $condition) {
            $result = $standardLibrary->callFunction($condition, $inputParameters);
            if (is_null($result) || $result === false) {
                return false;
            }
        }
        return true;
    }

    abstract public function evaluate(
        array $inputParameters,
        RulesetStandardLibrary $standardLibrary
    );
}
