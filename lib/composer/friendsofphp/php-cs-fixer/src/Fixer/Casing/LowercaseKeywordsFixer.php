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

namespace PhpCsFixer\Fixer\Casing;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

/**
 * Fixer for rules defined in PSR2 ¶2.5.
 *
 * @author Dariusz Rumiński <dariusz.ruminski@gmail.com>
 */
final class LowercaseKeywordsFixer extends AbstractFixer
{
    private static $excludedTokens = [T_HALT_COMPILER];

    /**
     * {@inheritdoc}
     */
    public function getDefinition()
    {
        return new FixerDefinition(
            'PHP keywords MUST be in lower case.',
            [
                new CodeSample(
                    '<?php
    FOREACH($a AS $B) {
        TRY {
            NEW $C($a, ISSET($B));
            WHILE($B) {
                INCLUDE "test.php";
            }
        } CATCH(\Exception $e) {
            EXIT(1);
        }
    }
'
                ),
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function isCandidate(Tokens $tokens)
    {
        return $tokens->isAnyTokenKindsFound(Token::getKeywords());
    }

    /**
     * {@inheritdoc}
     */
    protected function applyFix(\SplFileInfo $file, Tokens $tokens)
    {
        foreach ($tokens as $index => $token) {
            if ($token->isKeyword() && !$token->isGivenKind(self::$excludedTokens)) {
                $tokens[$index] = new Token([$token->getId(), strtolower($token->getContent())]);
            }
        }
    }
}
