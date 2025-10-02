<?php

declare(strict_types=1);

namespace JsonSchema\Constraints;

use JsonSchema\ConstraintError;
use JsonSchema\Constraints\TypeCheck\LooseTypeCheck;
use JsonSchema\Entity\JsonPointer;
use JsonSchema\Exception\ValidationException;
use JsonSchema\Tool\DeepCopy;
use JsonSchema\Uri\UriResolver;

#[\AllowDynamicProperties]
class UndefinedConstraint extends Constraint
{
    /**
     * @var list<string> List of properties to which a default value has been applied
     */
    protected $appliedDefaults = [];

    /**
     * {@inheritdoc}
     * */
    public function check(&$value, $schema = null, ?JsonPointer $path = null, $i = null, bool $fromDefault = false): void
    {
        if (is_null($schema) || !is_object($schema)) {
            return;
        }

        $path = $this->incrementPath($path, $i);
        if ($fromDefault) {
            $path->setFromDefault();
        }

        // check special properties
        $this->validateCommonProperties($value, $schema, $path, $i);

        // check allOf, anyOf, and oneOf properties
        $this->validateOfProperties($value, $schema, $path, '');

        // check known types
        $this->validateTypes($value, $schema, $path, $i);
    }

    /**
     * Validates the value against the types
     *
     * @param mixed       $value
     * @param mixed       $schema
     * @param JsonPointer $path
     * @param string      $i
     */
    public function validateTypes(&$value, $schema, JsonPointer $path, $i = null)
    {
        // check array
        if ($this->getTypeCheck()->isArray($value)) {
            $this->checkArray($value, $schema, $path, $i);
        }

        // check object
        if (LooseTypeCheck::isObject($value)) {
            // object processing should always be run on assoc arrays,
            // so use LooseTypeCheck here even if CHECK_MODE_TYPE_CAST
            // is not set (i.e. don't use $this->getTypeCheck() here).
            $this->checkObject(
                $value,
                $schema,
                $path,
                isset($schema->properties) ? $schema->properties : null,
                isset($schema->additionalProperties) ? $schema->additionalProperties : null,
                isset($schema->patternProperties) ? $schema->patternProperties : null,
                $this->appliedDefaults
            );
        }

        // check string
        if (is_string($value)) {
            $this->checkString($value, $schema, $path, $i);
        }

        // check numeric
        if (is_numeric($value)) {
            $this->checkNumber($value, $schema, $path, $i);
        }

        // check enum
        if (isset($schema->enum)) {
            $this->checkEnum($value, $schema, $path, $i);
        }

        // check const
        if (isset($schema->const)) {
            $this->checkConst($value, $schema, $path, $i);
        }
    }

    /**
     * Validates common properties
     *
     * @param mixed       $value
     * @param mixed       $schema
     * @param JsonPointer $path
     * @param string      $i
     */
    protected function validateCommonProperties(&$value, $schema, JsonPointer $path, $i = '')
    {
        // if it extends another schema, it must pass that schema as well
        if (isset($schema->extends)) {
            if (is_string($schema->extends)) {
                $schema->extends = $this->validateUri($schema, $schema->extends);
            }
            if (is_array($schema->extends)) {
                foreach ($schema->extends as $extends) {
                    $this->checkUndefined($value, $extends, $path, $i);
                }
            } else {
                $this->checkUndefined($value, $schema->extends, $path, $i);
            }
        }

        // Apply default values from schema
        if (!$path->fromDefault()) {
            $this->applyDefaultValues($value, $schema, $path);
        }

        // Verify required values
        if ($this->getTypeCheck()->isObject($value)) {
            if (!($value instanceof self) && isset($schema->required) && is_array($schema->required)) {
                // Draft 4 - Required is an array of strings - e.g. "required": ["foo", ...]
                foreach ($schema->required as $required) {
                    if (!$this->getTypeCheck()->propertyExists($value, $required)) {
                        $this->addError(
                            ConstraintError::REQUIRED(),
                            $this->incrementPath($path, $required), [
                                'property' => $required
                            ]
                        );
                    }
                }
            } elseif (isset($schema->required) && !is_array($schema->required)) {
                // Draft 3 - Required attribute - e.g. "foo": {"type": "string", "required": true}
                if ($schema->required && $value instanceof self) {
                    $propertyPaths = $path->getPropertyPaths();
                    $propertyName = end($propertyPaths);
                    $this->addError(ConstraintError::REQUIRED(), $path, ['property' => $propertyName]);
                }
            } else {
                // if the value is both undefined and not required, skip remaining checks
                // in this method which assume an actual, defined instance when validating.
                if ($value instanceof self) {
                    return;
                }
            }
        }

        // Verify type
        if (!($value instanceof self)) {
            $this->checkType($value, $schema, $path, $i);
        }

        // Verify disallowed items
        if (isset($schema->disallow)) {
            $initErrors = $this->getErrors();

            $typeSchema = new \stdClass();
            $typeSchema->type = $schema->disallow;
            $this->checkType($value, $typeSchema, $path);

            // if no new errors were raised it must be a disallowed value
            if (count($this->getErrors()) == count($initErrors)) {
                $this->addError(ConstraintError::DISALLOW(), $path);
            } else {
                $this->errors = $initErrors;
            }
        }

        if (isset($schema->not)) {
            $initErrors = $this->getErrors();
            $this->checkUndefined($value, $schema->not, $path, $i);

            // if no new errors were raised then the instance validated against the "not" schema
            if (count($this->getErrors()) == count($initErrors)) {
                $this->addError(ConstraintError::NOT(), $path);
            } else {
                $this->errors = $initErrors;
            }
        }

        // Verify that dependencies are met
        if (isset($schema->dependencies) && $this->getTypeCheck()->isObject($value)) {
            $this->validateDependencies($value, $schema->dependencies, $path);
        }
    }

