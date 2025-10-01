<?php
namespace JmesPath;

/**
 * Tokenizes JMESPath expressions
 */
class Lexer
{
    const T_DOT = 'dot';
    const T_STAR = 'star';
    const T_COMMA = 'comma';
    const T_COLON = 'colon';
    const T_CURRENT = 'current';
    const T_EXPREF = 'expref';
    const T_LPAREN = 'lparen';
    const T_RPAREN = 'rparen';
    const T_LBRACE = 'lbrace';
    const T_RBRACE = 'rbrace';
    const T_LBRACKET = 'lbracket';
    const T_RBRACKET = 'rbracket';
    const T_FLATTEN = 'flatten';
    const T_IDENTIFIER = 'identifier';
    const T_NUMBER = 'number';
    const T_QUOTED_IDENTIFIER = 'quoted_identifier';
    const T_UNKNOWN = 'unknown';
    const T_PIPE = 'pipe';
    const T_OR = 'or';
    const T_AND = 'and';
    const T_NOT = 'not';
    const T_FILTER = 'filter';
    const T_LITERAL = 'literal';
    const T_EOF = 'eof';
    const T_COMPARATOR = 'comparator';

    const STATE_IDENTIFIER = 0;
    const STATE_NUMBER = 1;
    const STATE_SINGLE_CHAR = 2;
    const STATE_WHITESPACE = 3;
    const STATE_STRING_LITERAL = 4;
    const STATE_QUOTED_STRING = 5;
    const STATE_JSON_LITERAL = 6;
    const STATE_LBRACKET = 7;
    const STATE_PIPE = 8;
    const STATE_LT = 9;
    const STATE_GT = 10;
    const STATE_EQ = 11;
    const STATE_NOT = 12;
    const STATE_AND = 13;

