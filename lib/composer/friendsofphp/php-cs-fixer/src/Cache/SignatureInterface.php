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

namespace PhpCsFixer\Cache;

/**
 * @author Andreas Möller <am@localheinz.com>
 *
 * @internal
 */
interface SignatureInterface
{
    /**
     * @return string
     */
    public function getPhpVersion();

    /**
     * @return string
     */
    public function getFixerVersion();

    /**
     * @return string
     */
    public function getIndent();

    /**
     * @return string
     */
    public function getLineEnding();

    /**
     * @return array
     */
    public function getRules();

    /**
     * @param SignatureInterface $signature
     *
     * @return bool
     */
    public function equals(self $signature);
}
