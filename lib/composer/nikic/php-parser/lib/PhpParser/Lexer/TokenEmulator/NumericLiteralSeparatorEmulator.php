<?php declare(strict_types=1);

namespace PhpParser\Lexer\TokenEmulator;

use PhpParser\Lexer\Emulative;

final class NumericLiteralSeparatorEmulator extends TokenEmulator
{
    const BIN = '(?:0b[01]+(?:_[01]+)*)';
    const HEX = '(?:0x[0-9a-f]+(?:_[0-9a-f]+)*)';
    const DEC = '(?:[0-9]+(?:_[0-9]+)*)';
    const SIMPLE_FLOAT = '(?:' . self::DEC . '\.' . self::DEC . '?|\.' . self::DEC . ')';
    const EXP = '(?:e[+-]?' . self::DEC . ')';
    const FLOAT = '(?:' . self::SIMPLE_FLOAT . self::EXP . '?|' . self::DEC . self::EXP . ')';
    const NUMBER = '~' . self::FLOAT . '|' . self::BIN . '|' . self::HEX . '|' . self::DEC . '~iA';

    public function getPhpVersion(): string
    {
        return Emulative::PHP_7_4;
    }

    public function isEmulationNeeded(string $code) : bool
    {
        return preg_match('~[0-9]_[0-9]~', $code)
            || preg_match('~0x[0-9a-f]+_[0-9a-f]~i', $code);
    }

    public function emulate(string $code, array $tokens): array
    {
        // We need to manually iterate and manage a count because we'll change
        // the tokens array on the way
        $codeOffset = 0;
        for ($i = 0, $c = count($tokens); $i < $c; ++$i) {
            $token = $tokens[$i];
            $tokenLen = \strlen(\is_array($token) ? $token[1] : $token);

            if ($token[0] !== T_LNUMBER && $token[0] !== T_DNUMBER) {
                $codeOffset += $tokenLen;
                continue;
            }

            $res = preg_match(self::NUMBER, $code, $matches, 0, $codeOffset);
            assert($res, "No number at number token position");

            $match = $matches[0];
            $matchLen = \strlen($match);
            if ($matchLen === $tokenLen) {
                // Original token already holds the full number.
                $codeOffset += $tokenLen;
                continue;
            }

            $tokenKind = $this->resolveIntegerOrFloatToken($match);
            $newTokens = [[$tokenKind, $match, $token[2]]];

            $numTokens = 1;
            $len = $tokenLen;
            while ($matchLen > $len) {
                $nextToken = $tokens[$i + $numTokens];
                $nextTokenText = \is_array($nextToken) ? $nextToken[1] : $nextToken;
                $nextTokenLen = \strlen($nextTokenText);

                $numTokens++;
                if ($matchLen < $len + $nextTokenLen) {
                    // Split trailing characters into a partial token.
                    assert(is_array($nextToken), "Partial token should be an array token");
                    $partialText = substr($nextTokenText, $matchLen - $len);
                    $newTokens[] = [$nextToken[0], $partialText, $nextToken[2]];
                    break;
                }

                $len += $nextTokenLen;
            }

            array_splice($tokens, $i, $numTokens, $newTokens);
            $c -= $numTokens - \count($newTokens);
            $codeOffset += $matchLen;
        }

        return $tokens;
    }

    private function resolveIntegerOrFloatToken(string $str): int
    {
        $str = str_replace('_', '', $str);

        if (stripos($str, '0b') === 0) {
            $num = bindec($str);
        } elseif (stripos($str, '0x') === 0) {
            $num = hexdec($str);
        } elseif (stripos($str, '0') === 0 && ctype_digit($str)) {
            $num = octdec($str);
        } else {
            $num = +$str;
        }

        return is_float($num) ? T_DNUMBER : T_LNUMBER;
    }

    public function reverseEmulate(string $code, array $tokens): array
    {
        // Numeric separators were not legal code previously, don't bother.
        return $tokens;
    }
}
