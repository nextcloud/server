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

namespace PhpCsFixer\Fixer;

use PhpCsFixer\Tokenizer\Tokens;

/**
 * @author Dariusz Rumiński <dariusz.ruminski@gmail.com>
 * @author Fabien Potencier <fabien@symfony.com>
 */
interface FixerInterface
{
    /**
     * Check if the fixer is a candidate for given Tokens collection.
     *
     * Fixer is a candidate when the collection contains tokens that may be fixed
     * during fixer work. This could be considered as some kind of bloom filter.
     * When this method returns true then to the Tokens collection may or may not
     * need a fixing, but when this method returns false then the Tokens collection
     * need no fixing for sure.
     *
     * @return bool
     */
    public function isCandidate(Tokens $tokens);

    /**
     * Check if fixer is risky or not.
     *
     * Risky fixer could change code behavior!
     *
     * @return bool
     */
    public function isRisky();

    /**
     * Fixes a file.
     *
     * @param \SplFileInfo $file   A \SplFileInfo instance
     * @param Tokens       $tokens Tokens collection
     */
    public function fix(\SplFileInfo $file, Tokens $tokens);

    /**
     * Returns the name of the fixer.
     *
     * The name must be all lowercase and without any spaces.
     *
     * @return string The name of the fixer
     */
    public function getName();

    /**
     * Returns the priority of the fixer.
     *
     * The default priority is 0 and higher priorities are executed first.
     *
     * @return int
     */
    public function getPriority();

    /**
     * Returns true if the file is supported by this fixer.
     *
     * @return bool true if the file is supported by this fixer, false otherwise
     */
    public function supports(\SplFileInfo $file);
}
