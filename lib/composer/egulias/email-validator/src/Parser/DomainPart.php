<?php

namespace Egulias\EmailValidator\Parser;

use Egulias\EmailValidator\EmailLexer;
use Egulias\EmailValidator\Exception\CharNotAllowed;
use Egulias\EmailValidator\Exception\CommaInDomain;
use Egulias\EmailValidator\Exception\ConsecutiveAt;
use Egulias\EmailValidator\Exception\CRLFAtTheEnd;
use Egulias\EmailValidator\Exception\CRNoLF;
use Egulias\EmailValidator\Exception\DomainHyphened;
use Egulias\EmailValidator\Exception\DotAtEnd;
use Egulias\EmailValidator\Exception\DotAtStart;
use Egulias\EmailValidator\Exception\ExpectingATEXT;
use Egulias\EmailValidator\Exception\ExpectingDomainLiteralClose;
use Egulias\EmailValidator\Exception\ExpectingDTEXT;
use Egulias\EmailValidator\Exception\NoDomainPart;
use Egulias\EmailValidator\Exception\UnopenedComment;
use Egulias\EmailValidator\Warning\AddressLiteral;
use Egulias\EmailValidator\Warning\CFWSWithFWS;
use Egulias\EmailValidator\Warning\DeprecatedComment;
use Egulias\EmailValidator\Warning\DomainLiteral;
use Egulias\EmailValidator\Warning\DomainTooLong;
use Egulias\EmailValidator\Warning\IPV6BadChar;
use Egulias\EmailValidator\Warning\IPV6ColonEnd;
use Egulias\EmailValidator\Warning\IPV6ColonStart;
use Egulias\EmailValidator\Warning\IPV6Deprecated;
use Egulias\EmailValidator\Warning\IPV6DoubleColon;
use Egulias\EmailValidator\Warning\IPV6GroupCount;
use Egulias\EmailValidator\Warning\IPV6MaxGroups;
use Egulias\EmailValidator\Warning\LabelTooLong;
use Egulias\EmailValidator\Warning\ObsoleteDTEXT;
use Egulias\EmailValidator\Warning\TLD;

class DomainPart extends Parser
{
    const DOMAIN_MAX_LENGTH = 254;
    const LABEL_MAX_LENGTH = 63;

    /**
     * @var string
     */
    protected $domainPart = '';

    public function parse($domainPart)
    {
        $this->lexer->moveNext();

        $this->performDomainStartChecks();

        $domain = $this->doParseDomainPart();

        $prev = $this->lexer->getPrevious();
        $length = strlen($domain);

        if ($prev['type'] === EmailLexer::S_DOT) {
            throw new DotAtEnd();
        }
        if ($prev['type'] === EmailLexer::S_HYPHEN) {
            throw new DomainHyphened();
        }
        if ($length > self::DOMAIN_MAX_LENGTH) {
            $this->warnings[DomainTooLong::CODE] = new DomainTooLong();
        }
        if ($prev['type'] === EmailLexer::S_CR) {
            throw new CRLFAtTheEnd();
        }
        $this->domainPart = $domain;
    }

    private function performDomainStartChecks()
    {
        $this->checkInvalidTokensAfterAT();
        $this->checkEmptyDomain();

        if ($this->lexer->token['type'] === EmailLexer::S_OPENPARENTHESIS) {
            $this->warnings[DeprecatedComment::CODE] = new DeprecatedComment();
            $this->parseDomainComments();
        }
    }

    private function checkEmptyDomain()
    {
        $thereIsNoDomain = $this->lexer->token['type'] === EmailLexer::S_EMPTY ||
            ($this->lexer->token['type'] === EmailLexer::S_SP &&
            !$this->lexer->isNextToken(EmailLexer::GENERIC));

        if ($thereIsNoDomain) {
            throw new NoDomainPart();
        }
    }

    private function checkInvalidTokensAfterAT()
    {
        if ($this->lexer->token['type'] === EmailLexer::S_DOT) {
            throw new DotAtStart();
        }
        if ($this->lexer->token['type'] === EmailLexer::S_HYPHEN) {
            throw new DomainHyphened();
        }
    }

    /**
     * @return string
     */
    public function getDomainPart()
    {
        return $this->domainPart;
    }