    /** @var array We know what token we are consuming based on each char */
    private static $transitionTable = [
        '<'  => self::STATE_LT,
        '>'  => self::STATE_GT,
        '='  => self::STATE_EQ,
        '!'  => self::STATE_NOT,
        '['  => self::STATE_LBRACKET,
        '|'  => self::STATE_PIPE,
        '&'  => self::STATE_AND,
        '`'  => self::STATE_JSON_LITERAL,
        '"'  => self::STATE_QUOTED_STRING,
        "'"  => self::STATE_STRING_LITERAL,
        '-'  => self::STATE_NUMBER,
        '0'  => self::STATE_NUMBER,
        '1'  => self::STATE_NUMBER,
        '2'  => self::STATE_NUMBER,
        '3'  => self::STATE_NUMBER,
        '4'  => self::STATE_NUMBER,
        '5'  => self::STATE_NUMBER,
        '6'  => self::STATE_NUMBER,
        '7'  => self::STATE_NUMBER,
        '8'  => self::STATE_NUMBER,
        '9'  => self::STATE_NUMBER,
        ' '  => self::STATE_WHITESPACE,
        "\t" => self::STATE_WHITESPACE,
        "\n" => self::STATE_WHITESPACE,
        "\r" => self::STATE_WHITESPACE,
        '.'  => self::STATE_SINGLE_CHAR,
        '*'  => self::STATE_SINGLE_CHAR,
        ']'  => self::STATE_SINGLE_CHAR,
        ','  => self::STATE_SINGLE_CHAR,
        ':'  => self::STATE_SINGLE_CHAR,
        '@'  => self::STATE_SINGLE_CHAR,
        '('  => self::STATE_SINGLE_CHAR,
        ')'  => self::STATE_SINGLE_CHAR,
        '{'  => self::STATE_SINGLE_CHAR,
        '}'  => self::STATE_SINGLE_CHAR,
        '_'  => self::STATE_IDENTIFIER,
        'A'  => self::STATE_IDENTIFIER,
        'B'  => self::STATE_IDENTIFIER,
        'C'  => self::STATE_IDENTIFIER,
        'D'  => self::STATE_IDENTIFIER,
        'E'  => self::STATE_IDENTIFIER,
        'F'  => self::STATE_IDENTIFIER,
        'G'  => self::STATE_IDENTIFIER,
        'H'  => self::STATE_IDENTIFIER,
        'I'  => self::STATE_IDENTIFIER,
        'J'  => self::STATE_IDENTIFIER,
        'K'  => self::STATE_IDENTIFIER,
        'L'  => self::STATE_IDENTIFIER,
        'M'  => self::STATE_IDENTIFIER,
        'N'  => self::STATE_IDENTIFIER,
        'O'  => self::STATE_IDENTIFIER,
        'P'  => self::STATE_IDENTIFIER,
        'Q'  => self::STATE_IDENTIFIER,
        'R'  => self::STATE_IDENTIFIER,
        'S'  => self::STATE_IDENTIFIER,
        'T'  => self::STATE_IDENTIFIER,
        'U'  => self::STATE_IDENTIFIER,
        'V'  => self::STATE_IDENTIFIER,
        'W'  => self::STATE_IDENTIFIER,
        'X'  => self::STATE_IDENTIFIER,
        'Y'  => self::STATE_IDENTIFIER,
        'Z'  => self::STATE_IDENTIFIER,
        'a'  => self::STATE_IDENTIFIER,
        'b'  => self::STATE_IDENTIFIER,
        'c'  => self::STATE_IDENTIFIER,
        'd'  => self::STATE_IDENTIFIER,
        'e'  => self::STATE_IDENTIFIER,
        'f'  => self::STATE_IDENTIFIER,
        'g'  => self::STATE_IDENTIFIER,
        'h'  => self::STATE_IDENTIFIER,
        'i'  => self::STATE_IDENTIFIER,
        'j'  => self::STATE_IDENTIFIER,
        'k'  => self::STATE_IDENTIFIER,
        'l'  => self::STATE_IDENTIFIER,
        'm'  => self::STATE_IDENTIFIER,
        'n'  => self::STATE_IDENTIFIER,
        'o'  => self::STATE_IDENTIFIER,
        'p'  => self::STATE_IDENTIFIER,
        'q'  => self::STATE_IDENTIFIER,
        'r'  => self::STATE_IDENTIFIER,
        's'  => self::STATE_IDENTIFIER,
        't'  => self::STATE_IDENTIFIER,
        'u'  => self::STATE_IDENTIFIER,
        'v'  => self::STATE_IDENTIFIER,
        'w'  => self::STATE_IDENTIFIER,
        'x'  => self::STATE_IDENTIFIER,
        'y'  => self::STATE_IDENTIFIER,
        'z'  => self::STATE_IDENTIFIER,
    ];

    /** @var array Valid identifier characters after first character */
    private $validIdentifier = [
        'A' => true, 'B' => true, 'C' => true, 'D' => true, 'E' => true,
        'F' => true, 'G' => true, 'H' => true, 'I' => true, 'J' => true,
        'K' => true, 'L' => true, 'M' => true, 'N' => true, 'O' => true,
        'P' => true, 'Q' => true, 'R' => true, 'S' => true, 'T' => true,
        'U' => true, 'V' => true, 'W' => true, 'X' => true, 'Y' => true,
        'Z' => true, 'a' => true, 'b' => true, 'c' => true, 'd' => true,
        'e' => true, 'f' => true, 'g' => true, 'h' => true, 'i' => true,
        'j' => true, 'k' => true, 'l' => true, 'm' => true, 'n' => true,
        'o' => true, 'p' => true, 'q' => true, 'r' => true, 's' => true,
        't' => true, 'u' => true, 'v' => true, 'w' => true, 'x' => true,
        'y' => true, 'z' => true, '_' => true, '0' => true, '1' => true,
        '2' => true, '3' => true, '4' => true, '5' => true, '6' => true,
        '7' => true, '8' => true, '9' => true,
    ];

