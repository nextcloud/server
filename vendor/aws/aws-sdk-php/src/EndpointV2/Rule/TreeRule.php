<?php

namespace Aws\EndpointV2\Rule;

use Aws\EndpointV2\Ruleset\RulesetStandardLibrary;

class TreeRule extends AbstractRule
{
    /** @var array */
    private $rules;

    public function __construct(array $definition)
    {
        parent::__construct($definition);
        $this->rules = $this->createRules($definition['rules']);
    }

    /**
     * @return array
     */
    public function getRules()
    {
        return $this->rules;
    }

    /**
     * If a tree rule's conditions evaluate successfully, iterate over its
     * subordinate rules and return a result if there is one. If any of the
     * subsequent rules are trees, the function will recurse until it reaches
     * an error or an endpoint rule
     *
     * @return mixed
     */
    public function evaluate(
        array $inputParameters,
        RulesetStandardLibrary $standardLibrary
    )
    {
        if ($this->evaluateConditions($inputParameters, $standardLibrary)) {
            foreach($this->rules as $rule) {
                $inputParametersCopy = $inputParameters;
                $evaluation = $rule->evaluate($inputParametersCopy, $standardLibrary);
                if ($evaluation !== false) {
                    return $evaluation;
                }
            }
        }
        return false;
    }

    private function createRules(array $rules)
    {
        $rulesList = [];

        forEach($rules as $rule) {
            $ruleType = RuleCreator::create($rule['type'], $rule);
            $rulesList[] = $ruleType;
        }
        return $rulesList;
    }
}