    /**
     * @param string $addressLiteral
     * @param int $maxGroups
     */
    public function checkIPV6Tag($addressLiteral, $maxGroups = 8)
    {
        $prev = $this->lexer->getPrevious();
        if ($prev['type'] === EmailLexer::S_COLON) {
            $this->warnings[IPV6ColonEnd::CODE] = new IPV6ColonEnd();
        }

        $IPv6       = substr($addressLiteral, 5);
        //Daniel Marschall's new IPv6 testing strategy
        $matchesIP  = explode(':', $IPv6);
        $groupCount = count($matchesIP);
        $colons     = strpos($IPv6, '::');

        if (count(preg_grep('/^[0-9A-Fa-f]{0,4}$/', $matchesIP, PREG_GREP_INVERT)) !== 0) {
            $this->warnings[IPV6BadChar::CODE] = new IPV6BadChar();
        }

        if ($colons === false) {
            // We need exactly the right number of groups
            if ($groupCount !== $maxGroups) {
                $this->warnings[IPV6GroupCount::CODE] = new IPV6GroupCount();
            }
            return;
        }

        if ($colons !== strrpos($IPv6, '::')) {
            $this->warnings[IPV6DoubleColon::CODE] = new IPV6DoubleColon();
            return;
        }

        if ($colons === 0 || $colons === (strlen($IPv6) - 2)) {
            // RFC 4291 allows :: at the start or end of an address
            //with 7 other groups in addition
            ++$maxGroups;
        }

        if ($groupCount > $maxGroups) {
            $this->warnings[IPV6MaxGroups::CODE] = new IPV6MaxGroups();
        } elseif ($groupCount === $maxGroups) {
            $this->warnings[IPV6Deprecated::CODE] = new IPV6Deprecated();
        }
    }

    /**
     * @return string
     */
    protected function doParseDomainPart()
    {
        $domain = '';
        $label = '';
        $openedParenthesis = 0;
        do {
            $prev = $this->lexer->getPrevious();

            $this->checkNotAllowedChars($this->lexer->token);

            if ($this->lexer->token['type'] === EmailLexer::S_OPENPARENTHESIS) {
                $this->parseComments();
                $openedParenthesis += $this->getOpenedParenthesis();
                $this->lexer->moveNext();
                $tmpPrev = $this->lexer->getPrevious();
                if ($tmpPrev['type'] === EmailLexer::S_CLOSEPARENTHESIS) {
                    $openedParenthesis--;
                }
            }
            if ($this->lexer->token['type'] === EmailLexer::S_CLOSEPARENTHESIS) {
                if ($openedParenthesis === 0) {
                    throw new UnopenedComment();
                } else {
                    $openedParenthesis--;
                }
            }

            $this->checkConsecutiveDots();
            $this->checkDomainPartExceptions($prev);

            if ($this->hasBrackets()) {
                $this->parseDomainLiteral();
            }

            if ($this->lexer->token['type'] === EmailLexer::S_DOT) {
                $this->checkLabelLength($label);
                $label = '';
            } else {
                $label .= $this->lexer->token['value'];
            }

            if ($this->isFWS()) {
                $this->parseFWS();
            }

            $domain .= $this->lexer->token['value'];
            $this->lexer->moveNext();
            if ($this->lexer->token['type'] === EmailLexer::S_SP) {
                throw new CharNotAllowed();
            }
        } while (null !== $this->lexer->token['type']);

        $this->checkLabelLength($label);

        return $domain;
    }

    private function checkNotAllowedChars(array $token)
    {
        $notAllowed = [EmailLexer::S_BACKSLASH => true, EmailLexer::S_SLASH=> true];
        if (isset($notAllowed[$token['type']])) {
            throw new CharNotAllowed();
        }
    }

    /**
     * @return string|false
     */
    protected function parseDomainLiteral()
    {
        if ($this->lexer->isNextToken(EmailLexer::S_COLON)) {
            $this->warnings[IPV6ColonStart::CODE] = new IPV6ColonStart();
        }
        if ($this->lexer->isNextToken(EmailLexer::S_IPV6TAG)) {
            $lexer = clone $this->lexer;
            $lexer->moveNext();
            if ($lexer->isNextToken(EmailLexer::S_DOUBLECOLON)) {
                $this->warnings[IPV6ColonStart::CODE] = new IPV6ColonStart();
            }
        }

        return $this->doParseDomainLiteral();
    }

    /**
     * @return string|false
     */
    protected function doParseDomainLiteral()
    {
        $IPv6TAG = false;
        $addressLiteral = '';
        do {
            if ($this->lexer->token['type'] === EmailLexer::C_NUL) {
                throw new ExpectingDTEXT();
            }

            if ($this->lexer->token['type'] === EmailLexer::INVALID ||
                $this->lexer->token['type'] === EmailLexer::C_DEL   ||
                $this->lexer->token['type'] === EmailLexer::S_LF
            ) {
                $this->warnings[ObsoleteDTEXT::CODE] = new ObsoleteDTEXT();
            }

            if ($this->lexer->isNextTokenAny(array(EmailLexer::S_OPENQBRACKET, EmailLexer::S_OPENBRACKET))) {
                throw new ExpectingDTEXT();
            }

            if ($this->lexer->isNextTokenAny(
                array(EmailLexer::S_HTAB, EmailLexer::S_SP, $this->lexer->token['type'] === EmailLexer::CRLF)
            )) {
                $this->warnings[CFWSWithFWS::CODE] = new CFWSWithFWS();
                $this->parseFWS();
            }

            if ($this->lexer->isNextToken(EmailLexer::S_CR)) {
                throw new CRNoLF();
            }

            if ($this->lexer->token['type'] === EmailLexer::S_BACKSLASH) {
                $this->warnings[ObsoleteDTEXT::CODE] = new ObsoleteDTEXT();
                $addressLiteral .= $this->lexer->token['value'];
                $this->lexer->moveNext();
                $this->validateQuotedPair();
            }
            if ($this->lexer->token['type'] === EmailLexer::S_IPV6TAG) {
                $IPv6TAG = true;
            }
            if ($this->lexer->token['type'] === EmailLexer::S_CLOSEQBRACKET) {
                break;
            }

            $addressLiteral .= $this->lexer->token['value'];

        } while ($this->lexer->moveNext());

        $addressLiteral = str_replace('[', '', $addressLiteral);
        $addressLiteral = $this->checkIPV4Tag($addressLiteral);

        if (false === $addressLiteral) {
            return $addressLiteral;
        }

        if (!$IPv6TAG) {
            $this->warnings[DomainLiteral::CODE] = new DomainLiteral();
            return $addressLiteral;
        }

        $this->warnings[AddressLiteral::CODE] = new AddressLiteral();

        $this->checkIPV6Tag($addressLiteral);

        return $addressLiteral;
    }

