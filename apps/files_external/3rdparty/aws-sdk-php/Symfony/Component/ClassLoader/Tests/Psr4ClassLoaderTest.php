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

use Symfony\Component\ClassLoader\Psr4ClassLoader;

class Psr4ClassLoaderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param string $className
     * @dataProvider getLoadClassTests
     */
    public function testLoadClass($className)
    {
        $loader = new Psr4ClassLoader();
        $loader->addPrefix(
            'Acme\\DemoLib',
            __DIR__.DIRECTORY_SEPARATOR.'Fixtures'.DIRECTORY_SEPARATOR.'psr-4'
        );
        $loader->loadClass($className);
        $this->assertTrue(class_exists($className), sprintf('loadClass() should load %s', $className));
    }

    /**
     * @return array
     */
    public function getLoadClassTests()
    {
        return array(
            array('Acme\\DemoLib\\Foo'),
            array('Acme\\DemoLib\\Class_With_Underscores'),
            array('Acme\\DemoLib\\Lets\\Go\\Deeper\\Foo'),
            array('Acme\\DemoLib\\Lets\\Go\\Deeper\\Class_With_Underscores'),
        );
    }

    /**
     * @param string $className
     * @dataProvider getLoadNonexistentClassTests
     */
    public function testLoadNonexistentClass($className)
    {
        $loader = new Psr4ClassLoader();
        $loader->addPrefix(
            'Acme\\DemoLib',
            __DIR__.DIRECTORY_SEPARATOR.'Fixtures'.DIRECTORY_SEPARATOR.'psr-4'
        );
        $loader->loadClass($className);
        $this->assertFalse(class_exists($className), sprintf('loadClass() should not load %s', $className));
    }

    /**
     * @return array
     */
    public function getLoadNonexistentClassTests()
    {
        return array(
            array('Acme\\DemoLib\\I_Do_Not_Exist'),
            array('UnknownVendor\\SomeLib\\I_Do_Not_Exist'),
        );
    }
}