    /** @var array Valid number characters after the first character */
    private $numbers = [
        '0' => true, '1' => true, '2' => true, '3' => true, '4' => true,
        '5' => true, '6' => true, '7' => true, '8' => true, '9' => true
    ];

    /** @var array Map of simple single character tokens */
    private $simpleTokens = [
        '.' => self::T_DOT,
        '*' => self::T_STAR,
        ']' => self::T_RBRACKET,
        ',' => self::T_COMMA,
        ':' => self::T_COLON,
        '@' => self::T_CURRENT,
        '(' => self::T_LPAREN,
        ')' => self::T_RPAREN,
        '{' => self::T_LBRACE,
        '}' => self::T_RBRACE,
    ];

    /**
     * Tokenize the JMESPath expression into an array of tokens hashes that
     * contain a 'type', 'value', and 'key'.
     *
     * @param string $input JMESPath input
     *
     * @return array
     * @throws SyntaxErrorException
     */
    public function tokenize($input)
    {
        $tokens = [];

        if ($input === '') {
            goto eof;
        }

        $chars = str_split($input);

        while (false !== ($current = current($chars))) {

            // Every character must be in the transition character table.
            if (!isset(self::$transitionTable[$current])) {
                $tokens[] = [
                    'type'  => self::T_UNKNOWN,
                    'pos'   => key($chars),
                    'value' => $current
                ];
                next($chars);
                continue;
            }

            $state = self::$transitionTable[$current];

            if ($state === self::STATE_SINGLE_CHAR) {

                // Consume simple tokens like ".", ",", "@", etc.
                $tokens[] = [
                    'type'  => $this->simpleTokens[$current],
                    'pos'   => key($chars),
                    'value' => $current
                ];
                next($chars);

            } elseif ($state === self::STATE_IDENTIFIER) {

                // Consume identifiers
                $start = key($chars);
                $buffer = '';
                do {
                    $buffer .= $current;
                    $current = next($chars);
                } while ($current !== false && isset($this->validIdentifier[$current]));
                $tokens[] = [
                    'type'  => self::T_IDENTIFIER,
                    'value' => $buffer,
                    'pos'   => $start
                ];

            } elseif ($state === self::STATE_WHITESPACE) {

                // Skip whitespace
                next($chars);

            } elseif ($state === self::STATE_LBRACKET) {

                // Consume "[", "[?", and "[]"
                $position = key($chars);
                $actual = next($chars);
                if ($actual === ']') {
                    next($chars);
                    $tokens[] = [
                        'type'  => self::T_FLATTEN,
                        'pos'   => $position,
                        'value' => '[]'
                    ];
                } elseif ($actual === '?') {
                    next($chars);
                    $tokens[] = [
                        'type'  => self::T_FILTER,
                        'pos'   => $position,
                        'value' => '[?'
                    ];
                } else {
                    $tokens[] = [
                        'type'  => self::T_LBRACKET,
                        'pos'   => $position,
                        'value' => '['
                    ];
                }

            } elseif ($state === self::STATE_STRING_LITERAL) {

                // Consume raw string literals
                $t = $this->inside($chars, "'", self::T_LITERAL);
                $t['value'] = str_replace("\\'", "'", $t['value']);
                $tokens[] = $t;

            } elseif ($state === self::STATE_PIPE) {

                // Consume pipe and OR
                $tokens[] = $this->matchOr($chars, '|', '|', self::T_OR, self::T_PIPE);

            } elseif ($state == self::STATE_JSON_LITERAL) {

                // Consume JSON literals
                $token = $this->inside($chars, '`', self::T_LITERAL);
                if ($token['type'] === self::T_LITERAL) {
                    $token['value'] = str_replace('\\`', '`', $token['value']);
                    $token = $this->parseJson($token);
                }
                $tokens[] = $token;

            } elseif ($state == self::STATE_NUMBER) {

                // Consume numbers
                $start = key($chars);
                $buffer = '';
                do {
                    $buffer .= $current;
                    $current = next($chars);
                } while ($current !== false && isset($this->numbers[$current]));
                $tokens[] = [
                    'type'  => self::T_NUMBER,
                    'value' => (int)$buffer,
                    'pos'   => $start
                ];

            } elseif ($state === self::STATE_QUOTED_STRING) {

                // Consume quoted identifiers
                $token = $this->inside($chars, '"', self::T_QUOTED_IDENTIFIER);
                if ($token['type'] === self::T_QUOTED_IDENTIFIER) {
                    $token['value'] = '"' . $token['value'] . '"';
                    $token = $this->parseJson($token);
                }
                $tokens[] = $token;

            } elseif ($state === self::STATE_EQ) {

                // Consume equals
                $tokens[] = $this->matchOr($chars, '=', '=', self::T_COMPARATOR, self::T_UNKNOWN);

            } elseif ($state == self::STATE_AND) {

                $tokens[] = $this->matchOr($chars, '&', '&', self::T_AND, self::T_EXPREF);

            } elseif ($state === self::STATE_NOT) {

                // Consume not equal
                $tokens[] = $this->matchOr($chars, '!', '=', self::T_COMPARATOR, self::T_NOT);

            } else {

                // either '<' or '>'
                // Consume less than and greater than
                $tokens[] = $this->matchOr($chars, $current, '=', self::T_COMPARATOR, self::T_COMPARATOR);

            }
        }

        eof:
        $tokens[] = [
            'type'  => self::T_EOF,
            'pos'   => mb_strlen($input, 'UTF-8'),
            'value' => null
        ];

        return $tokens;
    }

