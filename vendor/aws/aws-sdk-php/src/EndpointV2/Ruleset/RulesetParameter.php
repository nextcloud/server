<?php

namespace Aws\EndpointV2\Ruleset;

use Aws\Exception\UnresolvedEndpointException;
use function \Aws\is_associative;

/**
 * Houses properties of an individual parameter definition.
 */
class RulesetParameter
{
    /** @var string */
    private $name;

    /** @var string */
    private $type;

    /** @var string */
    private $builtIn;

    /** @var string */
    private $default;

    /** @var array */
    private $required;

    /** @var string */
    private $documentation;

    /** @var boolean */
    private $deprecated;

    /** @var array<string, string> */
    private static $typeMap = [
        'String' => 'is_string',
        'Boolean' => 'is_bool',
        'StringArray' => 'isStringArray'
    ];

    public function __construct($name, array $definition)
    {
        $type = ucfirst($definition['type']);
        if ($this->isValidType($type)) {
            $this->type = $type;
        } else {
            throw new UnresolvedEndpointException(
                'Unknown parameter type ' . "`{$type}`" .
                '. Parameters must be of type `String`, `Boolean` or `StringArray.'
            );
        }

        $this->name = $name;
        $this->builtIn = $definition['builtIn'] ?? null;
        $this->default = $definition['default'] ?? null;
        $this->required = $definition['required'] ?? false;
        $this->documentation = $definition['documentation'] ?? null;
        $this->deprecated = $definition['deprecated'] ?? false;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return mixed
     */
    public function getBuiltIn()
    {
        return $this->builtIn;
    }

    /**
     * @return mixed
     */
    public function getDefault()
    {
        return $this->default;
    }

    /**
     * @return boolean
     */
    public function getRequired()
    {
        return $this->required;
    }

    /**
     * @return string
     */
    public function getDocumentation()
    {
        return $this->documentation;
    }

    /**
     * @return boolean
     */
    public function getDeprecated()
    {
        return $this->deprecated;
    }

    /**
     * Validates that an input parameter matches the type provided in its definition.
     *
     * @return void
     * @throws InvalidArgumentException
     */
    public function validateInputParam($inputParam)
    {
        if (!$this->isValidInput($inputParam)) {
            throw new UnresolvedEndpointException(
                "Input parameter `{$this->name}` is the wrong type. Must be a {$this->type}."
            );
        }

        if ($this->deprecated) {
            $deprecated = $this->deprecated;
            $deprecationString = "{$this->name} has been deprecated ";
            $msg = $deprecated['message'] ?? null;
            $since = $deprecated['since'] ?? null;

            if (!is_null($since)){
                $deprecationString .= 'since ' . $since . '. ';
            }
            if (!is_null($msg)) {
                $deprecationString .= $msg;
            }

            trigger_error($deprecationString, E_USER_WARNING);
        }
    }

    private function isValidType($type)
    {
        return isset(self::$typeMap[$type]);
    }

    private function isValidInput($inputParam): bool
    {
        $method = self::$typeMap[$this->type];
        if (is_callable($method)) {
            return $method($inputParam);
        } elseif (method_exists($this, $method)) {
            return $this->$method($inputParam);
        }

        return false;
    }

    private function isStringArray(array $array): bool
    {
        if (is_associative($array)) {
            return false;
        }

        foreach($array as $value) {
            if (!is_string($value)) {
                return false;
            }
        }

        return true;
    }
}
