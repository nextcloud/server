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

namespace PhpCsFixer\Fixer\Operator;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\Fixer\ConfigurationDefinitionFixerInterface;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolver;
use PhpCsFixer\FixerConfiguration\FixerOptionBuilder;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Tokenizer\CT;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixer\Tokenizer\TokensAnalyzer;

/**
 * @author Gregor Harlan <gharlan@web.de>
 * @author Kuba Werłos <werlos@gmail.com>
 */
final class IncrementStyleFixer extends AbstractFixer implements ConfigurationDefinitionFixerInterface
{
    /**
     * @internal
     */
    const STYLE_PRE = 'pre';

    /**
     * @internal
     */
    const STYLE_POST = 'post';

    /**
     * {@inheritdoc}
     */
    public function getDefinition()
    {
        return new FixerDefinition(
            'Pre- or post-increment and decrement operators should be used if possible.',
            [
                new CodeSample("<?php\n\$a++;\n\$b--;\n"),
                new CodeSample(
                    "<?php\n++\$a;\n--\$b;\n",
                    ['style' => self::STYLE_POST]
                ),
            ]
        );
    }

    /**
     * {@inheritdoc}
     *
     * Must run after StandardizeIncrementFixer.
     */
    public function getPriority()
    {
        return 0;
    }

    /**
     * {@inheritdoc}
     */
    public function isCandidate(Tokens $tokens)
    {
        return $tokens->isAnyTokenKindsFound([T_INC, T_DEC]);
    }

    /**
     * {@inheritdoc}
     */
    protected function createConfigurationDefinition()
    {
        return new FixerConfigurationResolver([
            (new FixerOptionBuilder('style', 'Whether to use pre- or post-increment and decrement operators.'))
                ->setAllowedValues([self::STYLE_PRE, self::STYLE_POST])
                ->setDefault(self::STYLE_PRE)
                ->getOption(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function applyFix(\SplFileInfo $file, Tokens $tokens)
    {
        $tokensAnalyzer = new TokensAnalyzer($tokens);

        for ($index = $tokens->count() - 1; 0 <= $index; --$index) {
            $token = $tokens[$index];

            if (!$token->isGivenKind([T_INC, T_DEC])) {
                continue;
            }

            if (self::STYLE_PRE === $this->configuration['style'] && $tokensAnalyzer->isUnarySuccessorOperator($index)) {
                $nextToken = $tokens[$tokens->getNextMeaningfulToken($index)];
                if (!$nextToken->equalsAny([';', ')'])) {
                    continue;
                }

                $startIndex = $this->findStart($tokens, $index);

                $prevToken = $tokens[$tokens->getPrevMeaningfulToken($startIndex)];
                if ($prevToken->equalsAny([';', '{', '}', [T_OPEN_TAG], ')'])) {
                    $tokens->clearAt($index);
                    $tokens->insertAt($startIndex, clone $token);
                }
            } elseif (self::STYLE_POST === $this->configuration['style'] && $tokensAnalyzer->isUnaryPredecessorOperator($index)) {
                $prevToken = $tokens[$tokens->getPrevMeaningfulToken($index)];
                if (!$prevToken->equalsAny([';', '{', '}', [T_OPEN_TAG], ')'])) {
                    continue;
                }

                $endIndex = $this->findEnd($tokens, $index);

                $nextToken = $tokens[$tokens->getNextMeaningfulToken($endIndex)];
                if ($nextToken->equalsAny([';', ')'])) {
                    $tokens->clearAt($index);
                    $tokens->insertAt($tokens->getNextNonWhitespace($endIndex), clone $token);
                }
            }
        }
    }

    /**
     * @param int $index
     *
     * @return int
     */
    private function findEnd(Tokens $tokens, $index)
    {
        $nextIndex = $tokens->getNextMeaningfulToken($index);
        $nextToken = $tokens[$nextIndex];

        while ($nextToken->equalsAny([
            '$',
            '[',
            [CT::T_DYNAMIC_PROP_BRACE_OPEN],
            [CT::T_DYNAMIC_VAR_BRACE_OPEN],
            [CT::T_ARRAY_INDEX_CURLY_BRACE_OPEN],
            [T_NS_SEPARATOR],
            [T_STATIC],
            [T_STRING],
            [T_VARIABLE],
        ])) {
            $blockType = Tokens::detectBlockType($nextToken);
            if (null !== $blockType) {
                $nextIndex = $tokens->findBlockEnd($blockType['type'], $nextIndex);
            }
            $index = $nextIndex;
            $nextIndex = $tokens->getNextMeaningfulToken($nextIndex);
            $nextToken = $tokens[$nextIndex];
        }

        if ($nextToken->isGivenKind(T_OBJECT_OPERATOR)) {
            return $this->findEnd($tokens, $nextIndex);
        }

        if ($nextToken->isGivenKind(T_PAAMAYIM_NEKUDOTAYIM)) {
            return $this->findEnd($tokens, $tokens->getNextMeaningfulToken($nextIndex));
        }

        return $index;
    }

    /**
     * @param int $index
     *
     * @return int
     */
    private function findStart(Tokens $tokens, $index)
    {
        do {
            $index = $tokens->getPrevMeaningfulToken($index);
            $token = $tokens[$index];

            $blockType = Tokens::detectBlockType($token);
            if (null !== $blockType && !$blockType['isStart']) {
                $index = $tokens->findBlockStart($blockType['type'], $index);
                $token = $tokens[$index];
            }
        } while (!$token->equalsAny(['$', [T_VARIABLE]]));

        $prevIndex = $tokens->getPrevMeaningfulToken($index);
        $prevToken = $tokens[$prevIndex];

        if ($prevToken->equals('$')) {
            $index = $prevIndex;
            $prevIndex = $tokens->getPrevMeaningfulToken($index);
            $prevToken = $tokens[$prevIndex];
        }

        if ($prevToken->isGivenKind(T_OBJECT_OPERATOR)) {
            return $this->findStart($tokens, $prevIndex);
        }

        if ($prevToken->isGivenKind(T_PAAMAYIM_NEKUDOTAYIM)) {
            $prevPrevIndex = $tokens->getPrevMeaningfulToken($prevIndex);
            if (!$tokens[$prevPrevIndex]->isGivenKind([T_STATIC, T_STRING])) {
                return $this->findStart($tokens, $prevIndex);
            }

            $index = $tokens->getTokenNotOfKindSibling($prevIndex, -1, [[T_NS_SEPARATOR], [T_STATIC], [T_STRING]]);
            $index = $tokens->getNextMeaningfulToken($index);
        }

        return $index;
    }
}