    /**
     * Returns a token based on whether or not the next token matches the
     * expected value. If it does, a token of "$type" is returned. Otherwise,
     * a token of "$orElse" type is returned.
     *
     * @param array  $chars    Array of characters by reference.
     * @param string $current  The current character.
     * @param string $expected Expected character.
     * @param string $type     Expected result type.
     * @param string $orElse   Otherwise return a token of this type.
     *
     * @return array Returns a conditional token.
     */
    private function matchOr(array &$chars, $current, $expected, $type, $orElse)
    {
        if (next($chars) === $expected) {
            next($chars);
            return [
                'type'  => $type,
                'pos'   => key($chars) - 1,
                'value' => $current . $expected
            ];
        }

        return [
            'type'  => $orElse,
            'pos'   => key($chars) - 1,
            'value' => $current
        ];
    }

    /**
     * Returns a token the is the result of consuming inside of delimiter
     * characters. Escaped delimiters will be adjusted before returning a
     * value. If the token is not closed, "unknown" is returned.
     *
     * @param array  $chars Array of characters by reference.
     * @param string $delim The delimiter character.
     * @param string $type  Token type.
     *
     * @return array Returns the consumed token.
     */
    private function inside(array &$chars, $delim, $type)
    {
        $position = key($chars);
        $current = next($chars);
        $buffer = '';

        while ($current !== $delim) {
            if ($current === '\\') {
                $buffer .= '\\';
                $current = next($chars);
            }
            if ($current === false) {
                // Unclosed delimiter
                return [
                    'type'  => self::T_UNKNOWN,
                    'value' => $buffer,
                    'pos'   => $position
                ];
            }
            $buffer .= $current;
            $current = next($chars);
        }

        next($chars);

        return ['type' => $type, 'value' => $buffer, 'pos' => $position];
    }

    /**
     * Parses a JSON token or sets the token type to "unknown" on error.
     *
     * @param array $token Token that needs parsing.
     *
     * @return array Returns a token with a parsed value.
     */
    private function parseJson(array $token)
    {
        $value = json_decode($token['value'], true);

        if ($error = json_last_error()) {
            // Legacy support for elided quotes. Try to parse again by adding
            // quotes around the bad input value.
            $value = json_decode('"' . $token['value'] . '"', true);
            if ($error = json_last_error()) {
                $token['type'] = self::T_UNKNOWN;
                return $token;
            }
        }

        $token['value'] = $value;
        return $token;
    }
}
