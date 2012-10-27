<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Routing\Generator;

use Symfony\Component\Routing\RequestContextAwareInterface;

/**
 * UrlGeneratorInterface is the interface that all URL generator classes must implements.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 *
 * @api
 */
interface UrlGeneratorInterface extends RequestContextAwareInterface
{
    /**
     * Generates a URL from the given parameters.
     *
     * @param string  $name       The name of the route
     * @param mixed   $parameters An array of parameters
     * @param Boolean $absolute   Whether to generate an absolute URL
     *
     * @return string The generated URL
     *
     * @api
     */
    public function generate($name, $parameters = array(), $absolute = false);
}
