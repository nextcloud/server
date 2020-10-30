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

namespace PhpCsFixer\Console\SelfUpdate;

/**
 * @internal
 */
interface NewVersionCheckerInterface
{
    /**
     * Returns the tag of the latest version.
     *
     * @return string
     */
    public function getLatestVersion();

    /**
     * Returns the tag of the latest minor/patch version of the given major version.
     *
     * @param int $majorVersion
     *
     * @return null|string
     */
    public function getLatestVersionOfMajor($majorVersion);

    /**
     * Returns -1, 0, or 1 if the first version is respectively less than,
     * equal to, or greater than the second.
     *
     * @param string $versionA
     * @param string $versionB
     *
     * @return int
     */
    public function compareVersions($versionA, $versionB);
}
