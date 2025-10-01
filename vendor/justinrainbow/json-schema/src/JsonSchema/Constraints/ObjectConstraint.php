<?php

declare(strict_types=1);

namespace JsonSchema\Constraints;

use JsonSchema\ConstraintError;
use JsonSchema\Entity\JsonPointer;

class ObjectConstraint extends Constraint
{
    /**
     * @var list<string> List of properties to which a default value has been applied
     */
    protected $appliedDefaults = [];

    /**
     * {@inheritdoc}
     *
     * @param list<string> $appliedDefaults
     */
    public function check(
        &$element,
        $schema = null,
        ?JsonPointer $path = null,
        $properties = null,
        $additionalProp = null,
        $patternProperties = null,
        $appliedDefaults = []
    ): void {
        if ($element instanceof UndefinedConstraint) {
            return;
        }

        $this->appliedDefaults = $appliedDefaults;

        $matches = [];
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

    public function validatePatternProperties($element, ?JsonPointer $path, $patternProperties)
    {
        $matches = [];
        foreach ($patternProperties as $pregex => $schema) {
            $fullRegex = self::jsonPatternToPhpRegex($pregex);

            // Validate the pattern before using it to test for matches
            if (@preg_match($fullRegex, '') === false) {
                $this->addError(ConstraintError::PREGEX_INVALID(), $path, ['pregex' => $pregex]);
                continue;
            }
            foreach ($element as $i => $value) {
                if (preg_match($fullRegex, $i)) {
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
    public function validateElement($element, $matches, $schema = null, ?JsonPointer $path = null,
        $properties = null, $additionalProp = null)
    {
        $this->validateMinMaxConstraint($element, $schema, $path);

        foreach ($element as $i => $value) {
            $definition = $this->getProperty($properties, $i);

            // no additional properties allowed
            if (!in_array($i, $matches) && $additionalProp === false && $this->inlineSchemaProperty !== $i && !$definition) {
                $this->addError(ConstraintError::ADDITIONAL_PROPERTIES(), $path, ['property' => $i]);
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
                $this->addError(ConstraintError::REQUIRES(), $path, [
                    'property' => $i,
                    'requiredProperty' => $require
                ]);
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
    public function validateProperties(&$element, $properties = null, ?JsonPointer $path = null)
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
        } elseif (is_object($element) && property_exists($element, (string) $property)) {
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
    protected function validateMinMaxConstraint($element, $objectDefinition, ?JsonPointer $path = null)
    {
        if (!$this->getTypeCheck()::isObject($element)) {
            return;
        }

        // Verify minimum number of properties
        if (isset($objectDefinition->minProperties) && is_int($objectDefinition->minProperties)) {
            if ($this->getTypeCheck()->propertyCount($element) < max(0, $objectDefinition->minProperties)) {
                $this->addError(ConstraintError::PROPERTIES_MIN(), $path, ['minProperties' => $objectDefinition->minProperties]);
            }
        }
        // Verify maximum number of properties
        if (isset($objectDefinition->maxProperties) && is_int($objectDefinition->maxProperties)) {
            if ($this->getTypeCheck()->propertyCount($element) > max(0, $objectDefinition->maxProperties)) {
                $this->addError(ConstraintError::PROPERTIES_MAX(), $path, ['maxProperties' => $objectDefinition->maxProperties]);
            }
        }
    }
}
