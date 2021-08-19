<?php declare(strict_types=1);

namespace PhpParser\Lexer\TokenEmulator;

use PhpParser\Lexer\Emulative;

final class NullsafeTokenEmulator extends TokenEmulator
{
    public function getPhpVersion(): string
    {
        return Emulative::PHP_8_0;
    }

    public function isEmulationNeeded(string $code): bool
    {
        return strpos($code, '?->') !== false;
    }

    public function emulate(string $code, array $tokens): array
    {
        // We need to manually iterate and manage a count because we'll change
        // the tokens array on the way
        $line = 1;
        for ($i = 0, $c = count($tokens); $i < $c; ++$i) {
            if ($tokens[$i] === '?' && isset($tokens[$i + 1]) && $tokens[$i + 1][0] === \T_OBJECT_OPERATOR) {
                array_splice($tokens, $i, 2, [
                    [\T_NULLSAFE_OBJECT_OPERATOR, '?->', $line]
                ]);
                $c--;
                continue;
            }

            // Handle ?-> inside encapsed string.
            if ($tokens[$i][0] === \T_ENCAPSED_AND_WHITESPACE && isset($tokens[$i - 1])
                && $tokens[$i - 1][0] === \T_VARIABLE
                && preg_match('/^\?->([a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*)/', $tokens[$i][1], $matches)
            ) {
                $replacement = [
                    [\T_NULLSAFE_OBJECT_OPERATOR, '?->', $line],
                    [\T_STRING, $matches[1], $line],
                ];
                if (\strlen($matches[0]) !== \strlen($tokens[$i][1])) {
                    $replacement[] = [
                        \T_ENCAPSED_AND_WHITESPACE,
                        \substr($tokens[$i][1], \strlen($matches[0])),
                        $line
                    ];
                }
                array_splice($tokens, $i, 1, $replacement);
                $c += \count($replacement) - 1;
                continue;
            }

            if (\is_array($tokens[$i])) {
                $line += substr_count($tokens[$i][1], "\n");
            }
        }

        return $tokens;
    }

    public function reverseEmulate(string $code, array $tokens): array
    {
        // ?-> was not valid code previously, don't bother.
        return $tokens;
    }
}
