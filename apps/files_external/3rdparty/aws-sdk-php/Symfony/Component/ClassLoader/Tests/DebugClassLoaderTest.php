<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\ClassLoader\Tests;

use Symfony\Component\ClassLoader\ClassLoader;
use Symfony\Component\ClassLoader\DebugClassLoader;

class DebugClassLoaderTest extends \PHPUnit_Framework_TestCase
{
    private $loader;

    protected function setUp()
    {
        $this->loader = new ClassLoader();
        spl_autoload_register(array($this->loader, 'loadClass'));
    }

    protected function tearDown()
    {
        spl_autoload_unregister(array($this->loader, 'loadClass'));
    }

    public function testIdempotence()
    {
        DebugClassLoader::enable();
        DebugClassLoader::enable();

        $functions = spl_autoload_functions();
        foreach ($functions as $function) {
            if (is_array($function) && $function[0] instanceof DebugClassLoader) {
                $reflClass = new \ReflectionClass($function[0]);
                $reflProp = $reflClass->getProperty('classFinder');
                $reflProp->setAccessible(true);

                $this->assertNotInstanceOf('Symfony\Component\ClassLoader\DebugClassLoader', $reflProp->getValue($function[0]));

                return;
            }
        }

        throw new \Exception('DebugClassLoader did not register');
    }
}
