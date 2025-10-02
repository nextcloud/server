<?php

namespace Aws\EndpointV2\Ruleset;

use Aws\EndpointV2\Rule\RuleCreator;

/**
 * A collection of rules, parameter definitions and a class of helper functions
 * used to resolve either an endpoint or an error.
 */
class Ruleset
{
    /** @var string */
    private $version;

    /** @var array */
    private $parameters;

    /** @var array */
    private $rules;

    /** @var RulesetStandardLibrary */
    public $standardLibrary;

    public function __construct(array $ruleset, array $partitions)
    {
        $this->version = $ruleset['version'];
        $this->parameters = $this->createParameters($ruleset['parameters']);
        $this->rules = $this->createRules($ruleset['rules']);
        $this->standardLibrary = new RulesetStandardLibrary($partitions);
    }

    /**
     * @return mixed
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @return array
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * @return array
     */
    public function getRules()
    {
        return $this->rules;
    }

    /**
     * Evaluate the ruleset against the input parameters.
     * Return the first rule the parameters match against.
     *
     * @return mixed
     */
    public function evaluate(array $inputParameters)
    {
        $this->validateInputParameters($inputParameters);

        foreach($this->rules as $rule) {
            $evaluation = $rule->evaluate($inputParameters, $this->standardLibrary);
            if ($evaluation !== false) {
                return $evaluation;
            }
        }
        return false;
    }

    /**
     * Ensures all corresponding client-provided parameters match
     * the Ruleset parameter's specified type.
     *
     * @return void
     */
    private function validateInputParameters(array &$inputParameters)
    {
        foreach($this->parameters as $paramName => $param) {
            $inputParam = isset($inputParameters[$paramName]) ? $inputParameters[$paramName] : null;

            if (is_null($inputParam) && !is_null($param->getDefault())) {
                $inputParameters[$paramName] = $param->getDefault();
            } elseif (!is_null($inputParam)) {
                $param->validateInputParam($inputParam);
            }
        }
    }

    private function createParameters(array $parameters)
    {
        $parameterList = [];

        foreach($parameters as $name => $definition) {
            $parameterList[$name] = new RulesetParameter($name, $definition);
        }

        return $parameterList;
    }

    private function createRules(array $rules)
    {
        $rulesList = [];

        forEach($rules as $rule) {
            $ruleObj = RuleCreator::create($rule['type'], $rule);
            $rulesList[] = $ruleObj;
        }
        return $rulesList;
    }
}

