<?php declare(strict_types=1);

namespace PhpParser;

use PhpParser\Parser\Tokens;

class Lexer
{
    protected $code;
    protected $tokens;
    protected $pos;
    protected $line;
    protected $filePos;
    protected $prevCloseTagHasNewline;

    protected $tokenMap;
    protected $dropTokens;
    protected $identifierTokens;

    private $attributeStartLineUsed;
    private $attributeEndLineUsed;
    private $attributeStartTokenPosUsed;
    private $attributeEndTokenPosUsed;
    private $attributeStartFilePosUsed;
    private $attributeEndFilePosUsed;
    private $attributeCommentsUsed;

    /**
     * Creates a Lexer.
     *
     * @param array $options Options array. Currently only the 'usedAttributes' option is supported,
     *                       which is an array of attributes to add to the AST nodes. Possible
     *                       attributes are: 'comments', 'startLine', 'endLine', 'startTokenPos',
     *                       'endTokenPos', 'startFilePos', 'endFilePos'. The option defaults to the
     *                       first three. For more info see getNextToken() docs.
     */
    public function __construct(array $options = []) {
        // Create Map from internal tokens to PhpParser tokens.
        $this->defineCompatibilityTokens();
        $this->tokenMap = $this->createTokenMap();
        $this->identifierTokens = $this->createIdentifierTokenMap();

        // map of tokens to drop while lexing (the map is only used for isset lookup,
        // that's why the value is simply set to 1; the value is never actually used.)
        $this->dropTokens = array_fill_keys(
            [\T_WHITESPACE, \T_OPEN_TAG, \T_COMMENT, \T_DOC_COMMENT, \T_BAD_CHARACTER], 1
        );

        $defaultAttributes = ['comments', 'startLine', 'endLine'];
        $usedAttributes = array_fill_keys($options['usedAttributes'] ?? $defaultAttributes, true);

        // Create individual boolean properties to make these checks faster.
        $this->attributeStartLineUsed = isset($usedAttributes['startLine']);
        $this->attributeEndLineUsed = isset($usedAttributes['endLine']);
        $this->attributeStartTokenPosUsed = isset($usedAttributes['startTokenPos']);
        $this->attributeEndTokenPosUsed = isset($usedAttributes['endTokenPos']);
        $this->attributeStartFilePosUsed = isset($usedAttributes['startFilePos']);
        $this->attributeEndFilePosUsed = isset($usedAttributes['endFilePos']);
        $this->attributeCommentsUsed = isset($usedAttributes['comments']);
    }

    /**
     * Initializes the lexer for lexing the provided source code.
     *
     * This function does not throw if lexing errors occur. Instead, errors may be retrieved using
     * the getErrors() method.
     *
     * @param string $code The source code to lex
     * @param ErrorHandler|null $errorHandler Error handler to use for lexing errors. Defaults to
     *                                        ErrorHandler\Throwing
     */
    public function startLexing(string $code, ErrorHandler $errorHandler = null) {
        if (null === $errorHandler) {
            $errorHandler = new ErrorHandler\Throwing();
        }

        $this->code = $code; // keep the code around for __halt_compiler() handling
        $this->pos  = -1;
        $this->line =  1;
        $this->filePos = 0;

        // If inline HTML occurs without preceding code, treat it as if it had a leading newline.
        // This ensures proper composability, because having a newline is the "safe" assumption.
        $this->prevCloseTagHasNewline = true;

        $scream = ini_set('xdebug.scream', '0');

        error_clear_last();
        $this->tokens = @token_get_all($code);
        $this->postprocessTokens($errorHandler);

        if (false !== $scream) {
            ini_set('xdebug.scream', $scream);
        }
    }

    private function handleInvalidCharacterRange($start, $end, $line, ErrorHandler $errorHandler) {
        $tokens = [];
        for ($i = $start; $i < $end; $i++) {
            $chr = $this->code[$i];
            if ($chr === "\0") {
                // PHP cuts error message after null byte, so need special case
                $errorMsg = 'Unexpected null byte';
            } else {
                $errorMsg = sprintf(
                    'Unexpected character "%s" (ASCII %d)', $chr, ord($chr)
                );
            }

            $tokens[] = [\T_BAD_CHARACTER, $chr, $line];
            $errorHandler->handleError(new Error($errorMsg, [
                'startLine' => $line,
                'endLine' => $line,
                'startFilePos' => $i,
                'endFilePos' => $i,
            ]));
        }
        return $tokens;
    }

