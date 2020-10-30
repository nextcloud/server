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

namespace PhpCsFixer\ConfigurationException;

use PhpCsFixer\Console\Command\FixCommandExitStatusCalculator;

/**
 * Exceptions of this type are thrown on misconfiguration of the Fixer.
 *
 * @author SpacePossum
 *
 * @internal
 * @final Only internal extending this class is supported
 */
class InvalidConfigurationException extends \InvalidArgumentException
{
    /**
     * @param string          $message
     * @param null|int        $code
     * @param null|\Throwable $previous
     */
    public function __construct($message, $code = null, $previous = null)
    {
        parent::__construct(
            $message,
            null === $code ? FixCommandExitStatusCalculator::EXIT_STATUS_FLAG_HAS_INVALID_CONFIG : $code,
            $previous
        );
    }
}
