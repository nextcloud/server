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

use Symfony\Component\ClassLoader\UniversalClassLoader;

class UniversalClassLoaderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider getLoadClassTests
     */
    public function testLoadClass($className, $testClassName, $message)
    {
        $loader = new UniversalClassLoader();
        $loader->registerNamespace('Namespaced', __DIR__.DIRECTORY_SEPARATOR.'Fixtures');
        $loader->registerPrefix('Pearlike_', __DIR__.DIRECTORY_SEPARATOR.'Fixtures');
        $this->assertTrue($loader->loadClass($testClassName));
        $this->assertTrue(class_exists($className), $message);
    }

    public function getLoadClassTests()
    {
        return array(
            array('\\Namespaced\\Foo', 'Namespaced\\Foo',   '->loadClass() loads Namespaced\Foo class'),
            array('\\Pearlike_Foo',    'Pearlike_Foo',      '->loadClass() loads Pearlike_Foo class'),
        );
    }

    public function testUseIncludePath()
    {
        $loader = new UniversalClassLoader();
        $this->assertFalse($loader->getUseIncludePath());

        $this->assertNull($loader->findFile('Foo'));

        $includePath = get_include_path();

        $loader->useIncludePath(true);
        $this->assertTrue($loader->getUseIncludePath());

        set_include_path(__DIR__.'/Fixtures/includepath'.PATH_SEPARATOR.$includePath);

        $this->assertEquals(__DIR__.DIRECTORY_SEPARATOR.'Fixtures'.DIRECTORY_SEPARATOR.'includepath'.DIRECTORY_SEPARATOR.'Foo.php', $loader->findFile('Foo'));

        set_include_path($includePath);
    }

    public function testGetNamespaces()
    {
        $loader = new UniversalClassLoader();
        $loader->registerNamespace('Foo', __DIR__.DIRECTORY_SEPARATOR.'Fixtures');
        $loader->registerNamespace('Bar', __DIR__.DIRECTORY_SEPARATOR.'Fixtures');
        $loader->registerNamespace('Bas', __DIR__.DIRECTORY_SEPARATOR.'Fixtures');
        $namespaces = $loader->getNamespaces();
        $this->assertArrayHasKey('Foo', $namespaces);
        $this->assertArrayNotHasKey('Foo1', $namespaces);
        $this->assertArrayHasKey('Bar', $namespaces);
        $this->assertArrayHasKey('Bas', $namespaces);
    }

    public function testGetPrefixes()
    {
        $loader = new UniversalClassLoader();
        $loader->registerPrefix('Foo', __DIR__.DIRECTORY_SEPARATOR.'Fixtures');
        $loader->registerPrefix('Bar', __DIR__.DIRECTORY_SEPARATOR.'Fixtures');
        $loader->registerPrefix('Bas', __DIR__.DIRECTORY_SEPARATOR.'Fixtures');
        $prefixes = $loader->getPrefixes();
        $this->assertArrayHasKey('Foo', $prefixes);
        $this->assertArrayNotHasKey('Foo1', $prefixes);
        $this->assertArrayHasKey('Bar', $prefixes);
        $this->assertArrayHasKey('Bas', $prefixes);
    }

    /**
     * @dataProvider getLoadClassFromFallbackTests
     */
    public function testLoadClassFromFallback($className, $testClassName, $message)
    {
        $loader = new UniversalClassLoader();
        $loader->registerNamespace('Namespaced', __DIR__.DIRECTORY_SEPARATOR.'Fixtures');
        $loader->registerPrefix('Pearlike_', __DIR__.DIRECTORY_SEPARATOR.'Fixtures');
        $loader->registerNamespaceFallbacks(array(__DIR__.DIRECTORY_SEPARATOR.'Fixtures/fallback'));
        $loader->registerPrefixFallbacks(array(__DIR__.DIRECTORY_SEPARATOR.'Fixtures/fallback'));
        $this->assertTrue($loader->loadClass($testClassName));
        $this->assertTrue(class_exists($className), $message);
    }

    public function getLoadClassFromFallbackTests()
    {
        return array(
            array('\\Namespaced\\Baz',    'Namespaced\\Baz',    '->loadClass() loads Namespaced\Baz class'),
            array('\\Pearlike_Baz',       'Pearlike_Baz',       '->loadClass() loads Pearlike_Baz class'),
            array('\\Namespaced\\FooBar', 'Namespaced\\FooBar', '->loadClass() loads Namespaced\Baz class from fallback dir'),
            array('\\Pearlike_FooBar',    'Pearlike_FooBar',    '->loadClass() loads Pearlike_Baz class from fallback dir'),
        );
    }

    public function testRegisterPrefixFallback()
    {
        $loader = new UniversalClassLoader();
        $loader->registerPrefixFallback(__DIR__.DIRECTORY_SEPARATOR.'Fixtures/fallback');
        $this->assertEquals(array(__DIR__.DIRECTORY_SEPARATOR.'Fixtures/fallback'), $loader->getPrefixFallbacks());
    }

    public function testRegisterNamespaceFallback()
    {
        $loader = new UniversalClassLoader();
        $loader->registerNamespaceFallback(__DIR__.DIRECTORY_SEPARATOR.'Fixtures/Namespaced/fallback');
        $this->assertEquals(array(__DIR__.DIRECTORY_SEPARATOR.'Fixtures/Namespaced/fallback'), $loader->getNamespaceFallbacks());
    }

    /**
     * @dataProvider getLoadClassNamespaceCollisionTests
     */
    public function testLoadClassNamespaceCollision($namespaces, $className, $message)
    {
        $loader = new UniversalClassLoader();
        $loader->registerNamespaces($namespaces);

        $this->assertTrue($loader->loadClass($className));
        $this->assertTrue(class_exists($className), $message);
    }

    public function getLoadClassNamespaceCollisionTests()
    {
        return array(
            array(
                array(
                    'NamespaceCollision\\A' => __DIR__.DIRECTORY_SEPARATOR.'Fixtures/alpha',
                    'NamespaceCollision\\A\\B' => __DIR__.DIRECTORY_SEPARATOR.'Fixtures/beta',
                ),
                'NamespaceCollision\A\Foo',
                '->loadClass() loads NamespaceCollision\A\Foo from alpha.',
            ),
            array(
                array(
                    'NamespaceCollision\\A\\B' => __DIR__.DIRECTORY_SEPARATOR.'Fixtures/beta',
                    'NamespaceCollision\\A' => __DIR__.DIRECTORY_SEPARATOR.'Fixtures/alpha',
                ),
                'NamespaceCollision\A\Bar',
                '->loadClass() loads NamespaceCollision\A\Bar from alpha.',
            ),
            array(
                array(
                    'NamespaceCollision\\A' => __DIR__.DIRECTORY_SEPARATOR.'Fixtures/alpha',
                    'NamespaceCollision\\A\\B' => __DIR__.DIRECTORY_SEPARATOR.'Fixtures/beta',
                ),
                'NamespaceCollision\A\B\Foo',
                '->loadClass() loads NamespaceCollision\A\B\Foo from beta.',
            ),
            array(
                array(
                    'NamespaceCollision\\A\\B' => __DIR__.DIRECTORY_SEPARATOR.'Fixtures/beta',
                    'NamespaceCollision\\A' => __DIR__.DIRECTORY_SEPARATOR.'Fixtures/alpha',
                ),
                'NamespaceCollision\A\B\Bar',
                '->loadClass() loads NamespaceCollision\A\B\Bar from beta.',
            ),
        );
    }

    /**
     * @dataProvider getLoadClassPrefixCollisionTests
     */
    public function testLoadClassPrefixCollision($prefixes, $className, $message)
    {
        $loader = new UniversalClassLoader();
        $loader->registerPrefixes($prefixes);

        $this->assertTrue($loader->loadClass($className));
        $this->assertTrue(class_exists($className), $message);
    }

    public function getLoadClassPrefixCollisionTests()
    {
        return array(
            array(
                array(
                    'PrefixCollision_A_' => __DIR__.DIRECTORY_SEPARATOR.'Fixtures/alpha',
                    'PrefixCollision_A_B_' => __DIR__.DIRECTORY_SEPARATOR.'Fixtures/beta',
                ),
                'PrefixCollision_A_Foo',
                '->loadClass() loads PrefixCollision_A_Foo from alpha.',
            ),
            array(
                array(
                    'PrefixCollision_A_B_' => __DIR__.DIRECTORY_SEPARATOR.'Fixtures/beta',
                    'PrefixCollision_A_' => __DIR__.DIRECTORY_SEPARATOR.'Fixtures/alpha',
                ),
                'PrefixCollision_A_Bar',
                '->loadClass() loads PrefixCollision_A_Bar from alpha.',
            ),
            array(
                array(
                    'PrefixCollision_A_' => __DIR__.DIRECTORY_SEPARATOR.'Fixtures/alpha',
                    'PrefixCollision_A_B_' => __DIR__.DIRECTORY_SEPARATOR.'Fixtures/beta',
                ),
                'PrefixCollision_A_B_Foo',
                '->loadClass() loads PrefixCollision_A_B_Foo from beta.',
            ),
            array(
                array(
                    'PrefixCollision_A_B_' => __DIR__.DIRECTORY_SEPARATOR.'Fixtures/beta',
                    'PrefixCollision_A_' => __DIR__.DIRECTORY_SEPARATOR.'Fixtures/alpha',
                ),
                'PrefixCollision_A_B_Bar',
                '->loadClass() loads PrefixCollision_A_B_Bar from beta.',
            ),
        );
    }
}
