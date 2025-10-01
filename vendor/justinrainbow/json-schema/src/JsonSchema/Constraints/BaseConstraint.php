<?php

declare(strict_types=1);

namespace JsonSchema\Constraints;

use const JSON_ERROR_NONE;
use JsonSchema\ConstraintError;
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
    protected $errors = [];

    /**
     * @var int All error types which have occurred
     * @phpstan-var int-mask-of<Validator::ERROR_*>
     */
    protected $errorMask = Validator::ERROR_NONE;

    /**
     * @var Factory
     */
    protected $factory;

    public function __construct(?Factory $factory = null)
    {
        $this->factory = $factory ?: new Factory();
    }

    public function addError(ConstraintError $constraint, ?JsonPointer $path = null, array $more = []): void
    {
        $message = $constraint->getMessage();
        $name = $constraint->getValue();
        $error = [
            'property' => $this->convertJsonPointerIntoPropertyPath($path ?: new JsonPointer('')),
            'pointer' => ltrim((string) ($path ?: new JsonPointer('')), '#'),
            'message' => ucfirst(vsprintf($message, array_map(static function ($val) {
                if (is_scalar($val)) {
                    return is_bool($val) ? var_export($val, true) : $val;
                }

                return json_encode($val);
            }, array_values($more)))),
            'constraint' => [
                'name' => $name,
                'params' => $more
            ],
            'context' => $this->factory->getErrorContext(),
        ];

        if ($this->factory->getConfig(Constraint::CHECK_MODE_EXCEPTIONS)) {
            throw new ValidationException(sprintf('Error validating %s: %s', $error['pointer'], $error['message']));
        }

        $this->errors[] = $error;
        $this->errorMask |= $error['context'];
    }

    public function addErrors(array $errors): void
    {
        if ($errors) {
            $this->errors = array_merge($this->errors, $errors);
            $errorMask = &$this->errorMask;
            array_walk($errors, static function ($error) use (&$errorMask) {
                if (isset($error['context'])) {
                    $errorMask |= $error['context'];
                }
            });
        }
    }

    /**
     * @phpstan-param int-mask-of<Validator::ERROR_*> $errorContext
     */
    public function getErrors(int $errorContext = Validator::ERROR_ALL): array
    {
        if ($errorContext === Validator::ERROR_ALL) {
            return $this->errors;
        }

        return array_filter($this->errors, static function ($error) use ($errorContext) {
            return (bool) ($errorContext & $error['context']);
        });
    }

    /**
     * @phpstan-param int-mask-of<Validator::ERROR_*> $errorContext
     */
    public function numErrors(int $errorContext = Validator::ERROR_ALL): int
    {
        if ($errorContext === Validator::ERROR_ALL) {
            return count($this->errors);
        }

        return count($this->getErrors($errorContext));
    }

    public function isValid(): bool
    {
        return !$this->getErrors();
    }

    /**
     * Clears any reported errors. Should be used between
     * multiple validation checks.
     */
    public function reset(): void
    {
        $this->errors = [];
        $this->errorMask = Validator::ERROR_NONE;
    }

    /**
     * Get the error mask
     *
     * @phpstan-return int-mask-of<Validator::ERROR_*>
     */
    public function getErrorMask(): int
    {
        return $this->errorMask;
    }

    /**
     * Recursively cast an associative array to an object
     */
    public static function arrayToObjectRecursive(array $array): object
    {
        $json = json_encode($array);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $message = 'Unable to encode schema array as JSON';
            if (function_exists('json_last_error_msg')) {
                $message .= ': ' . json_last_error_msg();
            }
            throw new InvalidArgumentException($message);
        }

        return (object) json_decode($json, false);
    }

    /**
     * Transform a JSON pattern into a PCRE regex
     */
    public static function jsonPatternToPhpRegex(string $pattern): string
    {
        return '~' . str_replace('~', '\\~', $pattern) . '~u';
    }

    protected function convertJsonPointerIntoPropertyPath(JsonPointer $pointer): string
    {
        $result = array_map(
            static function ($path) {
                return sprintf(is_numeric($path) ? '[%d]' : '.%s', $path);
            },
            $pointer->getPropertyPaths()
        );

        return trim(implode('', $result), '.');
    }
}
