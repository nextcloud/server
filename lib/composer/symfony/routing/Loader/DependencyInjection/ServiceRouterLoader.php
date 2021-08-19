<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Routing\Loader\DependencyInjection;

use Psr\Container\ContainerInterface;
use Symfony\Component\Routing\Loader\ContainerLoader;
use Symfony\Component\Routing\Loader\ObjectRouteLoader;

@trigger_error(sprintf('The "%s" class is deprecated since Symfony 4.4, use "%s" instead.', ServiceRouterLoader::class, ContainerLoader::class), \E_USER_DEPRECATED);

/**
 * A route loader that executes a service to load the routes.
 *
 * @author Ryan Weaver <ryan@knpuniversity.com>
 *
 * @deprecated since Symfony 4.4, use Symfony\Component\Routing\Loader\ContainerLoader instead.
 */
class ServiceRouterLoader extends ObjectRouteLoader
{
    /**
     * @var ContainerInterface
     */
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    protected function getServiceObject($id)
    {
        return $this->container->get($id);
    }
}
