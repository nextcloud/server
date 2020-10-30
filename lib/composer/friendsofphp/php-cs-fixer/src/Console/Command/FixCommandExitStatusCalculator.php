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

namespace PhpCsFixer\Console\Command;

/**
 * @author Dariusz Rumiński <dariusz.ruminski@gmail.com>
 *
 * @internal
 */
final class FixCommandExitStatusCalculator
{
    // Exit status 1 is reserved for environment constraints not matched.
    const EXIT_STATUS_FLAG_HAS_INVALID_FILES = 4;
    const EXIT_STATUS_FLAG_HAS_CHANGED_FILES = 8;
    const EXIT_STATUS_FLAG_HAS_INVALID_CONFIG = 16;
    const EXIT_STATUS_FLAG_HAS_INVALID_FIXER_CONFIG = 32;
    const EXIT_STATUS_FLAG_EXCEPTION_IN_APP = 64;

    /**
     * @param bool $isDryRun
     * @param bool $hasChangedFiles
     * @param bool $hasInvalidErrors
     * @param bool $hasExceptionErrors
     * @param bool $hasLintErrorsAfterFixing
     *
     * @return int
     */
    public function calculate($isDryRun, $hasChangedFiles, $hasInvalidErrors, $hasExceptionErrors, $hasLintErrorsAfterFixing)
    {
        $exitStatus = 0;

        if ($isDryRun) {
            if ($hasChangedFiles) {
                $exitStatus |= self::EXIT_STATUS_FLAG_HAS_CHANGED_FILES;
            }

            if ($hasInvalidErrors) {
                $exitStatus |= self::EXIT_STATUS_FLAG_HAS_INVALID_FILES;
            }
        }

        if ($hasExceptionErrors || $hasLintErrorsAfterFixing) {
            $exitStatus |= self::EXIT_STATUS_FLAG_EXCEPTION_IN_APP;
        }

        return $exitStatus;
    }
}
