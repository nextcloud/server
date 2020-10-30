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

namespace PhpCsFixer\DocBlock;

/**
 * This class represents a short description (aka summary) of a docblock.
 *
 * @internal
 */
final class ShortDescription
{
    /**
     * The docblock containing the short description.
     *
     * @var DocBlock
     */
    private $doc;

    public function __construct(DocBlock $doc)
    {
        $this->doc = $doc;
    }

    /**
     * Get the line index of the line containing the end of the short
     * description, if present.
     *
     * @return null|int
     */
    public function getEnd()
    {
        $reachedContent = false;

        foreach ($this->doc->getLines() as $index => $line) {
            // we went past a description, then hit a tag or blank line, so
            // the last line of the description must be the one before this one
            if ($reachedContent && ($line->containsATag() || !$line->containsUsefulContent())) {
                return $index - 1;
            }

            // no short description was found
            if ($line->containsATag()) {
                return null;
            }

            // we've reached content, but need to check the next lines too
            // in case the short description is multi-line
            if ($line->containsUsefulContent()) {
                $reachedContent = true;
            }
        }

        return null;
    }
}
