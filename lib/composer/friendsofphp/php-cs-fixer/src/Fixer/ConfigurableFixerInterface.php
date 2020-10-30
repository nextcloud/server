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

namespace PhpCsFixer\Fixer;

use PhpCsFixer\ConfigurationException\InvalidFixerConfigurationException;

/**
 * @author Dariusz Rumiński <dariusz.ruminski@gmail.com>
 * @author SpacePossum
 *
 * @todo Will incorporate `ConfigurationDefinitionFixerInterface` in 3.0
 */
interface ConfigurableFixerInterface extends FixerInterface
{
    /**
     * Set configuration.
     *
     * New configuration must override current one, not patch it.
     * Using `null` makes fixer to use default configuration (or reset configuration from previously configured back
     * to default one).
     *
     * Some fixers may have no configuration, then - simply pass null.
     * Other ones may have configuration that will change behavior of fixer,
     * eg `php_unit_strict` fixer allows to configure which methods should be fixed.
     * Finally, some fixers need configuration to work, eg `header_comment`.
     *
     * @param null|array $configuration configuration depends on Fixer
     *
     * @throws InvalidFixerConfigurationException
     */
    public function configure(array $configuration = null);

    /*
     * Defines the available configuration options of the fixer.
     *
     * @return FixerConfigurationResolverInterface
     *
     * @todo uncomment at 3.0
     */
    // public function getConfigurationDefinition();
}
