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

namespace PhpCsFixer\Fixer\Basic;

use PhpCsFixer\AbstractPsrAutoloadingFixer;
use PhpCsFixer\FixerDefinition\FileSpecificCodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

/**
 * @author Jordi Boggiano <j.boggiano@seld.be>
 * @author Dariusz Rumiński <dariusz.ruminski@gmail.com>
 * @author Bram Gotink <bram@gotink.me>
 * @author Graham Campbell <graham@alt-three.com>
 */
final class Psr4Fixer extends AbstractPsrAutoloadingFixer
{
    /**
     * {@inheritdoc}
     */
    public function getDefinition()
    {
        return new FixerDefinition(
            'Class names should match the file name.',
            [
                new FileSpecificCodeSample(
                    '<?php
namespace PhpCsFixer\FIXER\Basic;
class InvalidName {}
',
                    new \SplFileInfo(__FILE__)
                ),
            ],
            null,
            'This fixer may change your class name, which will break the code that depends on the old name.'
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function applyFix(\SplFileInfo $file, Tokens $tokens)
    {
        $isNamespaceFound = false;
        $classyIndex = 0;
        $classyName = null;

        foreach ($tokens as $index => $token) {
            if ($token->isGivenKind(T_NAMESPACE)) {
                if ($isNamespaceFound) {
                    return;
                }

                $isNamespaceFound = true;
            } elseif ($token->isClassy()) {
                $prevToken = $tokens[$tokens->getPrevMeaningfulToken($index)];
                if ($prevToken->isGivenKind(T_NEW)) {
                    continue;
                }

                if (null !== $classyName) {
                    return;
                }

                $classyIndex = $tokens->getNextMeaningfulToken($index);
                $classyName = $tokens[$classyIndex]->getContent();
            }
        }

        if (null === $classyName) {
            return;
        }

        if ($isNamespaceFound) {
            $filename = basename(str_replace('\\', '/', $file->getRealPath()), '.php');

            if ($classyName !== $filename) {
                $tokens[$classyIndex] = new Token([T_STRING, $filename]);
            }
        } else {
            $normClass = str_replace('_', '/', $classyName);
            $filename = substr(str_replace('\\', '/', $file->getRealPath()), -\strlen($normClass) - 4, -4);

            if ($normClass !== $filename && strtolower($normClass) === strtolower($filename)) {
                $tokens[$classyIndex] = new Token([T_STRING, str_replace('/', '_', $filename)]);
            }
        }
    }
}