    /**
     * Check whether a default should be applied for this value
     *
     * @param mixed $schema
     * @param mixed $parentSchema
     * @param bool  $requiredOnly
     *
     * @return bool
     */
    private function shouldApplyDefaultValue($requiredOnly, $schema, $name = null, $parentSchema = null)
    {
        // required-only mode is off
        if (!$requiredOnly) {
            return true;
        }
        // draft-04 required is set
        if (
            $name !== null
            && isset($parentSchema->required)
            && is_array($parentSchema->required)
            && in_array($name, $parentSchema->required)
        ) {
            return true;
        }
        // draft-03 required is set
        if (isset($schema->required) && !is_array($schema->required) && $schema->required) {
            return true;
        }
        // default case
        return false;
    }

    /**
     * Apply default values
     *
     * @param mixed       $value
     * @param mixed       $schema
     * @param JsonPointer $path
     */
    protected function applyDefaultValues(&$value, $schema, $path): void
    {
        // only apply defaults if feature is enabled
        if (!$this->factory->getConfig(self::CHECK_MODE_APPLY_DEFAULTS)) {
            return;
        }

        // apply defaults if appropriate
        $requiredOnly = (bool) $this->factory->getConfig(self::CHECK_MODE_ONLY_REQUIRED_DEFAULTS);
        if (isset($schema->properties) && LooseTypeCheck::isObject($value)) {
            // $value is an object or assoc array, and properties are defined - treat as an object
            foreach ($schema->properties as $currentProperty => $propertyDefinition) {
                $propertyDefinition = $this->factory->getSchemaStorage()->resolveRefSchema($propertyDefinition);
                if (
                    !LooseTypeCheck::propertyExists($value, $currentProperty)
                    && property_exists($propertyDefinition, 'default')
                    && $this->shouldApplyDefaultValue($requiredOnly, $propertyDefinition, $currentProperty, $schema)
                ) {
                    // assign default value
                    if (is_object($propertyDefinition->default)) {
                        LooseTypeCheck::propertySet($value, $currentProperty, clone $propertyDefinition->default);
                    } else {
                        LooseTypeCheck::propertySet($value, $currentProperty, $propertyDefinition->default);
                    }
                    $this->appliedDefaults[] = $currentProperty;
                }
            }
        } elseif (isset($schema->items) && LooseTypeCheck::isArray($value)) {
            $items = [];
            if (LooseTypeCheck::isArray($schema->items)) {
                $items = $schema->items;
            } elseif (isset($schema->minItems) && count($value) < $schema->minItems) {
                $items = array_fill(count($value), $schema->minItems - count($value), $schema->items);
            }
            // $value is an array, and items are defined - treat as plain array
            foreach ($items as $currentItem => $itemDefinition) {
                $itemDefinition = $this->factory->getSchemaStorage()->resolveRefSchema($itemDefinition);
                if (
                    !array_key_exists($currentItem, $value)
                    && property_exists($itemDefinition, 'default')
                    && $this->shouldApplyDefaultValue($requiredOnly, $itemDefinition)) {
                    if (is_object($itemDefinition->default)) {
                        $value[$currentItem] = clone $itemDefinition->default;
                    } else {
                        $value[$currentItem] = $itemDefinition->default;
                    }
                }
                $path->setFromDefault();
            }
        } elseif (
            $value instanceof self
            && property_exists($schema, 'default')
            && $this->shouldApplyDefaultValue($requiredOnly, $schema)) {
            // $value is a leaf, not a container - apply the default directly
            $value = is_object($schema->default) ? clone $schema->default : $schema->default;
            $path->setFromDefault();
        }
    }

