<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Routing;

interface RequestContextAwareInterface
{
    /**
     * Sets the request context.
     */
    public function setContext(RequestContext $context);

    /**
     * Gets the request context.
     *
     * @return RequestContext The context
     */
    public function getContext();
}
