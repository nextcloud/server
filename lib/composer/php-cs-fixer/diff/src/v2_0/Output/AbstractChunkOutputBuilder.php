<?php
/*
 * This file is part of sebastian/diff.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhpCsFixer\Diff\v2_0\Output;

abstract class AbstractChunkOutputBuilder implements DiffOutputBuilderInterface
{
    /**
     * Takes input of the diff array and returns the common parts.
     * Iterates through diff line by line.
     *
     * @param array $diff
     * @param int   $lineThreshold
     *
     * @return array
     */
    protected function getCommonChunks(array $diff, $lineThreshold = 5)
    {
        $diffSize     = \count($diff);
        $capturing    = false;
        $chunkStart   = 0;
        $chunkSize    = 0;
        $commonChunks = [];

        for ($i = 0; $i < $diffSize; ++$i) {
            if ($diff[$i][1] === 0 /* OLD */) {
                if ($capturing === false) {
                    $capturing  = true;
                    $chunkStart = $i;
                    $chunkSize  = 0;
                } else {
                    ++$chunkSize;
                }
            } elseif ($capturing !== false) {
                if ($chunkSize >= $lineThreshold) {
                    $commonChunks[$chunkStart] = $chunkStart + $chunkSize;
                }

                $capturing = false;
            }
        }

        if ($capturing !== false && $chunkSize >= $lineThreshold) {
            $commonChunks[$chunkStart] = $chunkStart + $chunkSize;
        }

        return $commonChunks;
    }
}
