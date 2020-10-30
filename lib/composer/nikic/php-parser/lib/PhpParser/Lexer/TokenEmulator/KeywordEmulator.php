<?php declare(strict_types=1);

namespace PhpParser\Lexer\TokenEmulator;

abstract class KeywordEmulator extends TokenEmulator
{
    abstract function getKeywordString(): string;
    abstract function getKeywordToken(): int;

    public function isEmulationNeeded(string $code): bool
    {
        return strpos(strtolower($code), $this->getKeywordString()) !== false;
    }

    public function emulate(string $code, array $tokens): array
    {
        $keywordString = $this->getKeywordString();
        foreach ($tokens as $i => $token) {
            if ($token[0] === T_STRING && strtolower($token[1]) === $keywordString) {
                $previousNonSpaceToken = $this->getPreviousNonSpaceToken($tokens, $i);
                if ($previousNonSpaceToken !== null && $previousNonSpaceToken[0] === \T_OBJECT_OPERATOR) {
                    continue;
                }

                $tokens[$i][0] = $this->getKeywordToken();
            }
        }

        return $tokens;
    }

    /**
     * @param mixed[] $tokens
     * @return mixed[]|null
     */
    private function getPreviousNonSpaceToken(array $tokens, int $start)
    {
        for ($i = $start - 1; $i >= 0; --$i) {
            if ($tokens[$i][0] === T_WHITESPACE) {
                continue;
            }

            return $tokens[$i];
        }

        return null;
    }

    public function reverseEmulate(string $code, array $tokens): array
    {
        $keywordToken = $this->getKeywordToken();
        foreach ($tokens as $i => $token) {
            if ($token[0] === $keywordToken) {
                $tokens[$i][0] = \T_STRING;
            }
        }

        return $tokens;
    }
}