    /**
     * @param string $addressLiteral
     *
     * @return string|false
     */
    protected function checkIPV4Tag($addressLiteral)
    {
        $matchesIP  = array();

        // Extract IPv4 part from the end of the address-literal (if there is one)
        if (preg_match(
            '/\\b(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/',
            $addressLiteral,
            $matchesIP
        ) > 0
        ) {
            $index = strrpos($addressLiteral, $matchesIP[0]);
            if ($index === 0) {
                $this->warnings[AddressLiteral::CODE] = new AddressLiteral();
                return false;
            }
            // Convert IPv4 part to IPv6 format for further testing
            $addressLiteral = substr($addressLiteral, 0, (int) $index) . '0:0';
        }

        return $addressLiteral;
    }

    protected function checkDomainPartExceptions(array $prev)
    {
        $invalidDomainTokens = array(
            EmailLexer::S_DQUOTE => true,
            EmailLexer::S_SQUOTE => true,
            EmailLexer::S_BACKTICK => true,
            EmailLexer::S_SEMICOLON => true,
            EmailLexer::S_GREATERTHAN => true,
            EmailLexer::S_LOWERTHAN => true,
        );

        if (isset($invalidDomainTokens[$this->lexer->token['type']])) {
            throw new ExpectingATEXT();
        }

        if ($this->lexer->token['type'] === EmailLexer::S_COMMA) {
            throw new CommaInDomain();
        }

        if ($this->lexer->token['type'] === EmailLexer::S_AT) {
            throw new ConsecutiveAt();
        }

        if ($this->lexer->token['type'] === EmailLexer::S_OPENQBRACKET && $prev['type'] !== EmailLexer::S_AT) {
            throw new ExpectingATEXT();
        }

        if ($this->lexer->token['type'] === EmailLexer::S_HYPHEN && $this->lexer->isNextToken(EmailLexer::S_DOT)) {
            throw new DomainHyphened();
        }

        if ($this->lexer->token['type'] === EmailLexer::S_BACKSLASH
            && $this->lexer->isNextToken(EmailLexer::GENERIC)) {
            throw new ExpectingATEXT();
        }
    }

    /**
     * @return bool
     */
    protected function hasBrackets()
    {
        if ($this->lexer->token['type'] !== EmailLexer::S_OPENBRACKET) {
            return false;
        }

        try {
            $this->lexer->find(EmailLexer::S_CLOSEBRACKET);
        } catch (\RuntimeException $e) {
            throw new ExpectingDomainLiteralClose();
        }

        return true;
    }

    /**
     * @param string $label
     */
    protected function checkLabelLength($label)
    {
        if ($this->isLabelTooLong($label)) {
            $this->warnings[LabelTooLong::CODE] = new LabelTooLong();
        }
    }

    /**
     * @param string $label
     * @return bool
     */
    private function isLabelTooLong($label)
    {
        if (preg_match('/[^\x00-\x7F]/', $label)) {
            idn_to_ascii($label, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46, $idnaInfo);

            return (bool) ($idnaInfo['errors'] & IDNA_ERROR_LABEL_TOO_LONG);
        }

        return strlen($label) > self::LABEL_MAX_LENGTH;
    }

    protected function parseDomainComments()
    {
        $this->isUnclosedComment();
        while (!$this->lexer->isNextToken(EmailLexer::S_CLOSEPARENTHESIS)) {
            $this->warnEscaping();
            $this->lexer->moveNext();
        }

        $this->lexer->moveNext();
        if ($this->lexer->isNextToken(EmailLexer::S_DOT)) {
            throw new ExpectingATEXT();
        }
    }

    protected function addTLDWarnings()
    {
        if ($this->warnings[DomainLiteral::CODE]) {
            $this->warnings[TLD::CODE] = new TLD();
        }
    }
}
