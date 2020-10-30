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
interface ToolInfoInterface
{
    public function getComposerInstallationDetails();

    public function getComposerVersion();

    public function getVersion();

    public function isInstalledAsPhar();

    public function isInstalledByComposer();

    public function getPharDownloadUri($version);
}
