<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Routing\Matcher;

use Symfony\Component\Routing\RequestContextAwareInterface;

/**
 * UrlMatcherInterface is the interface that all URL matcher classes must implement.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 *
 * @api
 */
interface UrlMatcherInterface extends RequestContextAwareInterface
{
    /**
     * Tries to match a URL with a set of routes.
     *
     * @param string $pathinfo The path info to be parsed
     *
     * @return array An array of parameters
     *
     * @throws ResourceNotFoundException If the resource could not be found
     * @throws MethodNotAllowedException If the resource was found but the request method is not allowed
     *
     * @api
     */
    public function match($pathinfo);
}