    /**
     * Check whether comment token is unterminated.
     *
     * @return bool
     */
    private function isUnterminatedComment($token) : bool {
        return ($token[0] === \T_COMMENT || $token[0] === \T_DOC_COMMENT)
            && substr($token[1], 0, 2) === '/*'
            && substr($token[1], -2) !== '*/';
    }

    protected function postprocessTokens(ErrorHandler $errorHandler) {
        // PHP's error handling for token_get_all() is rather bad, so if we want detailed
        // error information we need to compute it ourselves. Invalid character errors are
        // detected by finding "gaps" in the token array. Unterminated comments are detected
        // by checking if a trailing comment has a "*/" at the end.
        //
        // Additionally, we canonicalize to the PHP 8 comment format here, which does not include
        // the trailing whitespace anymore.
        //
        // We also canonicalize to the PHP 8 T_NAME_* tokens.

        $filePos = 0;
        $line = 1;
        $numTokens = \count($this->tokens);
        for ($i = 0; $i < $numTokens; $i++) {
            $token = $this->tokens[$i];

            // Since PHP 7.4 invalid characters are represented by a T_BAD_CHARACTER token.
            // In this case we only need to emit an error.
            if ($token[0] === \T_BAD_CHARACTER) {
                $this->handleInvalidCharacterRange($filePos, $filePos + 1, $line, $errorHandler);
            }

            if ($token[0] === \T_COMMENT && substr($token[1], 0, 2) !== '/*'
                    && preg_match('/(\r\n|\n|\r)$/D', $token[1], $matches)) {
                $trailingNewline = $matches[0];
                $token[1] = substr($token[1], 0, -strlen($trailingNewline));
                $this->tokens[$i] = $token;
                if (isset($this->tokens[$i + 1]) && $this->tokens[$i + 1][0] === \T_WHITESPACE) {
                    // Move trailing newline into following T_WHITESPACE token, if it already exists.
                    $this->tokens[$i + 1][1] = $trailingNewline . $this->tokens[$i + 1][1];
                    $this->tokens[$i + 1][2]--;
                } else {
                    // Otherwise, we need to create a new T_WHITESPACE token.
                    array_splice($this->tokens, $i + 1, 0, [
                        [\T_WHITESPACE, $trailingNewline, $line],
                    ]);
                    $numTokens++;
                }
            }

            // Emulate PHP 8 T_NAME_* tokens, by combining sequences of T_NS_SEPARATOR and T_STRING
            // into a single token.
            if (\is_array($token)
                    && ($token[0] === \T_NS_SEPARATOR || isset($this->identifierTokens[$token[0]]))) {
                $lastWasSeparator = $token[0] === \T_NS_SEPARATOR;
                $text = $token[1];
                for ($j = $i + 1; isset($this->tokens[$j]); $j++) {
                    if ($lastWasSeparator) {
                        if (!isset($this->identifierTokens[$this->tokens[$j][0]])) {
                            break;
                        }
                        $lastWasSeparator = false;
                    } else {
                        if ($this->tokens[$j][0] !== \T_NS_SEPARATOR) {
                            break;
                        }
                        $lastWasSeparator = true;
                    }
                    $text .= $this->tokens[$j][1];
                }
                if ($lastWasSeparator) {
                    // Trailing separator is not part of the name.
                    $j--;
                    $text = substr($text, 0, -1);
                }
                if ($j > $i + 1) {
                    if ($token[0] === \T_NS_SEPARATOR) {
                        $type = \T_NAME_FULLY_QUALIFIED;
                    } else if ($token[0] === \T_NAMESPACE) {
                        $type = \T_NAME_RELATIVE;
                    } else {
                        $type = \T_NAME_QUALIFIED;
                    }
                    $token = [$type, $text, $line];
                    array_splice($this->tokens, $i, $j - $i, [$token]);
                    $numTokens -= $j - $i - 1;
                }
            }

            $tokenValue = \is_string($token) ? $token : $token[1];
            $tokenLen = \strlen($tokenValue);

            if (substr($this->code, $filePos, $tokenLen) !== $tokenValue) {
                // Something is missing, must be an invalid character
                $nextFilePos = strpos($this->code, $tokenValue, $filePos);
                $badCharTokens = $this->handleInvalidCharacterRange(
                    $filePos, $nextFilePos, $line, $errorHandler);
                $filePos = (int) $nextFilePos;

                array_splice($this->tokens, $i, 0, $badCharTokens);
                $numTokens += \count($badCharTokens);
                $i += \count($badCharTokens);
            }

            $filePos += $tokenLen;
            $line += substr_count($tokenValue, "\n");
        }

        if ($filePos !== \strlen($this->code)) {
            if (substr($this->code, $filePos, 2) === '/*') {
                // Unlike PHP, HHVM will drop unterminated comments entirely
                $comment = substr($this->code, $filePos);
                $errorHandler->handleError(new Error('Unterminated comment', [
                    'startLine' => $line,
                    'endLine' => $line + substr_count($comment, "\n"),
                    'startFilePos' => $filePos,
                    'endFilePos' => $filePos + \strlen($comment),
                ]));

                // Emulate the PHP behavior
                $isDocComment = isset($comment[3]) && $comment[3] === '*';
                $this->tokens[] = [$isDocComment ? \T_DOC_COMMENT : \T_COMMENT, $comment, $line];
            } else {
                // Invalid characters at the end of the input
                $badCharTokens = $this->handleInvalidCharacterRange(
                    $filePos, \strlen($this->code), $line, $errorHandler);
                $this->tokens = array_merge($this->tokens, $badCharTokens);
            }
            return;
        }

        if (count($this->tokens) > 0) {
            // Check for unterminated comment
            $lastToken = $this->tokens[count($this->tokens) - 1];
            if ($this->isUnterminatedComment($lastToken)) {
                $errorHandler->handleError(new Error('Unterminated comment', [
                    'startLine' => $line - substr_count($lastToken[1], "\n"),
                    'endLine' => $line,
                    'startFilePos' => $filePos - \strlen($lastToken[1]),
                    'endFilePos' => $filePos,
                ]));
            }
        }
    }