    /**
     * Validate allOf, anyOf, and oneOf properties
     *
     * @param mixed       $value
     * @param mixed       $schema
     * @param JsonPointer $path
     * @param string      $i
     */
    protected function validateOfProperties(&$value, $schema, JsonPointer $path, $i = '')
    {
        // Verify type
        if ($value instanceof self) {
            return;
        }

        if (isset($schema->allOf)) {
            $isValid = true;
            foreach ($schema->allOf as $allOf) {
                $initErrors = $this->getErrors();
                $this->checkUndefined($value, $allOf, $path, $i);
                $isValid = $isValid && (count($this->getErrors()) == count($initErrors));
            }
            if (!$isValid) {
                $this->addError(ConstraintError::ALL_OF(), $path);
            }
        }

        if (isset($schema->anyOf)) {
            $isValid = false;
            $startErrors = $this->getErrors();
            $coerceOrDefaults = $this->factory->getConfig(self::CHECK_MODE_COERCE_TYPES | self::CHECK_MODE_APPLY_DEFAULTS);

            foreach ($schema->anyOf as $anyOf) {
                $initErrors = $this->getErrors();
                try {
                    $anyOfValue = $coerceOrDefaults ? DeepCopy::copyOf($value) : $value;
                    $this->checkUndefined($anyOfValue, $anyOf, $path, $i);
                    if ($isValid = (count($this->getErrors()) === count($initErrors))) {
                        $value = $anyOfValue;
                        break;
                    }
                } catch (ValidationException $e) {
                    $isValid = false;
                }
            }
            if (!$isValid) {
                $this->addError(ConstraintError::ANY_OF(), $path);
            } else {
                $this->errors = $startErrors;
            }
        }

        if (isset($schema->oneOf)) {
            $allErrors = [];
            $matchedSchemas = [];
            $startErrors = $this->getErrors();
            $coerceOrDefaults = $this->factory->getConfig(self::CHECK_MODE_COERCE_TYPES | self::CHECK_MODE_APPLY_DEFAULTS);

            foreach ($schema->oneOf as $oneOf) {
                try {
                    $this->errors = [];

                    $oneOfValue = $coerceOrDefaults ? DeepCopy::copyOf($value) : $value;
                    $this->checkUndefined($oneOfValue, $oneOf, $path, $i);
                    if (count($this->getErrors()) === 0) {
                        $matchedSchemas[] = ['schema' => $oneOf, 'value' => $oneOfValue];
                    }
                    $allErrors = array_merge($allErrors, array_values($this->getErrors()));
                } catch (ValidationException $e) {
                    // deliberately do nothing here - validation failed, but we want to check
                    // other schema options in the OneOf field.
                }
            }
            if (count($matchedSchemas) !== 1) {
                $this->addErrors(array_merge($allErrors, $startErrors));
                $this->addError(ConstraintError::ONE_OF(), $path);
            } else {
                $this->errors = $startErrors;
                $value = $matchedSchemas[0]['value'];
            }
        }
    }

    /**
     * Validate dependencies
     *
     * @param mixed       $value
     * @param mixed       $dependencies
     * @param JsonPointer $path
     * @param string      $i
     */
    protected function validateDependencies($value, $dependencies, JsonPointer $path, $i = '')
    {
        foreach ($dependencies as $key => $dependency) {
            if ($this->getTypeCheck()->propertyExists($value, $key)) {
                if (is_string($dependency)) {
                    // Draft 3 string is allowed - e.g. "dependencies": {"bar": "foo"}
                    if (!$this->getTypeCheck()->propertyExists($value, $dependency)) {
                        $this->addError(ConstraintError::DEPENDENCIES(), $path, [
                            'key' => $key,
                            'dependency' => $dependency
                        ]);
                    }
                } elseif (is_array($dependency)) {
                    // Draft 4 must be an array - e.g. "dependencies": {"bar": ["foo"]}
                    foreach ($dependency as $d) {
                        if (!$this->getTypeCheck()->propertyExists($value, $d)) {
                            $this->addError(ConstraintError::DEPENDENCIES(), $path, [
                                'key' => $key,
                                'dependency' => $dependency
                            ]);
                        }
                    }
                } elseif (is_object($dependency)) {
                    // Schema - e.g. "dependencies": {"bar": {"properties": {"foo": {...}}}}
                    $this->checkUndefined($value, $dependency, $path, $i);
                }
            }
        }
    }

    protected function validateUri($schema, $schemaUri = null)
    {
        $resolver = new UriResolver();
        $retriever = $this->factory->getUriRetriever();

        $jsonSchema = null;
        if ($resolver->isValid($schemaUri)) {
            $schemaId = property_exists($schema, 'id') ? $schema->id : null;
            $jsonSchema = $retriever->retrieve($schemaId, $schemaUri);
        }

        return $jsonSchema;
    }
}
