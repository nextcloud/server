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

namespace PhpCsFixer\Linter;

use PhpCsFixer\FileReader;
use PhpCsFixer\Tokenizer\CodeHasher;
use PhpCsFixer\Tokenizer\Tokens;

/**
 * Handle PHP code linting.
 *
 * @author Dariusz Rumiński <dariusz.ruminski@gmail.com>
 *
 * @internal
 */
final class TokenizerLinter implements LinterInterface
{
    public function __construct()
    {
        if (false === \defined('TOKEN_PARSE')) {
            throw new UnavailableLinterException('Cannot use tokenizer as linter.');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isAsync()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function lintFile($path)
    {
        return $this->lintSource(FileReader::createSingleton()->read($path));
    }

    /**
     * {@inheritdoc}
     */
    public function lintSource($source)
    {
        try {
            // To lint, we will parse the source into Tokens.
            // During that process, it might throw ParseError.
            // If it won't, cache of tokenized version of source will be kept, which is great for Runner.
            // Yet, first we need to clear already existing cache to not hit it and lint the code indeed.
            $codeHash = CodeHasher::calculateCodeHash($source);
            Tokens::clearCache($codeHash);
            Tokens::fromCode($source);

            return new TokenizerLintingResult();
        } catch (\ParseError $e) {
            return new TokenizerLintingResult($e);
        }
    }
}
