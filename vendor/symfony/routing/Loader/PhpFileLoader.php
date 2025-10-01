<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Routing\Loader;

use Symfony\Component\Config\Loader\FileLoader;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Symfony\Component\Routing\RouteCollection;

/**
 * PhpFileLoader loads routes from a PHP file.
 *
 * The file must return a RouteCollection instance.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Nicolas grekas <p@tchwork.com>
 * @author Jules Pietri <jules@heahprod.com>
 */
class PhpFileLoader extends FileLoader
{
    /**
     * Loads a PHP file.
     */
    public function load(mixed $file, ?string $type = null): RouteCollection
    {
        $path = $this->locator->locate($file);
        $this->setCurrentDir(\dirname($path));

        // the closure forbids access to the private scope in the included file
        $loader = $this;
        $load = \Closure::bind(static function ($file) use ($loader) {
            return include $file;
        }, null, ProtectedPhpFileLoader::class);

        $result = $load($path);

        if (\is_object($result) && \is_callable($result)) {
            $collection = $this->callConfigurator($result, $path, $file);
        } else {
            $collection = $result;
        }

        $collection->addResource(new FileResource($path));

        return $collection;
    }

    public function supports(mixed $resource, ?string $type = null): bool
    {
        return \is_string($resource) && 'php' === pathinfo($resource, \PATHINFO_EXTENSION) && (!$type || 'php' === $type);
    }

    protected function callConfigurator(callable $result, string $path, string $file): RouteCollection
    {
        $collection = new RouteCollection();

        $result(new RoutingConfigurator($collection, $this, $path, $file, $this->env));

        return $collection;
    }
}

/**
 * @internal
 */
final class ProtectedPhpFileLoader extends PhpFileLoader
{
}
