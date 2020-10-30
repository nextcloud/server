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

/**
 * @internal
 */
interface PharCheckerInterface
{
    /**
     * @param string $filename
     *
     * @return null|string the invalidity reason if any, null otherwise
     */
    public function checkFileValidity($filename);
}
