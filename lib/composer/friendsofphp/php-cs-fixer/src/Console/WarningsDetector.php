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

namespace PhpCsFixer\Console;

use PhpCsFixer\ToolInfo;
use PhpCsFixer\ToolInfoInterface;

/**
 * @author Dariusz Rumiński <dariusz.ruminski@gmail.com>
 *
 * @internal
 */
final class WarningsDetector
{
    /**
     * @var ToolInfoInterface
     */
    private $toolInfo;

    /**
     * @var string[]
     */
    private $warnings = [];

    public function __construct(ToolInfoInterface $toolInfo)
    {
        $this->toolInfo = $toolInfo;
    }

    public function detectOldMajor()
    {
        // @TODO 3.0 to be activated with new MAJOR release
        // $this->warnings[] = 'You are running PHP CS Fixer v2, which is not maintained anymore. Please update to v3.';
    }

    public function detectOldVendor()
    {
        if ($this->toolInfo->isInstalledByComposer()) {
            $details = $this->toolInfo->getComposerInstallationDetails();
            if (ToolInfo::COMPOSER_LEGACY_PACKAGE_NAME === $details['name']) {
                $this->warnings[] = sprintf(
                    'You are running PHP CS Fixer installed with old vendor `%s`. Please update to `%s`.',
                    ToolInfo::COMPOSER_LEGACY_PACKAGE_NAME,
                    ToolInfo::COMPOSER_PACKAGE_NAME
                );
            }
        }
    }

    /**
     * @return string[]
     */
    public function getWarnings()
    {
        if (!\count($this->warnings)) {
            return [];
        }

        return array_unique(array_merge(
            $this->warnings,
            ['If you need help while solving warnings, ask at https://gitter.im/PHP-CS-Fixer, we will help you!']
        ));
    }
}
