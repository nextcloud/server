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

use Symfony\Component\Finder\Finder as BaseFinder;

/**
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Dariusz Rumiński <dariusz.ruminski@gmail.com>
 */
class Finder extends BaseFinder
{
    public function __construct()
    {
        parent::__construct();

        $this
            ->files()
            ->name('*.php')
            ->ignoreDotFiles(true)
            ->ignoreVCS(true)
            ->exclude('vendor')
        ;
    }
}
