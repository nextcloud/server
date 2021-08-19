<?php

namespace Egulias\EmailValidator\Parser;

use Egulias\EmailValidator\Exception\DotAtEnd;
use Egulias\EmailValidator\Exception\DotAtStart;
use Egulias\EmailValidator\EmailLexer;
use Egulias\EmailValidator\Exception\ExpectingAT;
use Egulias\EmailValidator\Exception\ExpectingATEXT;
use Egulias\EmailValidator\Exception\UnclosedQuotedString;
use Egulias\EmailValidator\Exception\UnopenedComment;
use Egulias\EmailValidator\Warning\CFWSWithFWS;
use Egulias\EmailValidator\Warning\LocalTooLong;

class LocalPart extends Parser
{
    public function parse($localPart)
    {
        $parseDQuote = true;
        $closingQuote = false;
        $openedParenthesis = 0;
        $totalLength = 0;

        while ($this->lexer->token['type'] !== EmailLexer::S_AT && null !== $this->lexer->token['type']) {
            if ($this->lexer->token['type'] === EmailLexer::S_DOT && null === $this->lexer->getPrevious()['type']) {
                throw new DotAtStart();
            }

            $closingQuote = $this->checkDQUOTE($closingQuote);
            if ($closingQuote && $parseDQuote) {
                $parseDQuote = $this->parseDoubleQuote();
            }

            if ($this->lexer->token['type'] === EmailLexer::S_OPENPARENTHESIS) {
                $this->parseComments();
                $openedParenthesis += $this->getOpenedParenthesis();
            }

            if ($this->lexer->token['type'] === EmailLexer::S_CLOSEPARENTHESIS) {
                if ($openedParenthesis === 0) {
                    throw new UnopenedComment();
                }

                $openedParenthesis--;
            }

            $this->checkConsecutiveDots();

            if ($this->lexer->token['type'] === EmailLexer::S_DOT &&
                $this->lexer->isNextToken(EmailLexer::S_AT)
            ) {
                throw new DotAtEnd();
            }

            $this->warnEscaping();
            $this->isInvalidToken($this->lexer->token, $closingQuote);

            if ($this->isFWS()) {
                $this->parseFWS();
            }

            $totalLength += strlen($this->lexer->token['value']);
            $this->lexer->moveNext();
        }

        if ($totalLength > LocalTooLong::LOCAL_PART_LENGTH) {
            $this->warnings[LocalTooLong::CODE] = new LocalTooLong();
        }
    }

    /**
     * @return bool
     */
    protected function parseDoubleQuote()
    {
        $parseAgain = true;
        $special = array(
            EmailLexer::S_CR => true,
            EmailLexer::S_HTAB => true,
            EmailLexer::S_LF => true
        );

        $invalid = array(
            EmailLexer::C_NUL => true,
            EmailLexer::S_HTAB => true,
            EmailLexer::S_CR => true,
            EmailLexer::S_LF => true
        );
        $setSpecialsWarning = true;

        $this->lexer->moveNext();

        while ($this->lexer->token['type'] !== EmailLexer::S_DQUOTE && null !== $this->lexer->token['type']) {
            $parseAgain = false;
            if (isset($special[$this->lexer->token['type']]) && $setSpecialsWarning) {
                $this->warnings[CFWSWithFWS::CODE] = new CFWSWithFWS();
                $setSpecialsWarning = false;
            }
            if ($this->lexer->token['type'] === EmailLexer::S_BACKSLASH && $this->lexer->isNextToken(EmailLexer::S_DQUOTE)) {
                $this->lexer->moveNext();
            }

            $this->lexer->moveNext();

            if (!$this->escaped() && isset($invalid[$this->lexer->token['type']])) {
                throw new ExpectingATEXT();
            }
        }

        $prev = $this->lexer->getPrevious();

        if ($prev['type'] === EmailLexer::S_BACKSLASH) {
            if (!$this->checkDQUOTE(false)) {
                throw new UnclosedQuotedString();
            }
        }

        if (!$this->lexer->isNextToken(EmailLexer::S_AT) && $prev['type'] !== EmailLexer::S_BACKSLASH) {
            throw new ExpectingAT();
        }

        return $parseAgain;
    }

    /**
     * @param bool $closingQuote
     */
    protected function isInvalidToken(array $token, $closingQuote)
    {
        $forbidden = array(
            EmailLexer::S_COMMA,
            EmailLexer::S_CLOSEBRACKET,
            EmailLexer::S_OPENBRACKET,
            EmailLexer::S_GREATERTHAN,
            EmailLexer::S_LOWERTHAN,
            EmailLexer::S_COLON,
            EmailLexer::S_SEMICOLON,
            EmailLexer::INVALID
        );

        if (in_array($token['type'], $forbidden) && !$closingQuote) {
            throw new ExpectingATEXT();
        }
    }
}
