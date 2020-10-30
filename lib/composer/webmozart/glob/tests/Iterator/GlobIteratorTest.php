<?php

/*
 * This file is part of the webmozart/glob package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webmozart\Glob\Tests\Iterator;

use PHPUnit_Framework_TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Webmozart\Glob\Glob;
use Webmozart\Glob\Iterator\GlobIterator;
use Webmozart\Glob\Test\TestUtil;

/**
 * @since  1.0
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class GlobIteratorTest extends PHPUnit_Framework_TestCase
{
    private $tempDir;

    private $tempFile;

    protected function setUp()
    {
        $this->tempDir = TestUtil::makeTempDir('webmozart-glob', __CLASS__);

        $filesystem = new Filesystem();
        $filesystem->mirror(__DIR__.'/../Fixtures', $this->tempDir);

        $this->tempFile = tempnam(sys_get_temp_dir(), 'webmozart_GlobIteratorTest');
    }

    protected function tearDown()
    {
        $filesystem = new Filesystem();
        $filesystem->remove($this->tempDir);
        $filesystem->remove($this->tempFile);
    }

    public function testIterate()
    {
        $iterator = new GlobIterator($this->tempDir.'/*.css');

        $this->assertSameAfterSorting(array(
            $this->tempDir.'/base.css',
        ), iterator_to_array($iterator));
    }

    public function testIterateEscaped()
    {
        if (defined('PHP_WINDOWS_VERSION_MAJOR')) {
            $this->markTestSkipped('A "*" in filenames is not supported on Windows.');

            return;
        }

        touch($this->tempDir.'/css/style*.css');

        $iterator = new GlobIterator($this->tempDir.'/css/style\\*.css');

        $this->assertSameAfterSorting(array(
            $this->tempDir.'/css/style*.css',
        ), iterator_to_array($iterator));
    }

    public function testIterateSpecialChars()
    {
        if (defined('PHP_WINDOWS_VERSION_MAJOR')) {
            $this->markTestSkipped('A "*" in filenames is not supported on Windows.');

            return;
        }

        touch($this->tempDir.'/css/style*.css');

        $iterator = new GlobIterator($this->tempDir.'/css/style*.css');

        $this->assertSameAfterSorting(array(
            $this->tempDir.'/css/style*.css',
            $this->tempDir.'/css/style.css',
        ), iterator_to_array($iterator));
    }

    public function testIterateDoubleWildcard()
    {
        $iterator = new GlobIterator($this->tempDir.'/**/*.css');

        $this->assertSameAfterSorting(array(
            $this->tempDir.'/base.css',
            $this->tempDir.'/css/reset.css',
            $this->tempDir.'/css/style.css',
        ), iterator_to_array($iterator));
    }

    public function testIterateSingleDirectory()
    {
        $iterator = new GlobIterator($this->tempDir.'/css');

        $this->assertSame(array(
            $this->tempDir.'/css',
        ), iterator_to_array($iterator));
    }

    public function testIterateSingleFile()
    {
        $iterator = new GlobIterator($this->tempDir.'/css/style.css');

        $this->assertSame(array(
            $this->tempDir.'/css/style.css',
        ), iterator_to_array($iterator));
    }

    public function testIterateSingleFileInDirectoryWithUnreadableFiles()
    {
        $iterator = new GlobIterator($this->tempFile);

        $this->assertSame(array(
            $this->tempFile,
        ), iterator_to_array($iterator));
    }

    public function testWildcardMayMatchZeroCharacters()
    {
        $iterator = new GlobIterator($this->tempDir.'/*css');

        $this->assertSameAfterSorting(array(
            $this->tempDir.'/base.css',
            $this->tempDir.'/css',
        ), iterator_to_array($iterator));
    }

    public function testDoubleWildcardMayMatchZeroCharacters()
    {
        $iterator = new GlobIterator($this->tempDir.'/**/*css');

        $this->assertSameAfterSorting(array(
            $this->tempDir.'/base.css',
            $this->tempDir.'/css',
            $this->tempDir.'/css/reset.css',
            $this->tempDir.'/css/style.css',
        ), iterator_to_array($iterator));
    }

    public function testWildcardInRoot()
    {
        $iterator = new GlobIterator($this->tempDir.'/*');

        $this->assertSameAfterSorting(array(
            $this->tempDir.'/base.css',
            $this->tempDir.'/css',
            $this->tempDir.'/js',
        ), iterator_to_array($iterator));
    }

    public function testDoubleWildcardInRoot()
    {
        $iterator = new GlobIterator($this->tempDir.'/**/*');

        $this->assertSameAfterSorting(array(
            $this->tempDir.'/base.css',
            $this->tempDir.'/css',
            $this->tempDir.'/css/reset.css',
            $this->tempDir.'/css/style.css',
            $this->tempDir.'/css/style.cts',
            $this->tempDir.'/css/style.cxs',
            $this->tempDir.'/js',
            $this->tempDir.'/js/script.js',
        ), iterator_to_array($iterator));
    }

    public function testNoMatches()
    {
        $iterator = new GlobIterator($this->tempDir.'/foo*');

        $this->assertSame(array(), iterator_to_array($iterator));
    }

    public function testNonExistingBaseDirectory()
    {
        $iterator = new GlobIterator($this->tempDir.'/foo/*');

        $this->assertSame(array(), iterator_to_array($iterator));
    }

    /**
     * Compares that an array is the same as another after sorting.
     *
     * This is necessary since RecursiveDirectoryIterator is not guaranteed to
     * return sorted results on all filesystems.
     *
     * @param mixed  $expected
     * @param mixed  $actual
     * @param string $message
     */
    private function assertSameAfterSorting($expected, $actual, $message = '')
    {
        if (is_array($actual)) {
            sort($actual);
        }

        $this->assertSame($expected, $actual, $message);
    }
}