    /**
     * Fetches the next token.
     *
     * The available attributes are determined by the 'usedAttributes' option, which can
     * be specified in the constructor. The following attributes are supported:
     *
     *  * 'comments'      => Array of PhpParser\Comment or PhpParser\Comment\Doc instances,
     *                       representing all comments that occurred between the previous
     *                       non-discarded token and the current one.
     *  * 'startLine'     => Line in which the node starts.
     *  * 'endLine'       => Line in which the node ends.
     *  * 'startTokenPos' => Offset into the token array of the first token in the node.
     *  * 'endTokenPos'   => Offset into the token array of the last token in the node.
     *  * 'startFilePos'  => Offset into the code string of the first character that is part of the node.
     *  * 'endFilePos'    => Offset into the code string of the last character that is part of the node.
     *
     * @param mixed $value           Variable to store token content in
     * @param mixed $startAttributes Variable to store start attributes in
     * @param mixed $endAttributes   Variable to store end attributes in
     *
     * @return int Token id
     */
    public function getNextToken(&$value = null, &$startAttributes = null, &$endAttributes = null) : int {
        $startAttributes = [];
        $endAttributes   = [];

        while (1) {
            if (isset($this->tokens[++$this->pos])) {
                $token = $this->tokens[$this->pos];
            } else {
                // EOF token with ID 0
                $token = "\0";
            }

            if ($this->attributeStartLineUsed) {
                $startAttributes['startLine'] = $this->line;
            }
            if ($this->attributeStartTokenPosUsed) {
                $startAttributes['startTokenPos'] = $this->pos;
            }
            if ($this->attributeStartFilePosUsed) {
                $startAttributes['startFilePos'] = $this->filePos;
            }

            if (\is_string($token)) {
                $value = $token;
                if (isset($token[1])) {
                    // bug in token_get_all
                    $this->filePos += 2;
                    $id = ord('"');
                } else {
                    $this->filePos += 1;
                    $id = ord($token);
                }
            } elseif (!isset($this->dropTokens[$token[0]])) {
                $value = $token[1];
                $id = $this->tokenMap[$token[0]];
                if (\T_CLOSE_TAG === $token[0]) {
                    $this->prevCloseTagHasNewline = false !== strpos($token[1], "\n");
                } elseif (\T_INLINE_HTML === $token[0]) {
                    $startAttributes['hasLeadingNewline'] = $this->prevCloseTagHasNewline;
                }

                $this->line += substr_count($value, "\n");
                $this->filePos += \strlen($value);
            } else {
                $origLine = $this->line;
                $origFilePos = $this->filePos;
                $this->line += substr_count($token[1], "\n");
                $this->filePos += \strlen($token[1]);

                if (\T_COMMENT === $token[0] || \T_DOC_COMMENT === $token[0]) {
                    if ($this->attributeCommentsUsed) {
                        $comment = \T_DOC_COMMENT === $token[0]
                            ? new Comment\Doc($token[1],
                                $origLine, $origFilePos, $this->pos,
                                $this->line, $this->filePos - 1, $this->pos)
                            : new Comment($token[1],
                                $origLine, $origFilePos, $this->pos,
                                $this->line, $this->filePos - 1, $this->pos);
                        $startAttributes['comments'][] = $comment;
                    }
                }
                continue;
            }

            if ($this->attributeEndLineUsed) {
                $endAttributes['endLine'] = $this->line;
            }
            if ($this->attributeEndTokenPosUsed) {
                $endAttributes['endTokenPos'] = $this->pos;
            }
            if ($this->attributeEndFilePosUsed) {
                $endAttributes['endFilePos'] = $this->filePos - 1;
            }

            return $id;
        }

        throw new \RuntimeException('Reached end of lexer loop');
    }

