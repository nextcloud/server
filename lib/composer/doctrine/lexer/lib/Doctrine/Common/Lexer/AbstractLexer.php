<?php

declare(strict_types=1);

namespace Doctrine\Common\Lexer;

use ReflectionClass;
use const PREG_SPLIT_DELIM_CAPTURE;
use const PREG_SPLIT_NO_EMPTY;
use const PREG_SPLIT_OFFSET_CAPTURE;
use function implode;
use function in_array;
use function preg_split;
use function sprintf;
use function substr;

/**
 * Base class for writing simple lexers, i.e. for creating small DSLs.
 */
abstract class AbstractLexer
{
    /**
     * Lexer original input string.
     *
     * @var string
     */
    private $input;

    /**
     * Array of scanned tokens.
     *
     * Each token is an associative array containing three items:
     *  - 'value'    : the string value of the token in the input string
     *  - 'type'     : the type of the token (identifier, numeric, string, input
     *                 parameter, none)
     *  - 'position' : the position of the token in the input string
     *
     * @var array
     */
    private $tokens = [];

    /**
     * Current lexer position in input string.
     *
     * @var int
     */
    private $position = 0;

    /**
     * Current peek of current lexer position.
     *
     * @var int
     */
    private $peek = 0;

    /**
     * The next token in the input.
     *
     * @var array|null
     */
    public $lookahead;

    /**
     * The last matched/seen token.
     *
     * @var array|null
     */
    public $token;

    /**
     * Composed regex for input parsing.
     *
     * @var string
     */
    private $regex;

    /**
     * Sets the input data to be tokenized.
     *
     * The Lexer is immediately reset and the new input tokenized.
     * Any unprocessed tokens from any previous input are lost.
     *
     * @param string $input The input to be tokenized.
     *
     * @return void
     */
    public function setInput($input)
    {
        $this->input  = $input;
        $this->tokens = [];

        $this->reset();
        $this->scan($input);
    }

    /**
     * Resets the lexer.
     *
     * @return void
     */
    public function reset()
    {
        $this->lookahead = null;
        $this->token     = null;
        $this->peek      = 0;
        $this->position  = 0;
    }

    /**
     * Resets the peek pointer to 0.
     *
     * @return void
     */
    public function resetPeek()
    {
        $this->peek = 0;
    }

    /**
     * Resets the lexer position on the input to the given position.
     *
     * @param int $position Position to place the lexical scanner.
     *
     * @return void
     */
    public function resetPosition($position = 0)
    {
        $this->position = $position;
    }

    /**
     * Retrieve the original lexer's input until a given position.
     *
     * @param int $position
     *
     * @return string
     */
    public function getInputUntilPosition($position)
    {
        return substr($this->input, 0, $position);
    }

    /**
     * Checks whether a given token matches the current lookahead.
     *
     * @param int|string $token
     *
     * @return bool
     */
    public function isNextToken($token)
    {
        return $this->lookahead !== null && $this->lookahead['type'] === $token;
    }

    /**
     * Checks whether any of the given tokens matches the current lookahead.
     *
     * @param array $tokens
     *
     * @return bool
     */
    public function isNextTokenAny(array $tokens)
    {
        return $this->lookahead !== null && in_array($this->lookahead['type'], $tokens, true);
    }

    /**
     * Moves to the next token in the input string.
     *
     * @return bool
     */
    public function moveNext()
    {
        $this->peek      = 0;
        $this->token     = $this->lookahead;
        $this->lookahead = isset($this->tokens[$this->position])
            ? $this->tokens[$this->position++] : null;

        return $this->lookahead !== null;
    }

    /**
     * Tells the lexer to skip input tokens until it sees a token with the given value.
     *
     * @param string $type The token type to skip until.
     *
     * @return void
     */
    public function skipUntil($type)
    {
        while ($this->lookahead !== null && $this->lookahead['type'] !== $type) {
            $this->moveNext();
        }
    }

    /**
     * Checks if given value is identical to the given token.
     *
     * @param mixed      $value
     * @param int|string $token
     *
     * @return bool
     */
    public function isA($value, $token)
    {
        return $this->getType($value) === $token;
    }

    /**
     * Moves the lookahead token forward.
     *
     * @return array|null The next token or NULL if there are no more tokens ahead.
     */
    public function peek()
    {
        if (isset($this->tokens[$this->position + $this->peek])) {
            return $this->tokens[$this->position + $this->peek++];
        }

        return null;
    }

    /**
     * Peeks at the next token, returns it and immediately resets the peek.
     *
     * @return array|null The next token or NULL if there are no more tokens ahead.
     */
    public function glimpse()
    {
        $peek       = $this->peek();
        $this->peek = 0;

        return $peek;
    }

    /**
     * Scans the input string for tokens.
     *
     * @param string $input A query string.
     *
     * @return void
     */
    protected function scan($input)
    {
        if (! isset($this->regex)) {
            $this->regex = sprintf(
                '/(%s)|%s/%s',
                implode(')|(', $this->getCatchablePatterns()),
                implode('|', $this->getNonCatchablePatterns()),
                $this->getModifiers()
            );
        }

        $flags   = PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_OFFSET_CAPTURE;
        $matches = preg_split($this->regex, $input, -1, $flags);

        if ($matches === false) {
            // Work around https://bugs.php.net/78122
            $matches = [[$input, 0]];
        }

        foreach ($matches as $match) {
            // Must remain before 'value' assignment since it can change content
            $type = $this->getType($match[0]);

            $this->tokens[] = [
                'value' => $match[0],
                'type'  => $type,
                'position' => $match[1],
            ];
        }
    }

    /**
     * Gets the literal for a given token.
     *
     * @param int|string $token
     *
     * @return int|string
     */
    public function getLiteral($token)
    {
        $className = static::class;
        $reflClass = new ReflectionClass($className);
        $constants = $reflClass->getConstants();

        foreach ($constants as $name => $value) {
            if ($value === $token) {
                return $className . '::' . $name;
            }
        }

        return $token;
    }

    /**
     * Regex modifiers
     *
     * @return string
     */
    protected function getModifiers()
    {
        return 'iu';
    }

    /**
     * Lexical catchable patterns.
     *
     * @return array
     */
    abstract protected function getCatchablePatterns();

    /**
     * Lexical non-catchable patterns.
     *
     * @return array
     */
    abstract protected function getNonCatchablePatterns();

    /**
     * Retrieve token type. Also processes the token value if necessary.
     *
     * @param string $value
     *
     * @return int|string|null
     */
    abstract protected function getType(&$value);
}
