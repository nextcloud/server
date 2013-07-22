<?php

namespace Guzzle\Service\Description;

/**
 * Validator responsible for preparing and validating parameters against the parameter's schema
 */
interface ValidatorInterface
{
    /**
     * Validate a value against the acceptable types, regular expressions, minimum, maximums, instanceOf, enums, etc
     * Add default and static values to the passed in variable. If the validation completes successfully, the input
     * must be run correctly through the matching schema's filters attribute.
     *
     * @param Parameter $param Schema that is being validated against the value
     * @param mixed     $value Value to validate and process. The value may change during this process.
     *
     * @return bool  Returns true if the input data is valid for the schema
     */
    public function validate(Parameter $param, &$value);

    /**
     * Get validation errors encountered while validating
     *
     * @return array
     */
    public function getErrors();
}
