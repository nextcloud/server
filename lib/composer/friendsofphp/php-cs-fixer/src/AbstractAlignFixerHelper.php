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

use PhpCsFixer\Tokenizer\Tokens;

/**
 * @author Carlos Cirello <carlos.cirello.nl@gmail.com>
 *
 * @internal
 *
 * @deprecated
 */
abstract class AbstractAlignFixerHelper
{
    /**
     * @const Placeholder used as anchor for right alignment.
     */
    const ALIGNABLE_PLACEHOLDER = "\x2 ALIGNABLE%d \x3";

    /**
     * Keep track of the deepest level ever achieved while
     * parsing the code. Used later to replace alignment
     * placeholders with spaces.
     *
     * @var int
     */
    protected $deepestLevel = 0;

    public function fix(Tokens $tokens)
    {
        // This fixer works partially on Tokens and partially on string representation of code.
        // During the process of fixing internal state of single Token may be affected by injecting ALIGNABLE_PLACEHOLDER to its content.
        // The placeholder will be resolved by `replacePlaceholder` method by removing placeholder or changing it into spaces.
        // That way of fixing the code causes disturbances in marking Token as changed - if code is perfectly valid then placeholder
        // still be injected and removed, which will cause the `changed` flag to be set.
        // To handle that unwanted behavior we work on clone of Tokens collection and then override original collection with fixed collection.
        $tokensClone = clone $tokens;

        $this->injectAlignmentPlaceholders($tokensClone, 0, \count($tokens));
        $content = $this->replacePlaceholder($tokensClone);

        $tokens->setCode($content);
    }

    /**
     * Inject into the text placeholders of candidates of vertical alignment.
     *
     * @param int $startAt
     * @param int $endAt
     */
    abstract protected function injectAlignmentPlaceholders(Tokens $tokens, $startAt, $endAt);

    /**
     * Look for group of placeholders, and provide vertical alignment.
     *
     * @return string
     */
    protected function replacePlaceholder(Tokens $tokens)
    {
        $tmpCode = $tokens->generateCode();

        for ($j = 0; $j <= $this->deepestLevel; ++$j) {
            $placeholder = sprintf(self::ALIGNABLE_PLACEHOLDER, $j);

            if (false === strpos($tmpCode, $placeholder)) {
                continue;
            }

            $lines = explode("\n", $tmpCode);
            $linesWithPlaceholder = [];
            $blockSize = 0;

            $linesWithPlaceholder[$blockSize] = [];

            foreach ($lines as $index => $line) {
                if (substr_count($line, $placeholder) > 0) {
                    $linesWithPlaceholder[$blockSize][] = $index;
                } else {
                    ++$blockSize;
                    $linesWithPlaceholder[$blockSize] = [];
                }
            }

            foreach ($linesWithPlaceholder as $group) {
                if (\count($group) < 1) {
                    continue;
                }

                $rightmostSymbol = 0;
                foreach ($group as $index) {
                    $rightmostSymbol = max($rightmostSymbol, strpos(utf8_decode($lines[$index]), $placeholder));
                }

                foreach ($group as $index) {
                    $line = $lines[$index];
                    $currentSymbol = strpos(utf8_decode($line), $placeholder);
                    $delta = abs($rightmostSymbol - $currentSymbol);

                    if ($delta > 0) {
                        $line = str_replace($placeholder, str_repeat(' ', $delta).$placeholder, $line);
                        $lines[$index] = $line;
                    }
                }
            }

            $tmpCode = str_replace($placeholder, '', implode("\n", $lines));
        }

        return $tmpCode;
    }
}
