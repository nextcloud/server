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

namespace PhpCsFixer\Fixer\CastNotation;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\VersionSpecification;
use PhpCsFixer\FixerDefinition\VersionSpecificCodeSample;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

/**
 * @author SpacePossum
 */
final class ShortScalarCastFixer extends AbstractFixer
{
    /**
     * {@inheritdoc}
     */
    public function getDefinition()
    {
        return new FixerDefinition(
            'Cast `(boolean)` and `(integer)` should be written as `(bool)` and `(int)`, `(double)` and `(real)` as `(float)`, `(binary)` as `(string)`.',
            [
                new VersionSpecificCodeSample(
                    "<?php\n\$a = (boolean) \$b;\n\$a = (integer) \$b;\n\$a = (double) \$b;\n\$a = (real) \$b;\n\n\$a = (binary) \$b;\n",
                    new VersionSpecification(null, 70399)
                ),
                new VersionSpecificCodeSample(
                    "<?php\n\$a = (boolean) \$b;\n\$a = (integer) \$b;\n\$a = (double) \$b;\n\n\$a = (binary) \$b;\n",
                    new VersionSpecification(70400)
                ),
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function isCandidate(Tokens $tokens)
    {
        return $tokens->isAnyTokenKindsFound(Token::getCastTokenKinds());
    }

    /**
     * {@inheritdoc}
     */
    protected function applyFix(\SplFileInfo $file, Tokens $tokens)
    {
        static $castMap = [
            'boolean' => 'bool',
            'integer' => 'int',
            'double' => 'float',
            'real' => 'float',
            'binary' => 'string',
        ];

        for ($index = 0, $count = $tokens->count(); $index < $count; ++$index) {
            if (!$tokens[$index]->isCast()) {
                continue;
            }

            $castFrom = trim(substr($tokens[$index]->getContent(), 1, -1));
            $castFromLowered = strtolower($castFrom);

            if (!\array_key_exists($castFromLowered, $castMap)) {
                continue;
            }

            $tokens[$index] = new Token([
                $tokens[$index]->getId(),
                str_replace($castFrom, $castMap[$castFromLowered], $tokens[$index]->getContent()),
            ]);
        }
    }
}
