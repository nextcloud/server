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

namespace PhpCsFixer\Fixer\ControlStructure;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\Fixer\ConfigurationDefinitionFixerInterface;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolver;
use PhpCsFixer\FixerConfiguration\FixerOptionBuilder;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

/**
 * @author SpacePossum
 */
final class NoUnneededCurlyBracesFixer extends AbstractFixer implements ConfigurationDefinitionFixerInterface
{
    /**
     * {@inheritdoc}
     */
    public function getDefinition()
    {
        return new FixerDefinition(
            'Removes unneeded curly braces that are superfluous and aren\'t part of a control structure\'s body.',
            [
                new CodeSample(
                    '<?php {
    echo 1;
}

switch ($b) {
    case 1: {
        break;
    }
}
'
                ),
                new CodeSample(
                    '<?php
namespace Foo {
    function Bar(){}
}
',
                    ['namespaces' => true]
                ),
            ]
        );
    }

    /**
     * {@inheritdoc}
     *
     * Must run before NoUselessElseFixer, NoUselessReturnFixer, ReturnAssignmentFixer.
     */
    public function getPriority()
    {
        return 26;
    }

    /**
     * {@inheritdoc}
     */
    public function isCandidate(Tokens $tokens)
    {
        return $tokens->isTokenKindFound('}');
    }

    /**
     * {@inheritdoc}
     */
    protected function applyFix(\SplFileInfo $file, Tokens $tokens)
    {
        foreach ($this->findCurlyBraceOpen($tokens) as $index) {
            if ($this->isOverComplete($tokens, $index)) {
                $this->clearOverCompleteBraces($tokens, $index, $tokens->findBlockEnd(Tokens::BLOCK_TYPE_CURLY_BRACE, $index));
            }
        }

        if ($this->configuration['namespaces']) {
            $this->clearIfIsOverCompleteNamespaceBlock($tokens);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function createConfigurationDefinition()
    {
        return new FixerConfigurationResolver([
            (new FixerOptionBuilder('namespaces', 'Remove unneeded curly braces from bracketed namespaces.'))
                ->setAllowedTypes(['bool'])
                ->setDefault(false)
                ->getOption(),
        ]);
    }

    /**
     * @param int $openIndex  index of `{` token
     * @param int $closeIndex index of `}` token
     */
    private function clearOverCompleteBraces(Tokens $tokens, $openIndex, $closeIndex)
    {
        $tokens->clearTokenAndMergeSurroundingWhitespace($closeIndex);
        $tokens->clearTokenAndMergeSurroundingWhitespace($openIndex);
    }

    private function findCurlyBraceOpen(Tokens $tokens)
    {
        for ($i = \count($tokens) - 1; $i > 0; --$i) {
            if ($tokens[$i]->equals('{')) {
                yield $i;
            }
        }
    }

    /**
     * @param int $index index of `{` token
     *
     * @return bool
     */
    private function isOverComplete(Tokens $tokens, $index)
    {
        static $whiteList = ['{', '}', [T_OPEN_TAG], ':', ';'];

        return $tokens[$tokens->getPrevMeaningfulToken($index)]->equalsAny($whiteList);
    }

    private function clearIfIsOverCompleteNamespaceBlock(Tokens $tokens)
    {
        if (Tokens::isLegacyMode()) {
            $index = $tokens->getNextTokenOfKind(0, [[T_NAMESPACE]]);
            $secondNamespaceIndex = $tokens->getNextTokenOfKind($index, [[T_NAMESPACE]]);

            if (null !== $secondNamespaceIndex) {
                return;
            }
        } elseif (1 !== $tokens->countTokenKind(T_NAMESPACE)) {
            return; // fast check, we never fix if multiple namespaces are defined
        }

        $index = $tokens->getNextTokenOfKind(0, [[T_NAMESPACE]]);

        do {
            $index = $tokens->getNextMeaningfulToken($index);
        } while ($tokens[$index]->isGivenKind([T_STRING, T_NS_SEPARATOR]));

        if (!$tokens[$index]->equals('{')) {
            return; // `;`
        }

        $closeIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_CURLY_BRACE, $index);
        $afterCloseIndex = $tokens->getNextMeaningfulToken($closeIndex);

        if (null !== $afterCloseIndex && (!$tokens[$afterCloseIndex]->isGivenKind(T_CLOSE_TAG) || null !== $tokens->getNextMeaningfulToken($afterCloseIndex))) {
            return;
        }

        // clear up
        $tokens->clearTokenAndMergeSurroundingWhitespace($closeIndex);
        $tokens[$index] = new Token(';');

        if ($tokens[$index - 1]->isWhitespace(" \t") && !$tokens[$index - 2]->isComment()) {
            $tokens->clearTokenAndMergeSurroundingWhitespace($index - 1);
        }
    }
}
