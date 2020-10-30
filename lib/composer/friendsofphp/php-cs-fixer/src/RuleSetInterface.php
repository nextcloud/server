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
 * Set of rules to be used by fixer.
 *
 * Example of set: ["@PSR2" => true, "@PSR1" => false, "strict" => true].
 *
 * @author Dariusz Rumiński <dariusz.ruminski@gmail.com>
 */
interface RuleSetInterface
{
    public function __construct(array $set = []);

    public static function create(array $set = []);

    /**
     * Get configuration for given rule.
     *
     * @param string $rule name of rule
     *
     * @return null|array
     */
    public function getRuleConfiguration($rule);

    /**
     * Get all rules from rules set.
     *
     * @return array
     */
    public function getRules();

    /**
     * Get names of all set definitions.
     *
     * @return string[]
     */
    public function getSetDefinitionNames();

    /**
     * Check given rule is in rules set.
     *
     * @param string $rule name of rule
     *
     * @return bool
     */
    public function hasRule($rule);
}
