<?php

/*
 * This file is part of PHP CS Fixer.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *     Dariusz Rumiński <dariusz.ruminski@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace PhpCsFixer\Doctrine\Annotation;

use Doctrine\Common\Annotations\DocLexer;
use PhpCsFixer\Preg;
use PhpCsFixer\Tokenizer\Token as PhpToken;

/**
 * A list of Doctrine annotation tokens.
 *
 * @internal
 */
final class Tokens extends \SplFixedArray
{
    /**
     * @param string[] $ignoredTags
     *
     * @throws \InvalidArgumentException
     *
     * @return self
     */
    public static function createFromDocComment(PhpToken $input, array $ignoredTags = [])
    {
        if (!$input->isGivenKind(T_DOC_COMMENT)) {
            throw new \InvalidArgumentException('Input must be a T_DOC_COMMENT token.');
        }

        $tokens = new self();

        $content = $input->getContent();
        $ignoredTextPosition = 0;
        $currentPosition = 0;
        while (false !== $nextAtPosition = strpos($content, '@', $currentPosition)) {
            if (0 !== $nextAtPosition && !Preg::match('/\s/', $content[$nextAtPosition - 1])) {
                $currentPosition = $nextAtPosition + 1;

                continue;
            }

            $lexer = new DocLexer();
            $lexer->setInput(substr($content, $nextAtPosition));

            $scannedTokens = [];
            $index = 0;
            $nbScannedTokensToUse = 0;
            $nbScopes = 0;
            while (null !== $token = $lexer->peek()) {
                if (0 === $index && DocLexer::T_AT !== $token['type']) {
                    break;
                }

                if (1 === $index) {
                    if (DocLexer::T_IDENTIFIER !== $token['type'] || \in_array($token['value'], $ignoredTags, true)) {
                        break;
                    }

                    $nbScannedTokensToUse = 2;
                }

                if ($index >= 2 && 0 === $nbScopes && !\in_array($token['type'], [DocLexer::T_NONE, DocLexer::T_OPEN_PARENTHESIS], true)) {
                    break;
                }

                $scannedTokens[] = $token;

                if (DocLexer::T_OPEN_PARENTHESIS === $token['type']) {
                    ++$nbScopes;
                } elseif (DocLexer::T_CLOSE_PARENTHESIS === $token['type']) {
                    if (0 === --$nbScopes) {
                        $nbScannedTokensToUse = \count($scannedTokens);

                        break;
                    }
                }

                ++$index;
            }

            if (0 !== $nbScopes) {
                break;
            }

            if (0 !== $nbScannedTokensToUse) {
                $ignoredTextLength = $nextAtPosition - $ignoredTextPosition;
                if (0 !== $ignoredTextLength) {
                    $tokens[] = new Token(DocLexer::T_NONE, substr($content, $ignoredTextPosition, $ignoredTextLength));
                }

                $lastTokenEndIndex = 0;
                foreach (\array_slice($scannedTokens, 0, $nbScannedTokensToUse) as $token) {
                    if (DocLexer::T_STRING === $token['type']) {
                        $token['value'] = '"'.str_replace('"', '""', $token['value']).'"';
                    }

                    $missingTextLength = $token['position'] - $lastTokenEndIndex;
                    if ($missingTextLength > 0) {
                        $tokens[] = new Token(DocLexer::T_NONE, substr(
                            $content,
                            $nextAtPosition + $lastTokenEndIndex,
                            $missingTextLength
                        ));
                    }

                    $tokens[] = new Token($token['type'], $token['value']);
                    $lastTokenEndIndex = $token['position'] + \strlen($token['value']);
                }

                $currentPosition = $ignoredTextPosition = $nextAtPosition + $token['position'] + \strlen($token['value']);
            } else {
                $currentPosition = $nextAtPosition + 1;
            }
        }

        if ($ignoredTextPosition < \strlen($content)) {
            $tokens[] = new Token(DocLexer::T_NONE, substr($content, $ignoredTextPosition));
        }

        return $tokens;
    }

    /**
     * Returns the index of the closest next token that is neither a comment nor a whitespace token.
     *
     * @param int $index
     *
     * @return null|int
     */
    public function getNextMeaningfulToken($index)
    {
        return $this->getMeaningfulTokenSibling($index, 1);
    }

    /**
     * Returns the index of the closest previous token that is neither a comment nor a whitespace token.
     *
     * @param int $index
     *
     * @return null|int
     */
    public function getPreviousMeaningfulToken($index)
    {
        return $this->getMeaningfulTokenSibling($index, -1);
    }

    /**
     * Returns the index of the closest next token of the given type.
     *
     * @param string|string[] $type
     * @param int             $index
     *
     * @return null|int
     */
    public function getNextTokenOfType($type, $index)
    {
        return $this->getTokenOfTypeSibling($index, $type, 1);
    }

