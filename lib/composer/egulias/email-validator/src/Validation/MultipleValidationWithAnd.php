<?php

namespace Egulias\EmailValidator\Validation;

use Egulias\EmailValidator\EmailLexer;
use Egulias\EmailValidator\Validation\Exception\EmptyValidationList;

class MultipleValidationWithAnd implements EmailValidation
{
    /**
     * If one of validations gets failure skips all succeeding validation.
     * This means MultipleErrors will only contain a single error which first found.
     */
    const STOP_ON_ERROR = 0;

    /**
     * All of validations will be invoked even if one of them got failure.
     * So MultipleErrors will contain all causes.
     */
    const ALLOW_ALL_ERRORS = 1;

    /**
     * @var EmailValidation[]
     */
    private $validations = [];

    /**
     * @var array
     */
    private $warnings = [];

    /**
     * @var MultipleErrors|null
     */
    private $error;

    /**
     * @var int
     */
    private $mode;

    /**
     * @param EmailValidation[] $validations The validations.
     * @param int               $mode        The validation mode (one of the constants).
     */
    public function __construct(array $validations, $mode = self::ALLOW_ALL_ERRORS)
    {
        if (count($validations) == 0) {
            throw new EmptyValidationList();
        }

        $this->validations = $validations;
        $this->mode = $mode;
    }

    /**
     * {@inheritdoc}
     */
    public function isValid($email, EmailLexer $emailLexer)
    {
        $result = true;
        $errors = [];
        foreach ($this->validations as $validation) {
            $emailLexer->reset();
            $validationResult = $validation->isValid($email, $emailLexer);
            $result = $result && $validationResult;
            $this->warnings = array_merge($this->warnings, $validation->getWarnings());
            $errors = $this->addNewError($validation->getError(), $errors);

            if ($this->shouldStop($result)) {
                break;
            }
        }

        if (!empty($errors)) {
            $this->error = new MultipleErrors($errors);
        }

        return $result;
    }

    /**
     * @param \Egulias\EmailValidator\Exception\InvalidEmail|null $possibleError
     * @param \Egulias\EmailValidator\Exception\InvalidEmail[] $errors
     *
     * @return \Egulias\EmailValidator\Exception\InvalidEmail[]
     */
    private function addNewError($possibleError, array $errors)
    {
        if (null !== $possibleError) {
            $errors[] = $possibleError;
        }

        return $errors;
    }

    /**
     * @param bool $result
     *
     * @return bool
     */
    private function shouldStop($result)
    {
        return !$result && $this->mode === self::STOP_ON_ERROR;
    }

    /**
     * Returns the validation errors.
     *
     * @return MultipleErrors|null
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * {@inheritdoc}
     */
    public function getWarnings()
    {
        return $this->warnings;
    }
}