    /**
     * Returns the token array for current code.
     *
     * The token array is in the same format as provided by the
     * token_get_all() function and does not discard tokens (i.e.
     * whitespace and comments are included). The token position
     * attributes are against this token array.
     *
     * @return array Array of tokens in token_get_all() format
     */
    public function getTokens() : array {
        return $this->tokens;
    }

    /**
     * Handles __halt_compiler() by returning the text after it.
     *
     * @return string Remaining text
     */
    public function handleHaltCompiler() : string {
        // text after T_HALT_COMPILER, still including ();
        $textAfter = substr($this->code, $this->filePos);

        // ensure that it is followed by ();
        // this simplifies the situation, by not allowing any comments
        // in between of the tokens.
        if (!preg_match('~^\s*\(\s*\)\s*(?:;|\?>\r?\n?)~', $textAfter, $matches)) {
            throw new Error('__HALT_COMPILER must be followed by "();"');
        }

        // prevent the lexer from returning any further tokens
        $this->pos = count($this->tokens);

        // return with (); removed
        return substr($textAfter, strlen($matches[0]));
    }

    private function defineCompatibilityTokens() {
        static $compatTokensDefined = false;
        if ($compatTokensDefined) {
            return;
        }

        $compatTokens = [
            // PHP 7.4
            'T_BAD_CHARACTER',
            'T_FN',
            'T_COALESCE_EQUAL',
            // PHP 8.0
            'T_NAME_QUALIFIED',
            'T_NAME_FULLY_QUALIFIED',
            'T_NAME_RELATIVE',
            'T_MATCH',
            'T_NULLSAFE_OBJECT_OPERATOR',
            'T_ATTRIBUTE',
        ];

        // PHP-Parser might be used together with another library that also emulates some or all
        // of these tokens. Perform a sanity-check that all already defined tokens have been
        // assigned a unique ID.
        $usedTokenIds = [];
        foreach ($compatTokens as $token) {
            if (\defined($token)) {
                $tokenId = \constant($token);
                $clashingToken = $usedTokenIds[$tokenId] ?? null;
                if ($clashingToken !== null) {
                    throw new \Error(sprintf(
                        'Token %s has same ID as token %s, ' .
                        'you may be using a library with broken token emulation',
                        $token, $clashingToken
                    ));
                }
                $usedTokenIds[$tokenId] = $token;
            }
        }

        // Now define any tokens that have not yet been emulated. Try to assign IDs from -1
        // downwards, but skip any IDs that may already be in use.
        $newTokenId = -1;
        foreach ($compatTokens as $token) {
            if (!\defined($token)) {
                while (isset($usedTokenIds[$newTokenId])) {
                    $newTokenId--;
                }
                \define($token, $newTokenId);
                $newTokenId--;
            }
        }

        $compatTokensDefined = true;
    }

