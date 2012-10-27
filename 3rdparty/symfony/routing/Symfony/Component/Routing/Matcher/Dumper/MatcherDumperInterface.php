<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Routing\Matcher\Dumper;

/**
 * MatcherDumperInterface is the interface that all matcher dumper classes must implement.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
interface MatcherDumperInterface
{
    /**
     * Dumps a set of routes to a PHP class.
     *
     * Available options:
     *
     *  * class:      The class name
     *  * base_class: The base class name
     *
     * @param array $options An array of options
     *
     * @return string A PHP class representing the matcher class
     */
    public function dump(array $options = array());

    /**
     * Gets the routes to match.
     *
     * @return RouteCollection A RouteCollection instance
     */
    public function getRoutes();
}
