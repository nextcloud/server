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
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

/**
 * Fixer for rules defined in PSR2 ¶5.2.
 *
 * @author SpacePossum
 */
final class SwitchCaseSemicolonToColonFixer extends AbstractFixer
{
    /**
     * {@inheritdoc}
     */
    public function getDefinition()
    {
        return new FixerDefinition(
            'A case should be followed by a colon and not a semicolon.',
            [
                new CodeSample(
                    '<?php
    switch ($a) {
        case 1;
            break;
        default;
            break;
    }
'
                ),
            ]
        );
    }

    /**
     * {@inheritdoc}
     *
     * Must run after NoEmptyStatementFixer.
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
        return $tokens->isAnyTokenKindsFound([T_CASE, T_DEFAULT]);
    }

    /**
     * {@inheritdoc}
     */
    protected function applyFix(\SplFileInfo $file, Tokens $tokens)
    {
        foreach ($tokens as $index => $token) {
            if ($token->isGivenKind([T_CASE, T_DEFAULT])) {
                $this->fixSwitchCase($tokens, $index);
            }
        }
    }

    /**
     * @param int $index
     */
    protected function fixSwitchCase(Tokens $tokens, $index)
    {
        $ternariesCount = 0;
        do {
            if ($tokens[$index]->equalsAny(['(', '{'])) { // skip constructs
                $type = Tokens::detectBlockType($tokens[$index]);
                $index = $tokens->findBlockEnd($type['type'], $index);

                continue;
            }

            if ($tokens[$index]->equals('?')) {
                ++$ternariesCount;

                continue;
            }

            if ($tokens[$index]->equalsAny([':', ';'])) {
                if (0 === $ternariesCount) {
                    break;
                }

                --$ternariesCount;
            }
        } while (++$index);

        if ($tokens[$index]->equals(';')) {
            $tokens[$index] = new Token(':');
        }
    }
}