    /**
     * Creates the token map.
     *
     * The token map maps the PHP internal token identifiers
     * to the identifiers used by the Parser. Additionally it
     * maps T_OPEN_TAG_WITH_ECHO to T_ECHO and T_CLOSE_TAG to ';'.
     *
     * @return array The token map
     */
    protected function createTokenMap() : array {
        $tokenMap = [];

        // 256 is the minimum possible token number, as everything below
        // it is an ASCII value
        for ($i = 256; $i < 1000; ++$i) {
            if (\T_DOUBLE_COLON === $i) {
                // T_DOUBLE_COLON is equivalent to T_PAAMAYIM_NEKUDOTAYIM
                $tokenMap[$i] = Tokens::T_PAAMAYIM_NEKUDOTAYIM;
            } elseif(\T_OPEN_TAG_WITH_ECHO === $i) {
                // T_OPEN_TAG_WITH_ECHO with dropped T_OPEN_TAG results in T_ECHO
                $tokenMap[$i] = Tokens::T_ECHO;
            } elseif(\T_CLOSE_TAG === $i) {
                // T_CLOSE_TAG is equivalent to ';'
                $tokenMap[$i] = ord(';');
            } elseif ('UNKNOWN' !== $name = token_name($i)) {
                if ('T_HASHBANG' === $name) {
                    // HHVM uses a special token for #! hashbang lines
                    $tokenMap[$i] = Tokens::T_INLINE_HTML;
                } elseif (defined($name = Tokens::class . '::' . $name)) {
                    // Other tokens can be mapped directly
                    $tokenMap[$i] = constant($name);
                }
            }
        }

        // HHVM uses a special token for numbers that overflow to double
        if (defined('T_ONUMBER')) {
            $tokenMap[\T_ONUMBER] = Tokens::T_DNUMBER;
        }
        // HHVM also has a separate token for the __COMPILER_HALT_OFFSET__ constant
        if (defined('T_COMPILER_HALT_OFFSET')) {
            $tokenMap[\T_COMPILER_HALT_OFFSET] = Tokens::T_STRING;
        }

        // Assign tokens for which we define compatibility constants, as token_name() does not know them.
        $tokenMap[\T_FN] = Tokens::T_FN;
        $tokenMap[\T_COALESCE_EQUAL] = Tokens::T_COALESCE_EQUAL;
        $tokenMap[\T_NAME_QUALIFIED] = Tokens::T_NAME_QUALIFIED;
        $tokenMap[\T_NAME_FULLY_QUALIFIED] = Tokens::T_NAME_FULLY_QUALIFIED;
        $tokenMap[\T_NAME_RELATIVE] = Tokens::T_NAME_RELATIVE;
        $tokenMap[\T_MATCH] = Tokens::T_MATCH;
        $tokenMap[\T_NULLSAFE_OBJECT_OPERATOR] = Tokens::T_NULLSAFE_OBJECT_OPERATOR;
        $tokenMap[\T_ATTRIBUTE] = Tokens::T_ATTRIBUTE;

        return $tokenMap;
    }

    private function createIdentifierTokenMap(): array {
        // Based on semi_reserved production.
        return array_fill_keys([
            \T_STRING,
            \T_STATIC, \T_ABSTRACT, \T_FINAL, \T_PRIVATE, \T_PROTECTED, \T_PUBLIC,
            \T_INCLUDE, \T_INCLUDE_ONCE, \T_EVAL, \T_REQUIRE, \T_REQUIRE_ONCE, \T_LOGICAL_OR, \T_LOGICAL_XOR, \T_LOGICAL_AND,
            \T_INSTANCEOF, \T_NEW, \T_CLONE, \T_EXIT, \T_IF, \T_ELSEIF, \T_ELSE, \T_ENDIF, \T_ECHO, \T_DO, \T_WHILE,
            \T_ENDWHILE, \T_FOR, \T_ENDFOR, \T_FOREACH, \T_ENDFOREACH, \T_DECLARE, \T_ENDDECLARE, \T_AS, \T_TRY, \T_CATCH,
            \T_FINALLY, \T_THROW, \T_USE, \T_INSTEADOF, \T_GLOBAL, \T_VAR, \T_UNSET, \T_ISSET, \T_EMPTY, \T_CONTINUE, \T_GOTO,
            \T_FUNCTION, \T_CONST, \T_RETURN, \T_PRINT, \T_YIELD, \T_LIST, \T_SWITCH, \T_ENDSWITCH, \T_CASE, \T_DEFAULT,
            \T_BREAK, \T_ARRAY, \T_CALLABLE, \T_EXTENDS, \T_IMPLEMENTS, \T_NAMESPACE, \T_TRAIT, \T_INTERFACE, \T_CLASS,
            \T_CLASS_C, \T_TRAIT_C, \T_FUNC_C, \T_METHOD_C, \T_LINE, \T_FILE, \T_DIR, \T_NS_C, \T_HALT_COMPILER, \T_FN,
            \T_MATCH,
        ], true);
    }
}
