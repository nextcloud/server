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

class ClassLoaderTest extends \PHPUnit_Framework_TestCase
{
    public function testGetPrefixes()
    {
        $loader = new ClassLoader();
        $loader->addPrefix('Foo', __DIR__.DIRECTORY_SEPARATOR.'Fixtures');
        $loader->addPrefix('Bar', __DIR__.DIRECTORY_SEPARATOR.'Fixtures');
        $loader->addPrefix('Bas', __DIR__.DIRECTORY_SEPARATOR.'Fixtures');
        $prefixes = $loader->getPrefixes();
        $this->assertArrayHasKey('Foo', $prefixes);
        $this->assertArrayNotHasKey('Foo1', $prefixes);
        $this->assertArrayHasKey('Bar', $prefixes);
        $this->assertArrayHasKey('Bas', $prefixes);
    }

    public function testGetFallbackDirs()
    {
        $loader = new ClassLoader();
        $loader->addPrefix(null, __DIR__.DIRECTORY_SEPARATOR.'Fixtures');
        $loader->addPrefix(null, __DIR__.DIRECTORY_SEPARATOR.'Fixtures');
        $fallback_dirs = $loader->getFallbackDirs();
        $this->assertCount(2, $fallback_dirs);
    }

    /**
     * @dataProvider getLoadClassTests
     */
    public function testLoadClass($className, $testClassName, $message)
    {
        $loader = new ClassLoader();
        $loader->addPrefix('Namespaced2\\', __DIR__.DIRECTORY_SEPARATOR.'Fixtures');
        $loader->addPrefix('Pearlike2_', __DIR__.DIRECTORY_SEPARATOR.'Fixtures');
        $loader->loadClass($testClassName);
        $this->assertTrue(class_exists($className), $message);
    }

    public function getLoadClassTests()
    {
        return array(
            array('\\Namespaced2\\Foo', 'Namespaced2\\Foo',   '->loadClass() loads Namespaced2\Foo class'),
            array('\\Pearlike2_Foo',    'Pearlike2_Foo',      '->loadClass() loads Pearlike2_Foo class'),
        );
    }

    /**
     * @dataProvider getLoadNonexistentClassTests
     */
    public function testLoadNonexistentClass($className, $testClassName, $message)
    {
        $loader = new ClassLoader();
        $loader->addPrefix('Namespaced2\\', __DIR__.DIRECTORY_SEPARATOR.'Fixtures');
        $loader->addPrefix('Pearlike2_', __DIR__.DIRECTORY_SEPARATOR.'Fixtures');
        $loader->loadClass($testClassName);
        $this->assertFalse(class_exists($className), $message);
    }

    public function getLoadNonexistentClassTests()
    {
        return array(
            array('\\Pearlike3_Bar', '\\Pearlike3_Bar', '->loadClass() loads non exising Pearlike3_Bar class with a leading slash'),
        );
    }

    public function testAddPrefix()
    {
        $loader = new ClassLoader();
        $loader->addPrefix('Foo', __DIR__.DIRECTORY_SEPARATOR.'Fixtures');
        $loader->addPrefix('Foo', __DIR__.DIRECTORY_SEPARATOR.'Fixtures');
        $prefixes = $loader->getPrefixes();
        $this->assertArrayHasKey('Foo', $prefixes);
        $this->assertCount(2, $prefixes['Foo']);
    }

    public function testUseIncludePath()
    {
        $loader = new ClassLoader();
        $this->assertFalse($loader->getUseIncludePath());

        $this->assertNull($loader->findFile('Foo'));

        $includePath = get_include_path();

        $loader->setUseIncludePath(true);
        $this->assertTrue($loader->getUseIncludePath());

        set_include_path(__DIR__.'/Fixtures/includepath'.PATH_SEPARATOR.$includePath);

        $this->assertEquals(__DIR__.DIRECTORY_SEPARATOR.'Fixtures'.DIRECTORY_SEPARATOR.'includepath'.DIRECTORY_SEPARATOR.'Foo.php', $loader->findFile('Foo'));

        set_include_path($includePath);
    }

    /**
     * @dataProvider getLoadClassFromFallbackTests
     */
    public function testLoadClassFromFallback($className, $testClassName, $message)
    {
        $loader = new ClassLoader();
        $loader->addPrefix('Namespaced2\\', __DIR__.DIRECTORY_SEPARATOR.'Fixtures');
        $loader->addPrefix('Pearlike2_', __DIR__.DIRECTORY_SEPARATOR.'Fixtures');
        $loader->addPrefix('', array(__DIR__.DIRECTORY_SEPARATOR.'Fixtures/fallback'));
        $loader->loadClass($testClassName);
        $this->assertTrue(class_exists($className), $message);
    }