    /**
     * Returns the index of the closest previous token of the given type.
     *
     * @param string|string[] $type
     * @param int             $index
     *
     * @return null|int
     */
    public function getPreviousTokenOfType($type, $index)
    {
        return $this->getTokenOfTypeSibling($index, $type, -1);
    }

    /**
     * Returns the index of the last token that is part of the annotation at the given index.
     *
     * @param int $index
     *
     * @return null|int
     */
    public function getAnnotationEnd($index)
    {
        $currentIndex = null;

        if (isset($this[$index + 2])) {
            if ($this[$index + 2]->isType(DocLexer::T_OPEN_PARENTHESIS)) {
                $currentIndex = $index + 2;
            } elseif (
                isset($this[$index + 3])
                && $this[$index + 2]->isType(DocLexer::T_NONE)
                && $this[$index + 3]->isType(DocLexer::T_OPEN_PARENTHESIS)
                && Preg::match('/^(\R\s*\*\s*)*\s*$/', $this[$index + 2]->getContent())
            ) {
                $currentIndex = $index + 3;
            }
        }

        if (null !== $currentIndex) {
            $level = 0;
            for ($max = \count($this); $currentIndex < $max; ++$currentIndex) {
                if ($this[$currentIndex]->isType(DocLexer::T_OPEN_PARENTHESIS)) {
                    ++$level;
                } elseif ($this[$currentIndex]->isType(DocLexer::T_CLOSE_PARENTHESIS)) {
                    --$level;
                }

                if (0 === $level) {
                    return $currentIndex;
                }
            }

            return null;
        }

        return $index + 1;
    }

    /**
     * Returns the index of the close brace that matches the open brace at the given index.
     *
     * @param int $index
     *
     * @return null|int
     */
    public function getArrayEnd($index)
    {
        $level = 1;
        for (++$index, $max = \count($this); $index < $max; ++$index) {
            if ($this[$index]->isType(DocLexer::T_OPEN_CURLY_BRACES)) {
                ++$level;
            } elseif ($this[$index]->isType($index, DocLexer::T_CLOSE_CURLY_BRACES)) {
                --$level;
            }

            if (0 === $level) {
                return $index;
            }
        }

        return null;
    }

    /**
     * Returns the code from the tokens.
     *
     * @return string
     */
    public function getCode()
    {
        $code = '';
        foreach ($this as $token) {
            $code .= $token->getContent();
        }

        return $code;
    }

    /**
     * Inserts a token at the given index.
     *
     * @param int $index
     */
    public function insertAt($index, Token $token)
    {
        $this->setSize($this->getSize() + 1);

        for ($i = $this->getSize() - 1; $i > $index; --$i) {
            $this[$i] = isset($this[$i - 1]) ? $this[$i - 1] : new Token();
        }

        $this[$index] = $token;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \InvalidArgumentException
     */
    public function offsetSet($index, $token)
    {
        if (!$token instanceof Token) {
            $type = \gettype($token);
            if ('object' === $type) {
                $type = \get_class($token);
            }

            throw new \InvalidArgumentException(sprintf(
                'Token must be an instance of PhpCsFixer\\Doctrine\\Annotation\\Token, %s given.',
                $type
            ));
        }

        if (null === $index) {
            $index = \count($this);
            $this->setSize($this->getSize() + 1);
        }

        parent::offsetSet($index, $token);
    }

    /**
     * {@inheritdoc}
     *
     * @throws \OutOfBoundsException
     */
    public function offsetUnset($index)
    {
        if (!isset($this[$index])) {
            throw new \OutOfBoundsException(sprintf('Index %s is invalid or does not exist.', $index));
        }

        $max = \count($this) - 1;
        while ($index < $max) {
            $this[$index] = $this[$index + 1];
            ++$index;
        }

        parent::offsetUnset($index);

        $this->setSize($max);
    }

    /**
     * @param int $index
     * @param int $direction
     *
     * @return null|int
     */
    private function getMeaningfulTokenSibling($index, $direction)
    {
        while (true) {
            $index += $direction;

            if (!$this->offsetExists($index)) {
                break;
            }

            if (!$this[$index]->isType(DocLexer::T_NONE)) {
                return $index;
            }
        }

        return null;
    }

    /**
     * @param int             $index
     * @param string|string[] $type
     * @param int             $direction
     *
     * @return null|int
     */
    private function getTokenOfTypeSibling($index, $type, $direction)
    {
        while (true) {
            $index += $direction;

            if (!$this->offsetExists($index)) {
                break;
            }

            if ($this[$index]->isType($type)) {
                return $index;
            }
        }

        return null;
    }
}
