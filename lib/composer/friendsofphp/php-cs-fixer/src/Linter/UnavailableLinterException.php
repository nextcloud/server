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

/**
 * Exception that is thrown when the chosen linter is not available on the environment.
 *
 * @author Dariusz Rumiński <dariusz.ruminski@gmail.com>
 */
class UnavailableLinterException extends \RuntimeException
{
}
