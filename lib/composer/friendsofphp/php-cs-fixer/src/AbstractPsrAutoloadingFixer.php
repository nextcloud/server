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

namespace PhpCsFixer;

use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

/**
 * @author Jordi Boggiano <j.boggiano@seld.be>
 * @author Dariusz Rumiński <dariusz.ruminski@gmail.com>
 * @author Bram Gotink <bram@gotink.me>
 * @author Graham Campbell <graham@alt-three.com>
 *
 * @internal
 */
abstract class AbstractPsrAutoloadingFixer extends AbstractFixer
{
    /**
     * {@inheritdoc}
     */
    public function isCandidate(Tokens $tokens)
    {
        return $tokens->isAnyTokenKindsFound(Token::getClassyTokenKinds());
    }

    /**
     * {@inheritdoc}
     */
    public function isRisky()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getPriority()
    {
        return -10;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(\SplFileInfo $file)
    {
        if ($file instanceof StdinFileInfo) {
            return false;
        }

        $filenameParts = explode('.', $file->getBasename(), 2);

        if (
            // ignore file with extension other than php
            (!isset($filenameParts[1]) || 'php' !== $filenameParts[1])
            // ignore file with name that cannot be a class name
            || 0 === Preg::match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/', $filenameParts[0])
        ) {
            return false;
        }

        try {
            $tokens = Tokens::fromCode(sprintf('<?php class %s {}', $filenameParts[0]));

            if ($tokens[3]->isKeyword() || $tokens[3]->isMagicConstant()) {
                // name can not be a class name - detected by PHP 5.x
                return false;
            }
        } catch (\ParseError $e) {
            // name can not be a class name - detected by PHP 7.x
            return false;
        }

        // ignore stubs/fixtures, since they are typically containing invalid files for various reasons
        return !Preg::match('{[/\\\\](stub|fixture)s?[/\\\\]}i', $file->getRealPath());
    }
}
