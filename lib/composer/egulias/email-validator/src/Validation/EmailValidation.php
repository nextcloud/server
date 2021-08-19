<?php

namespace Egulias\EmailValidator\Validation;

use Egulias\EmailValidator\EmailLexer;
use Egulias\EmailValidator\Exception\InvalidEmail;
use Egulias\EmailValidator\Warning\Warning;

interface EmailValidation
{
    /**
     * Returns true if the given email is valid.
     *
     * @param string     $email      The email you want to validate.
     * @param EmailLexer $emailLexer The email lexer.
     *
     * @return bool
     */
    public function isValid($email, EmailLexer $emailLexer);

    /**
     * Returns the validation error.
     *
     * @return InvalidEmail|null
     */
    public function getError();

    /**
     * Returns the validation warnings.
     *
     * @return Warning[]
     */
    public function getWarnings();
}
