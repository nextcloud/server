<?php

/*
 * This file is part of the JsonSchema package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JsonSchema\Constraints;

use JsonSchema\Entity\JsonPointer;

/**
 * The ObjectConstraint Constraints, validates an object against a given schema
 *
 * @author Robert Schönthal <seroscho@googlemail.com>
 * @author Bruno Prieto Reis <bruno.p.reis@gmail.com>
 */
class ObjectConstraint extends Constraint
{
    /**
     * @var array List of properties to which a default value has been applied
     */
    protected $appliedDefaults = array();

    /**
     * {@inheritdoc}
     */
    public function check(&$element, $schema = null, JsonPointer $path = null, $properties = null,
        $additionalProp = null, $patternProperties = null, $appliedDefaults = array())
    {
        if ($element instanceof UndefinedConstraint) {
            return;
        }

        $this->appliedDefaults = $appliedDefaults;

        $matches = array();
        if ($patternProperties) {
            // validate the element pattern properties
            $matches = $this->validatePatternProperties($element, $path, $patternProperties);
        }

        if ($properties) {
            // validate the element properties
            $this->validateProperties($element, $properties, $path);
        }

        // validate additional element properties & constraints
        $this->validateElement($element, $matches, $schema, $path, $properties, $additionalProp);
    }

    public function validatePatternProperties($element, JsonPointer $path = null, $patternProperties)
    {
        $try = array('/', '#', '+', '~', '%');
        $matches = array();
        foreach ($patternProperties as $pregex => $schema) {
            $delimiter = '/';
            // Choose delimiter. Necessary for patterns like ^/ , otherwise you get error
            foreach ($try as $delimiter) {
                if (strpos($pregex, $delimiter) === false) { // safe to use
                    break;
                }
            }

            // Validate the pattern before using it to test for matches
            if (@preg_match($delimiter . $pregex . $delimiter . 'u', '') === false) {
                $this->addError($path, 'The pattern "' . $pregex . '" is invalid', 'pregex', array('pregex' => $pregex));
                continue;
            }
            foreach ($element as $i => $value) {
                if (preg_match($delimiter . $pregex . $delimiter . 'u', $i)) {
                    $matches[] = $i;
                    $this->checkUndefined($value, $schema ?: new \stdClass(), $path, $i, in_array($i, $this->appliedDefaults));
                }
            }
        }

        return $matches;
    }

    /**
     * Validates the element properties
     *
     * @param \StdClass        $element        Element to validate
     * @param array            $matches        Matches from patternProperties (if any)
     * @param \StdClass        $schema         ObjectConstraint definition
     * @param JsonPointer|null $path           Current test path
     * @param \StdClass        $properties     Properties
     * @param mixed            $additionalProp Additional properties
     */
    public function validateElement($element, $matches, $schema = null, JsonPointer $path = null,
        $properties = null, $additionalProp = null)
    {
        $this->validateMinMaxConstraint($element, $schema, $path);

        foreach ($element as $i => $value) {
            $definition = $this->getProperty($properties, $i);

            // no additional properties allowed
            if (!in_array($i, $matches) && $additionalProp === false && $this->inlineSchemaProperty !== $i && !$definition) {
                $this->addError($path, 'The property ' . $i . ' is not defined and the definition does not allow additional properties', 'additionalProp');
            }

            // additional properties defined
            if (!in_array($i, $matches) && $additionalProp && !$definition) {
                if ($additionalProp === true) {
                    $this->checkUndefined($value, null, $path, $i, in_array($i, $this->appliedDefaults));
                } else {
                    $this->checkUndefined($value, $additionalProp, $path, $i, in_array($i, $this->appliedDefaults));
                }
            }

            // property requires presence of another
            $require = $this->getProperty($definition, 'requires');
            if ($require && !$this->getProperty($element, $require)) {
                $this->addError($path, 'The presence of the property ' . $i . ' requires that ' . $require . ' also be present', 'requires');
            }

            $property = $this->getProperty($element, $i, $this->factory->createInstanceFor('undefined'));
            if (is_object($property)) {
                $this->validateMinMaxConstraint(!($property instanceof UndefinedConstraint) ? $property : $element, $definition, $path);
            }
        }
    }

    /**
     * Validates the definition properties
     *
     * @param \stdClass        $element    Element to validate
     * @param \stdClass        $properties Property definitions
     * @param JsonPointer|null $path       Path?
     */
    public function validateProperties(&$element, $properties = null, JsonPointer $path = null)
    {
        $undefinedConstraint = $this->factory->createInstanceFor('undefined');

        foreach ($properties as $i => $value) {
            $property = &$this->getProperty($element, $i, $undefinedConstraint);
            $definition = $this->getProperty($properties, $i);

            if (is_object($definition)) {
                // Undefined constraint will check for is_object() and quit if is not - so why pass it?
                $this->checkUndefined($property, $definition, $path, $i, in_array($i, $this->appliedDefaults));
            }
        }
    }

    /**
     * retrieves a property from an object or array
     *
     * @param mixed  $element  Element to validate
     * @param string $property Property to retrieve
     * @param mixed  $fallback Default value if property is not found
     *
     * @return mixed
     */
    protected function &getProperty(&$element, $property, $fallback = null)
    {
        if (is_array($element) && (isset($element[$property]) || array_key_exists($property, $element)) /*$this->checkMode == self::CHECK_MODE_TYPE_CAST*/) {
            return $element[$property];
        } elseif (is_object($element) && property_exists($element, $property)) {
            return $element->$property;
        }

        return $fallback;
    }

    /**
     * validating minimum and maximum property constraints (if present) against an element
     *
     * @param \stdClass        $element          Element to validate
     * @param \stdClass        $objectDefinition ObjectConstraint definition
     * @param JsonPointer|null $path             Path to test?
     */
    protected function validateMinMaxConstraint($element, $objectDefinition, JsonPointer $path = null)
    {
        // Verify minimum number of properties
        if (isset($objectDefinition->minProperties) && !is_object($objectDefinition->minProperties)) {
            if ($this->getTypeCheck()->propertyCount($element) < $objectDefinition->minProperties) {
                $this->addError($path, 'Must contain a minimum of ' . $objectDefinition->minProperties . ' properties', 'minProperties', array('minProperties' => $objectDefinition->minProperties));
            }
        }
        // Verify maximum number of properties
        if (isset($objectDefinition->maxProperties) && !is_object($objectDefinition->maxProperties)) {
            if ($this->getTypeCheck()->propertyCount($element) > $objectDefinition->maxProperties) {
                $this->addError($path, 'Must contain no more than ' . $objectDefinition->maxProperties . ' properties', 'maxProperties', array('maxProperties' => $objectDefinition->maxProperties));
            }
        }
    }
}
