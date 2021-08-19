<?php

/*
 * This file is part of the JsonSchema package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JsonSchema\Constraints;

use JsonSchema\Entity\JsonPointer;
use JsonSchema\Exception\InvalidArgumentException;
use JsonSchema\Exception\ValidationException;
use JsonSchema\Validator;

/**
 * A more basic constraint definition - used for the public
 * interface to avoid exposing library internals.
 */
class BaseConstraint
{
    /**
     * @var array Errors
     */
    protected $errors = array();

    /**
     * @var int All error types which have occurred
     */
    protected $errorMask = Validator::ERROR_NONE;

    /**
     * @var Factory
     */
    protected $factory;

    /**
     * @param Factory $factory
     */
    public function __construct(Factory $factory = null)
    {
        $this->factory = $factory ?: new Factory();
    }

    public function addError(JsonPointer $path = null, $message, $constraint = '', array $more = null)
    {
        $error = array(
            'property' => $this->convertJsonPointerIntoPropertyPath($path ?: new JsonPointer('')),
            'pointer' => ltrim(strval($path ?: new JsonPointer('')), '#'),
            'message' => $message,
            'constraint' => $constraint,
            'context' => $this->factory->getErrorContext(),
        );

        if ($this->factory->getConfig(Constraint::CHECK_MODE_EXCEPTIONS)) {
            throw new ValidationException(sprintf('Error validating %s: %s', $error['pointer'], $error['message']));
        }

        if (is_array($more) && count($more) > 0) {
            $error += $more;
        }

        $this->errors[] = $error;
        $this->errorMask |= $error['context'];
    }

    public function addErrors(array $errors)
    {
        if ($errors) {
            $this->errors = array_merge($this->errors, $errors);
            $errorMask = &$this->errorMask;
            array_walk($errors, function ($error) use (&$errorMask) {
                if (isset($error['context'])) {
                    $errorMask |= $error['context'];
                }
            });
        }
    }

    public function getErrors($errorContext = Validator::ERROR_ALL)
    {
        if ($errorContext === Validator::ERROR_ALL) {
            return $this->errors;
        }

        return array_filter($this->errors, function ($error) use ($errorContext) {
            if ($errorContext & $error['context']) {
                return true;
            }
        });
    }

    public function numErrors($errorContext = Validator::ERROR_ALL)
    {
        if ($errorContext === Validator::ERROR_ALL) {
            return count($this->errors);
        }

        return count($this->getErrors($errorContext));
    }

    public function isValid()
    {
        return !$this->getErrors();
    }

    /**
     * Clears any reported errors.  Should be used between
     * multiple validation checks.
     */
    public function reset()
    {
        $this->errors = array();
        $this->errorMask = Validator::ERROR_NONE;
    }

    /**
     * Get the error mask
     *
     * @return int
     */
    public function getErrorMask()
    {
        return $this->errorMask;
    }

    /**
     * Recursively cast an associative array to an object
     *
     * @param array $array
     *
     * @return object
     */
    public static function arrayToObjectRecursive($array)
    {
        $json = json_encode($array);
        if (json_last_error() !== \JSON_ERROR_NONE) {
            $message = 'Unable to encode schema array as JSON';
            if (function_exists('json_last_error_msg')) {
                $message .= ': ' . json_last_error_msg();
            }
            throw new InvalidArgumentException($message);
        }

        return (object) json_decode($json);
    }
}