    public function getLoadClassFromFallbackTests()
    {
        return array(
            array('\\Namespaced2\\Baz',    'Namespaced2\\Baz',    '->loadClass() loads Namespaced2\Baz class'),
            array('\\Pearlike2_Baz',       'Pearlike2_Baz',       '->loadClass() loads Pearlike2_Baz class'),
            array('\\Namespaced2\\FooBar', 'Namespaced2\\FooBar', '->loadClass() loads Namespaced2\Baz class from fallback dir'),
            array('\\Pearlike2_FooBar',    'Pearlike2_FooBar',    '->loadClass() loads Pearlike2_Baz class from fallback dir'),
        );
    }

    /**
     * @dataProvider getLoadClassNamespaceCollisionTests
     */
    public function testLoadClassNamespaceCollision($namespaces, $className, $message)
    {
        $loader = new ClassLoader();
        $loader->addPrefixes($namespaces);

        $loader->loadClass($className);
        $this->assertTrue(class_exists($className), $message);
    }

    public function getLoadClassNamespaceCollisionTests()
    {
        return array(
            array(
                array(
                    'NamespaceCollision\\C' => __DIR__.DIRECTORY_SEPARATOR.'Fixtures/alpha',
                    'NamespaceCollision\\C\\B' => __DIR__.DIRECTORY_SEPARATOR.'Fixtures/beta',
                ),
                'NamespaceCollision\C\Foo',
                '->loadClass() loads NamespaceCollision\C\Foo from alpha.',
            ),
            array(
                array(
                    'NamespaceCollision\\C\\B' => __DIR__.DIRECTORY_SEPARATOR.'Fixtures/beta',
                    'NamespaceCollision\\C' => __DIR__.DIRECTORY_SEPARATOR.'Fixtures/alpha',
                ),
                'NamespaceCollision\C\Bar',
                '->loadClass() loads NamespaceCollision\C\Bar from alpha.',
            ),
            array(
                array(
                    'NamespaceCollision\\C' => __DIR__.DIRECTORY_SEPARATOR.'Fixtures/alpha',
                    'NamespaceCollision\\C\\B' => __DIR__.DIRECTORY_SEPARATOR.'Fixtures/beta',
                ),
                'NamespaceCollision\C\B\Foo',
                '->loadClass() loads NamespaceCollision\C\B\Foo from beta.',
            ),
            array(
                array(
                    'NamespaceCollision\\C\\B' => __DIR__.DIRECTORY_SEPARATOR.'Fixtures/beta',
                    'NamespaceCollision\\C' => __DIR__.DIRECTORY_SEPARATOR.'Fixtures/alpha',
                ),
                'NamespaceCollision\C\B\Bar',
                '->loadClass() loads NamespaceCollision\C\B\Bar from beta.',
            ),
            array(
                array(
                    'PrefixCollision_C_' => __DIR__.DIRECTORY_SEPARATOR.'Fixtures/alpha',
                    'PrefixCollision_C_B_' => __DIR__.DIRECTORY_SEPARATOR.'Fixtures/beta',
                ),
                'PrefixCollision_C_Foo',
                '->loadClass() loads PrefixCollision_C_Foo from alpha.',
            ),
            array(
                array(
                    'PrefixCollision_C_B_' => __DIR__.DIRECTORY_SEPARATOR.'Fixtures/beta',
                    'PrefixCollision_C_' => __DIR__.DIRECTORY_SEPARATOR.'Fixtures/alpha',
                ),
                'PrefixCollision_C_Bar',
                '->loadClass() loads PrefixCollision_C_Bar from alpha.',
            ),
            array(
                array(
                    'PrefixCollision_C_' => __DIR__.DIRECTORY_SEPARATOR.'Fixtures/alpha',
                    'PrefixCollision_C_B_' => __DIR__.DIRECTORY_SEPARATOR.'Fixtures/beta',
                ),
                'PrefixCollision_C_B_Foo',
                '->loadClass() loads PrefixCollision_C_B_Foo from beta.',
            ),
            array(
                array(
                    'PrefixCollision_C_B_' => __DIR__.DIRECTORY_SEPARATOR.'Fixtures/beta',
                    'PrefixCollision_C_' => __DIR__.DIRECTORY_SEPARATOR.'Fixtures/alpha',
                ),
                'PrefixCollision_C_B_Bar',
                '->loadClass() loads PrefixCollision_C_B_Bar from beta.',
            ),
        );
    }
}
