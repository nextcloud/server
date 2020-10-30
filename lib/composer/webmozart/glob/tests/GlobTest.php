<?php

/*
 * This file is part of the webmozart/glob package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webmozart\Glob\Tests;

use PHPUnit_Framework_TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Webmozart\Glob\Glob;
use Webmozart\Glob\Test\TestUtil;

/**
 * @since  1.0
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class GlobTest extends PHPUnit_Framework_TestCase
{
    private $tempDir;

    protected function setUp()
    {
        $this->tempDir = TestUtil::makeTempDir('webmozart-glob', __CLASS__);

        $filesystem = new Filesystem();
        $filesystem->mirror(__DIR__.'/Fixtures', $this->tempDir);

        TestStreamWrapper::register('globtest', __DIR__.'/Fixtures');
    }

    protected function tearDown()
    {
        $filesystem = new Filesystem();
        $filesystem->remove($this->tempDir);

        TestStreamWrapper::unregister('globtest');
    }

    public function testGlob()
    {
        $this->assertSame(array(
            $this->tempDir.'/base.css',
        ), Glob::glob($this->tempDir.'/*.css'));

        $this->assertSame(array(
            $this->tempDir.'/base.css',
            $this->tempDir.'/css',
        ), Glob::glob($this->tempDir.'/*css*'));

        $this->assertSame(array(
            $this->tempDir.'/css/reset.css',
            $this->tempDir.'/css/style.css',
        ), Glob::glob($this->tempDir.'/*/*.css'));

        $this->assertSame(array(
            $this->tempDir.'/css/reset.css',
            $this->tempDir.'/css/style.css',
            $this->tempDir.'/css/style.cts',
            $this->tempDir.'/css/style.cxs',
        ), Glob::glob($this->tempDir.'/*/*.c?s'));

        $this->assertSame(array(
            $this->tempDir.'/css/reset.css',
            $this->tempDir.'/css/style.css',
            $this->tempDir.'/css/style.cts',
        ), Glob::glob($this->tempDir.'/*/*.c[st]s'));

        $this->assertSame(array(
            $this->tempDir.'/css/style.cts',
        ), Glob::glob($this->tempDir.'/*/*.c[t]s'));

        $this->assertSame(array(
            $this->tempDir.'/css/style.cts',
            $this->tempDir.'/css/style.cxs',
        ), Glob::glob($this->tempDir.'/*/*.c[t-x]s'));

        $this->assertSame(array(
            $this->tempDir.'/css/style.cts',
            $this->tempDir.'/css/style.cxs',
        ), Glob::glob($this->tempDir.'/*/*.c[^s]s'));

        $this->assertSame(array(
            $this->tempDir.'/css/reset.css',
            $this->tempDir.'/css/style.css',
        ), Glob::glob($this->tempDir.'/*/*.c[^t-x]s'));

        $this->assertSame(array(
            $this->tempDir.'/css/reset.css',
            $this->tempDir.'/css/style.css',
        ), Glob::glob($this->tempDir.'/*/**/*.css'));

        $this->assertSame(array(
            $this->tempDir.'/base.css',
            $this->tempDir.'/css/reset.css',
            $this->tempDir.'/css/style.css',
        ), Glob::glob($this->tempDir.'/**/*.css'));

        $this->assertSame(array(
            $this->tempDir.'/base.css',
            $this->tempDir.'/css',
            $this->tempDir.'/css/reset.css',
            $this->tempDir.'/css/style.css',
        ), Glob::glob($this->tempDir.'/**/*css'));

        $this->assertSame(array(
            $this->tempDir.'/base.css',
            $this->tempDir.'/css/reset.css',
        ), Glob::glob($this->tempDir.'/**/{base,reset}.css'));

        $this->assertSame(array(
            $this->tempDir.'/css',
            $this->tempDir.'/css/reset.css',
            $this->tempDir.'/css/style.css',
            $this->tempDir.'/css/style.cts',
            $this->tempDir.'/css/style.cxs',
        ), Glob::glob($this->tempDir.'/css{,/**/*}'));

        $this->assertSame(array(), Glob::glob($this->tempDir.'/*foo*'));
    }

    public function testGlobStreamWrapper()
    {
        $this->assertSame(array(
            'globtest:///base.css',
        ), Glob::glob('globtest:///*.css'));

        $this->assertSame(array(
            'globtest:///base.css',
            'globtest:///css',
        ), Glob::glob('globtest:///*css*'));

        $this->assertSame(array(
            'globtest:///css/reset.css',
            'globtest:///css/style.css',
        ), Glob::glob('globtest:///*/*.css'));

        $this->assertSame(array(
            'globtest:///css/reset.css',
            'globtest:///css/style.css',
            'globtest:///css/style.cts',
            'globtest:///css/style.cxs',
        ), Glob::glob('globtest:///*/*.c?s'));

        $this->assertSame(array(
            'globtest:///css/reset.css',
            'globtest:///css/style.css',
            'globtest:///css/style.cts',
        ), Glob::glob('globtest:///*/*.c[st]s'));

        $this->assertSame(array(
            'globtest:///css/style.cts',
        ), Glob::glob('globtest:///*/*.c[t]s'));

        $this->assertSame(array(
            'globtest:///css/style.cts',
            'globtest:///css/style.cxs',
        ), Glob::glob('globtest:///*/*.c[t-x]s'));

        $this->assertSame(array(
            'globtest:///css/style.cts',
            'globtest:///css/style.cxs',
        ), Glob::glob('globtest:///*/*.c[^s]s'));

        $this->assertSame(array(
            'globtest:///css/reset.css',
            'globtest:///css/style.css',
        ), Glob::glob('globtest:///*/*.c[^t-x]s'));

        $this->assertSame(array(
            'globtest:///css/reset.css',
            'globtest:///css/style.css',
        ), Glob::glob('globtest:///*/**/*.css'));

        $this->assertSame(array(
            'globtest:///base.css',
            'globtest:///css/reset.css',
            'globtest:///css/style.css',
        ), Glob::glob('globtest:///**/*.css'));

        $this->assertSame(array(
            'globtest:///base.css',
            'globtest:///css',
            'globtest:///css/reset.css',
            'globtest:///css/style.css',
        ), Glob::glob('globtest:///**/*css'));

        $this->assertSame(array(
            'globtest:///base.css',
            'globtest:///css/reset.css',
        ), Glob::glob('globtest:///**/{base,reset}.css'));

        $this->assertSame(array(
            'globtest:///css',
            'globtest:///css/reset.css',
            'globtest:///css/style.css',
            'globtest:///css/style.cts',
            'globtest:///css/style.cxs',
        ), Glob::glob('globtest:///css{,/**/*}'));

        $this->assertSame(array(), Glob::glob('globtest:///*foo*'));
    }

    public function testGlobEscape()
    {
        if (defined('PHP_WINDOWS_VERSION_MAJOR')) {
            $this->markTestSkipped('A "*" in filenames is not supported on Windows.');

            return;
        }

        touch($this->tempDir.'/css/style*.css');
        touch($this->tempDir.'/css/style{.css');
        touch($this->tempDir.'/css/style}.css');
        touch($this->tempDir.'/css/style?.css');
        touch($this->tempDir.'/css/style[.css');
        touch($this->tempDir.'/css/style].css');
        touch($this->tempDir.'/css/style^.css');

        $this->assertSame(array(
            $this->tempDir.'/css/style*.css',
            $this->tempDir.'/css/style.css',
            $this->tempDir.'/css/style?.css',
            $this->tempDir.'/css/style[.css',
            $this->tempDir.'/css/style].css',
            $this->tempDir.'/css/style^.css',
            $this->tempDir.'/css/style{.css',
            $this->tempDir.'/css/style}.css',
        ), Glob::glob($this->tempDir.'/css/style*.css'));

        $this->assertSame(array(
            $this->tempDir.'/css/style*.css',
        ), Glob::glob($this->tempDir.'/css/style\\*.css'));

        $this->assertSame(array(
            $this->tempDir.'/css/style{.css',
        ), Glob::glob($this->tempDir.'/css/style\\{.css'));

        $this->assertSame(array(
            $this->tempDir.'/css/style}.css',
        ), Glob::glob($this->tempDir.'/css/style\\}.css'));

        $this->assertSame(array(
            $this->tempDir.'/css/style?.css',
        ), Glob::glob($this->tempDir.'/css/style\\?.css'));

        $this->assertSame(array(
            $this->tempDir.'/css/style[.css',
        ), Glob::glob($this->tempDir.'/css/style\\[.css'));

        $this->assertSame(array(
            $this->tempDir.'/css/style].css',
        ), Glob::glob($this->tempDir.'/css/style\\].css'));

        $this->assertSame(array(
            $this->tempDir.'/css/style^.css',
        ), Glob::glob($this->tempDir.'/css/style\\^.css'));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testNativeGlobThrowsExceptionIfUnclosedBrace()
    {
        // native impl
        $this->assertSame(array(), Glob::glob($this->tempDir.'/*.cs{t,s'));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testCustomGlobThrowsExceptionIfUnclosedBrace()
    {
        // custom impl
        $this->assertSame(array(), Glob::glob($this->tempDir.'/**/*.cs{t,s'));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testNativeGlobThrowsExceptionIfUnclosedBracket()
    {
        // native impl
        $this->assertSame(array(), Glob::glob($this->tempDir.'/*.cs[ts'));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testCustomGlobThrowsExceptionIfUnclosedBracket()
    {
        // custom impl
        $this->assertSame(array(), Glob::glob($this->tempDir.'/**/*.cs[ts'));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage *.css
     */
    public function testGlobFailsIfNotAbsolute()
    {
        Glob::glob('*.css');
    }

    /**
     * @dataProvider provideWildcardMatches
     */
    public function testToRegEx($path, $isMatch)
    {
        $regExp = Glob::toRegEx('/foo/*.js~');

        $this->assertSame($isMatch, preg_match($regExp, $path));
    }

    /**
     * @dataProvider provideDoubleWildcardMatches
     */
    public function testToRegExDoubleWildcard($path, $isMatch)
    {
        $regExp = Glob::toRegEx('/foo/**/*.js~');

        $this->assertSame($isMatch, preg_match($regExp, $path));
    }

    public function provideWildcardMatches()
    {
        return array(
            // The method assumes that the path is already consolidated
            array('/bar/baz.js~', 0),
            array('/foo/baz.js~', 1),
            array('/foo/../bar/baz.js~', 0),
            array('/foo/../foo/baz.js~', 0),
            array('/bar/baz.js', 0),
            array('/foo/bar/baz.js~', 0),
            array('foo/baz.js~', 0),
            array('/bar/foo/baz.js~', 0),
            array('/bar/.js~', 0),
        );
    }

    public function provideDoubleWildcardMatches()
    {
        return array(
            array('/bar/baz.js~', 0),
            array('/foo/baz.js~', 1),
            array('/foo/../bar/baz.js~', 1),
            array('/foo/../foo/baz.js~', 1),
            array('/bar/baz.js', 0),
            array('/foo/bar/baz.js~', 1),
            array('foo/baz.js~', 0),
            array('/bar/foo/baz.js~', 0),
            array('/bar/.js~', 0),
        );
    }

    // From the PHP manual: To specify a literal single quote, escape it with a
    // backslash (\). To specify a literal backslash, double it (\\).
    // All other instances of backslash will be treated as a literal backslash

    public function testEscapedWildcard()
    {
        // evaluates to "\*"
        $regExp = Glob::toRegEx('/foo/\\*.js~');

        $this->assertSame(0, preg_match($regExp, '/foo/baz.js~'));
        $this->assertSame(1, preg_match($regExp, '/foo/*.js~'));
        $this->assertSame(0, preg_match($regExp, '/foo/\\baz.js~'));
        $this->assertSame(0, preg_match($regExp, '/foo/\\*.js~'));
    }

    public function testEscapedWildcard2()
    {
        // evaluates to "\*"
        $regExp = Glob::toRegEx('/foo/\*.js~');

        $this->assertSame(0, preg_match($regExp, '/foo/baz.js~'));
        $this->assertSame(1, preg_match($regExp, '/foo/*.js~'));
        $this->assertSame(0, preg_match($regExp, '/foo/\\baz.js~'));
        $this->assertSame(0, preg_match($regExp, '/foo/\\*.js~'));
    }

    public function testMatchEscapedWildcard()
    {
        // evaluates to "\*"
        $regExp = Glob::toRegEx('/foo/\\*.js~');

        $this->assertSame(1, preg_match($regExp, '/foo/*.js~'));
    }

    public function testMatchEscapedDoubleWildcard()
    {
        // evaluates to "\*\*"
        $regExp = Glob::toRegEx('/foo/\\*\\*.js~');

        $this->assertSame(1, preg_match($regExp, '/foo/**.js~'));
    }

    public function testMatchWildcardWithLeadingBackslash()
    {
        // evaluates to "\\*"
        $regExp = Glob::toRegEx('/foo/\\\\*.js~');

        $this->assertSame(1, preg_match($regExp, '/foo/\\baz.js~'));
        $this->assertSame(1, preg_match($regExp, '/foo/\baz.js~'));
        $this->assertSame(0, preg_match($regExp, '/foo/baz.js~'));
    }

    public function testMatchWildcardWithLeadingBackslash2()
    {
        // evaluates to "\\*"
        $regExp = Glob::toRegEx('/foo/\\\*.js~');

        $this->assertSame(1, preg_match($regExp, '/foo/\\baz.js~'));
        $this->assertSame(1, preg_match($regExp, '/foo/\baz.js~'));
        $this->assertSame(0, preg_match($regExp, '/foo/baz.js~'));
    }

    public function testMatchEscapedWildcardWithLeadingBackslash()
    {
        // evaluates to "\\\*"
        $regExp = Glob::toRegEx('/foo/\\\\\\*.js~');

        $this->assertSame(1, preg_match($regExp, '/foo/\\*.js~'));
        $this->assertSame(1, preg_match($regExp, '/foo/\*.js~'));
        $this->assertSame(0, preg_match($regExp, '/foo/*.js~'));
        $this->assertSame(0, preg_match($regExp, '/foo/\\baz.js~'));
        $this->assertSame(0, preg_match($regExp, '/foo/\baz.js~'));
    }

    public function testMatchWildcardWithTwoLeadingBackslashes()
    {
        // evaluates to "\\\\*"
        $regExp = Glob::toRegEx('/foo/\\\\\\\\*.js~');

        $this->assertSame(1, preg_match($regExp, '/foo/\\\\baz.js~'));
        $this->assertSame(1, preg_match($regExp, '/foo/\\\baz.js~'));
        $this->assertSame(0, preg_match($regExp, '/foo/\\baz.js~'));
        $this->assertSame(0, preg_match($regExp, '/foo/\baz.js~'));
        $this->assertSame(0, preg_match($regExp, '/foo/baz.js~'));
    }

    public function testMatchEscapedWildcardWithTwoLeadingBackslashes()
    {
        // evaluates to "\\\\*"
        $regExp = Glob::toRegEx('/foo/\\\\\\\\\\*.js~');

        $this->assertSame(1, preg_match($regExp, '/foo/\\\\*.js~'));
        $this->assertSame(1, preg_match($regExp, '/foo/\\\*.js~'));
        $this->assertSame(0, preg_match($regExp, '/foo/\\*.js~'));
        $this->assertSame(0, preg_match($regExp, '/foo/\*.js~'));
        $this->assertSame(0, preg_match($regExp, '/foo/*.js~'));
        $this->assertSame(0, preg_match($regExp, '/foo/\\\\baz.js~'));
        $this->assertSame(0, preg_match($regExp, '/foo/\\\baz.js~'));
    }

    public function testMatchEscapedLeftBrace()
    {
        $regExp = Glob::toRegEx('/foo/\\{.js~');

        $this->assertSame(1, preg_match($regExp, '/foo/{.js~'));
    }

    public function testMatchLeftBraceWithLeadingBackslash()
    {
        $regExp = Glob::toRegEx('/foo/\\\\{b,c}az.js~');

        $this->assertSame(1, preg_match($regExp, '/foo/\\baz.js~'));
        $this->assertSame(1, preg_match($regExp, '/foo/\baz.js~'));
        $this->assertSame(0, preg_match($regExp, '/foo/baz.js~'));
    }

    public function testMatchEscapedLeftBraceWithLeadingBackslash()
    {
        $regExp = Glob::toRegEx('/foo/\\\\\\{b,c}az.js~');

        $this->assertSame(0, preg_match($regExp, '/foo/\\baz.js~'));
        $this->assertSame(0, preg_match($regExp, '/foo/\baz.js~'));
        $this->assertSame(0, preg_match($regExp, '/foo/baz.js~'));
        $this->assertSame(1, preg_match($regExp, '/foo/\\{b,c}az.js~'));
        $this->assertSame(1, preg_match($regExp, '/foo/\{b,c}az.js~'));
        $this->assertSame(0, preg_match($regExp, '/foo/{b,c}az.js~'));
    }

    public function testMatchUnescapedRightBraceWithoutLeadingLeftBrace()
    {
        $regExp = Glob::toRegEx('/foo/}.js~');

        $this->assertSame(1, preg_match($regExp, '/foo/}.js~'));
    }

    public function testMatchEscapedRightBrace()
    {
        $regExp = Glob::toRegEx('/foo/\\}.js~');

        $this->assertSame(1, preg_match($regExp, '/foo/}.js~'));
    }

    public function testMatchRightBraceWithLeadingBackslash()
    {
        $regExp = Glob::toRegEx('/foo/{b,c\\\\}az.js~');

        $this->assertSame(1, preg_match($regExp, '/foo/baz.js~'));
        $this->assertSame(1, preg_match($regExp, '/foo/c\\az.js~'));
        $this->assertSame(1, preg_match($regExp, '/foo/c\az.js~'));
        $this->assertSame(0, preg_match($regExp, '/foo/caz.js~'));
    }

    public function testMatchEscapedRightBraceWithLeadingBackslash()
    {
        $regExp = Glob::toRegEx('/foo/{b,c\\\\\\}}az.js~');

        $this->assertSame(1, preg_match($regExp, '/foo/baz.js~'));
        $this->assertSame(1, preg_match($regExp, '/foo/c\\}az.js~'));
        $this->assertSame(1, preg_match($regExp, '/foo/c\}az.js~'));
        $this->assertSame(0, preg_match($regExp, '/foo/c\\az.js~'));
        $this->assertSame(0, preg_match($regExp, '/foo/c\az.js~'));
    }

    public function testCloseBracesAsSoonAsPossible()
    {
        $regExp = Glob::toRegEx('/foo/{b,c}}az.js~');

        $this->assertSame(1, preg_match($regExp, '/foo/b}az.js~'));
        $this->assertSame(1, preg_match($regExp, '/foo/c}az.js~'));
        $this->assertSame(0, preg_match($regExp, '/foo/baz.js~'));
        $this->assertSame(0, preg_match($regExp, '/foo/caz.js~'));
    }

    public function testMatchEscapedQuestionMark()
    {
        $regExp = Glob::toRegEx('/foo/\\?.js~');

        $this->assertSame(1, preg_match($regExp, '/foo/?.js~'));
    }

    public function testMatchQuestionMarkWithLeadingBackslash()
    {
        $regExp = Glob::toRegEx('/foo/\\\\?az.js~');

        $this->assertSame(1, preg_match($regExp, '/foo/\\baz.js~'));
        $this->assertSame(1, preg_match($regExp, '/foo/\baz.js~'));
        $this->assertSame(1, preg_match($regExp, '/foo/\\caz.js~'));
        $this->assertSame(1, preg_match($regExp, '/foo/\caz.js~'));
        $this->assertSame(0, preg_match($regExp, '/foo/baz.js~'));
        $this->assertSame(0, preg_match($regExp, '/foo/caz.js~'));
    }

    public function testMatchEscapedQuestionMarkWithLeadingBackslash()
    {
        $regExp = Glob::toRegEx('/foo/\\\\\\??az.js~');

        $this->assertSame(1, preg_match($regExp, '/foo/\\?baz.js~'));
        $this->assertSame(1, preg_match($regExp, '/foo/\?baz.js~'));
        $this->assertSame(1, preg_match($regExp, '/foo/\\?caz.js~'));
        $this->assertSame(1, preg_match($regExp, '/foo/\?caz.js~'));
        $this->assertSame(0, preg_match($regExp, '/foo/\\baz.js~'));
        $this->assertSame(0, preg_match($regExp, '/foo/\baz.js~'));
        $this->assertSame(0, preg_match($regExp, '/foo/\\caz.js~'));
        $this->assertSame(0, preg_match($regExp, '/foo/\caz.js~'));
        $this->assertSame(0, preg_match($regExp, '/foo/baz.js~'));
        $this->assertSame(0, preg_match($regExp, '/foo/caz.js~'));
    }

    public function testMatchEscapedLeftBracket()
    {
        $regExp = Glob::toRegEx('/foo/\\[.js~');

        $this->assertSame(1, preg_match($regExp, '/foo/[.js~'));
    }

    public function testMatchLeftBracketWithLeadingBackslash()
    {
        $regExp = Glob::toRegEx('/foo/\\\\[bc]az.js~');

        $this->assertSame(1, preg_match($regExp, '/foo/\\baz.js~'));
        $this->assertSame(1, preg_match($regExp, '/foo/\baz.js~'));
        $this->assertSame(0, preg_match($regExp, '/foo/baz.js~'));
    }

    public function testMatchEscapedLeftBracketWithLeadingBackslash()
    {
        $regExp = Glob::toRegEx('/foo/\\\\\\[bc]az.js~');

        $this->assertSame(0, preg_match($regExp, '/foo/\\baz.js~'));
        $this->assertSame(0, preg_match($regExp, '/foo/\baz.js~'));
        $this->assertSame(0, preg_match($regExp, '/foo/baz.js~'));
        $this->assertSame(1, preg_match($regExp, '/foo/\\[bc]az.js~'));
        $this->assertSame(1, preg_match($regExp, '/foo/\[bc]az.js~'));
        $this->assertSame(0, preg_match($regExp, '/foo/[bc]az.js~'));
    }

    public function testMatchUnescapedRightBracketWithoutLeadingLeftBracket()
    {
        $regExp = Glob::toRegEx('/foo/].js~');

        $this->assertSame(1, preg_match($regExp, '/foo/].js~'));
    }

    public function testMatchEscapedRightBracket()
    {
        $regExp = Glob::toRegEx('/foo/\\].js~');

        $this->assertSame(1, preg_match($regExp, '/foo/].js~'));
    }

    public function testMatchRightBracketWithLeadingBackslash()
    {
        $regExp = Glob::toRegEx('/foo/[bc\\\\]az.js~');

        $this->assertSame(1, preg_match($regExp, '/foo/baz.js~'));
        $this->assertSame(1, preg_match($regExp, '/foo/caz.js~'));
        $this->assertSame(1, preg_match($regExp, '/foo/\\az.js~'));
        $this->assertSame(0, preg_match($regExp, '/foo/bc\\az.js~'));
        $this->assertSame(0, preg_match($regExp, '/foo/c\\az.js~'));
        $this->assertSame(0, preg_match($regExp, '/foo/az.js~'));
    }

    public function testMatchEscapedRightBracketWithLeadingBackslash()
    {
        $regExp = Glob::toRegEx('/foo/[bc\\\\\\]]az.js~');

        $this->assertSame(1, preg_match($regExp, '/foo/baz.js~'));
        $this->assertSame(1, preg_match($regExp, '/foo/caz.js~'));
        $this->assertSame(1, preg_match($regExp, '/foo/\\az.js~'));
        $this->assertSame(1, preg_match($regExp, '/foo/]az.js~'));
        $this->assertSame(0, preg_match($regExp, '/foo/bc\\]az.js~'));
        $this->assertSame(0, preg_match($regExp, '/foo/c\\]az.js~'));
        $this->assertSame(0, preg_match($regExp, '/foo/\\]az.js~'));
    }

    public function testMatchUnescapedCaretWithoutLeadingLeftBracket()
    {
        $regExp = Glob::toRegEx('/foo/^.js~');

        $this->assertSame(1, preg_match($regExp, '/foo/^.js~'));
    }

    public function testMatchEscapedCaret()
    {
        $regExp = Glob::toRegEx('/foo/\\^.js~');

        $this->assertSame(1, preg_match($regExp, '/foo/^.js~'));
    }

    public function testMatchCaretWithLeadingBackslash()
    {
        $regExp = Glob::toRegEx('/foo/[\\\\^]az.js~');

        $this->assertSame(1, preg_match($regExp, '/foo/\\az.js~'));
        $this->assertSame(1, preg_match($regExp, '/foo/^az.js~'));
        $this->assertSame(0, preg_match($regExp, '/foo/az.js~'));
    }

    public function testMatchEscapedCaretWithLeadingBackslash()
    {
        $regExp = Glob::toRegEx('/foo/[\\\\\\^]az.js~');

        $this->assertSame(1, preg_match($regExp, '/foo/\\az.js~'));
        $this->assertSame(1, preg_match($regExp, '/foo/^az.js~'));
        $this->assertSame(0, preg_match($regExp, '/foo/az.js~'));
    }

    public function testMatchUnescapedHyphenWithoutLeadingLeftBracket()
    {
        $regExp = Glob::toRegEx('/foo/-.js~');

        $this->assertSame(1, preg_match($regExp, '/foo/-.js~'));
    }

    public function testMatchEscapedHyphen()
    {
        $regExp = Glob::toRegEx('/foo/\\-.js~');

        $this->assertSame(1, preg_match($regExp, '/foo/-.js~'));
    }

    public function testMatchHyphenWithLeadingBackslash()
    {
        // range from "\" to "a"
        $regExp = Glob::toRegEx('/foo/[\\\\-a]az.js~');

        $this->assertSame(1, preg_match($regExp, '/foo/\\az.js~'));
        $this->assertSame(1, preg_match($regExp, '/foo/aaz.js~'));
        $this->assertSame(0, preg_match($regExp, '/foo/baz.js~'));
        $this->assertSame(0, preg_match($regExp, '/foo/caz.js~'));
    }

    public function testMatchEscapedHyphenWithLeadingBackslash()
    {
        $regExp = Glob::toRegEx('/foo/[\\\\\\-]az.js~');

        $this->assertSame(1, preg_match($regExp, '/foo/\\az.js~'));
        $this->assertSame(1, preg_match($regExp, '/foo/-az.js~'));
        $this->assertSame(0, preg_match($regExp, '/foo/baz.js~'));
    }

    public function testCloseBracketsAsSoonAsPossible()
    {
        $regExp = Glob::toRegEx('/foo/[bc]]az.js~');

        $this->assertSame(1, preg_match($regExp, '/foo/b]az.js~'));
        $this->assertSame(1, preg_match($regExp, '/foo/c]az.js~'));
        $this->assertSame(0, preg_match($regExp, '/foo/baz.js~'));
        $this->assertSame(0, preg_match($regExp, '/foo/caz.js~'));
    }

    public function testMatchCharacterRanges()
    {
        $regExp = Glob::toRegEx('/foo/[a-c]az.js~');

        $this->assertSame(1, preg_match($regExp, '/foo/aaz.js~'));
        $this->assertSame(1, preg_match($regExp, '/foo/baz.js~'));
        $this->assertSame(1, preg_match($regExp, '/foo/caz.js~'));
        $this->assertSame(0, preg_match($regExp, '/foo/daz.js~'));
        $this->assertSame(0, preg_match($regExp, '/foo/eaz.js~'));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage *.css
     */
    public function testToRegexFailsIfNotAbsolute()
    {
        Glob::toRegEx('*.css');
    }

    /**
     * @dataProvider provideStaticPrefixes
     */
    public function testGetStaticPrefix($glob, $prefix)
    {
        $this->assertSame($prefix, Glob::getStaticPrefix($glob));
    }

    public function provideStaticPrefixes()
    {
        return array(
            // The method assumes that the path is already consolidated
            array('/foo/baz/../*/bar/*', '/foo/baz/../'),
            array('/foo/baz/bar\\*', '/foo/baz/bar*'),
            array('/foo/baz/bar\\\\*', '/foo/baz/bar\\'),
            array('/foo/baz/bar\\\\\\*', '/foo/baz/bar\\*'),
            array('/foo/baz/bar\\\\\\\\*', '/foo/baz/bar\\\\'),
            array('/foo/baz/bar\\*\\\\', '/foo/baz/bar*\\'),
            array('/foo/baz/bar\\{a,b}', '/foo/baz/bar{a,b}'),
            array('/foo/baz/bar\\\\{a,b}', '/foo/baz/bar\\'),
            array('/foo/baz/bar\\\\\\{a,b}', '/foo/baz/bar\\{a,b}'),
            array('/foo/baz/bar\\\\\\\\{a,b}', '/foo/baz/bar\\\\'),
            array('/foo/baz/bar\\{a,b}\\\\', '/foo/baz/bar{a,b}\\'),
            array('/foo/baz/bar\\?', '/foo/baz/bar?'),
            array('/foo/baz/bar\\\\?', '/foo/baz/bar\\'),
            array('/foo/baz/bar\\\\\\?', '/foo/baz/bar\\?'),
            array('/foo/baz/bar\\\\\\\\?', '/foo/baz/bar\\\\'),
            array('/foo/baz/bar\\?\\\\', '/foo/baz/bar?\\'),
            array('/foo/baz/bar\\[ab]', '/foo/baz/bar[ab]'),
            array('/foo/baz/bar\\\\[ab]', '/foo/baz/bar\\'),
            array('/foo/baz/bar\\\\\\[ab]', '/foo/baz/bar\\[ab]'),
            array('/foo/baz/bar\\\\\\\\[ab]', '/foo/baz/bar\\\\'),
            array('/foo/baz/bar\\[ab]\\\\', '/foo/baz/bar[ab]\\'),
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage *.css
     */
    public function testGetStaticPrefixFailsIfNotAbsolute()
    {
        Glob::getStaticPrefix('*.css');
    }

    /**
     * @dataProvider provideBasePaths
     */
    public function testGetBasePath($glob, $basePath, $flags = 0)
    {
        $this->assertSame($basePath, Glob::getBasePath($glob, $flags));
    }

    /**
     * @dataProvider provideBasePaths
     */
    public function testGetBasePathStream($glob, $basePath)
    {
        $this->assertSame('globtest://'.$basePath, Glob::getBasePath('globtest://'.$glob));
    }

    public function provideBasePaths()
    {
        return array(
            // The method assumes that the path is already consolidated
            array('/foo/baz/../*/bar/*', '/foo/baz/..'),
            array('/foo/baz/bar*', '/foo/baz'),
            array('/foo/baz/bar', '/foo/baz'),
            array('/foo/baz*', '/foo'),
            array('/foo*', '/'),
            array('/*', '/'),
            array('/foo/baz*/bar', '/foo'),
            array('/foo/baz\\*/bar', '/foo/baz*'),
            array('/foo/baz\\\\*/bar', '/foo'),
            array('/foo/baz\\\\\\*/bar', '/foo/baz\\*'),
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage *.css
     */
    public function testGetBasePathFailsIfNotAbsolute()
    {
        Glob::getBasePath('*.css');
    }

    /**
     * @dataProvider provideDoubleWildcardMatches
     */
    public function testMatch($path, $isMatch)
    {
        $this->assertSame((bool) $isMatch, Glob::match($path, '/foo/**/*.js~'));
    }

    public function testMatchPathWithoutWildcard()
    {
        $this->assertTrue(Glob::match('/foo/bar.js~', '/foo/bar.js~'));
        $this->assertFalse(Glob::match('/foo/bar.js', '/foo/bar.js~'));
    }

    public function testMatchEscaped()
    {
        $this->assertTrue(Glob::match('/foo/bar*.js~', '/foo/bar*.js~'));
        $this->assertTrue(Glob::match('/foo/bar\\*.js~', '/foo/bar*.js~'));
        $this->assertTrue(Glob::match('/foo/bar\\baz.js~', '/foo/bar*.js~'));
        $this->assertTrue(Glob::match('/foo/bar*.js~', '/foo/bar\\*.js~'));
        $this->assertFalse(Glob::match('/foo/bar\\*.js~', '/foo/bar\\*.js~'));
        $this->assertFalse(Glob::match('/foo/bar\\baz.js~', '/foo/bar\\*.js~'));
        $this->assertFalse(Glob::match('/foo/bar*.js~', '/foo/bar\\\\*.js~'));
        $this->assertTrue(Glob::match('/foo/bar\\*.js~', '/foo/bar\\\\*.js~'));
        $this->assertTrue(Glob::match('/foo/bar\\baz.js~', '/foo/bar\\\\*.js~'));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage *.css
     */
    public function testMatchFailsIfNotAbsolute()
    {
        Glob::match('/foo/bar.css', '*.css');
    }

    public function testFilter()
    {
        $paths = array();
        $filtered = array();

        // The keys remain the same in the filtered array
        $i = 42;

        foreach ($this->provideDoubleWildcardMatches() as $input) {
            $paths[$i] = $input[0];

            if ($input[1]) {
                $filtered[$i] = $input[0];
            }

            ++$i;
        }

        $this->assertSame($filtered, Glob::filter($paths, '/foo/**/*.js~'));
    }

    public function testFilterWithoutWildcard()
    {
        $paths = array(
            '/foo',
            '/foo/bar.js',
        );

        $this->assertSame(array(1 => '/foo/bar.js'), Glob::filter($paths, '/foo/bar.js'));
        $this->assertSame(array(), Glob::filter($paths, '/foo/bar.js~'));
    }

    public function testFilterEscaped()
    {
        $paths = array(
            '/foo',
            '/foo*.js',
            '/foo/bar.js',
            '/foo/bar*.js',
            '/foo/bar\\*.js',
            '/foo/bar\\baz.js',
        );

        $this->assertSame(array(
            1 => '/foo*.js',
            3 => '/foo/bar*.js',
            4 => '/foo/bar\\*.js',
        ), Glob::filter($paths, '/**/*\\*.js'));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage *.css
     */
    public function testFilterFailsIfNotAbsolute()
    {
        Glob::filter(array('/foo/bar.css'), '*.css');
    }

    public function testFilterKeys()
    {
        $paths = array();
        $filtered = array();

        // The values remain the same in the filtered array
        $i = 42;

        foreach ($this->provideDoubleWildcardMatches() as $input) {
            $paths[$input[0]] = $i;

            if ($input[1]) {
                $filtered[$input[0]] = $i;
            }

            ++$i;
        }

        $this->assertSame($filtered, Glob::filter($paths, '/foo/**/*.js~', Glob::FILTER_KEY));
    }

    public function testFilterKeysWithoutWildcard()
    {
        $paths = array(
            '/foo' => 2,
            '/foo/bar.js' => 3,
        );

        $this->assertSame(array('/foo/bar.js' => 3), Glob::filter($paths, '/foo/bar.js', Glob::FILTER_KEY));
        $this->assertSame(array(), Glob::filter($paths, '/foo/bar.js~', Glob::FILTER_KEY));
    }

    public function testFilterKeysEscaped()
    {
        $paths = array(
            '/foo' => 3,
            '/foo*.js' => 4,
            '/foo/bar.js' => 5,
            '/foo/bar*.js' => 6,
            '/foo/bar\\*.js' => 7,
            '/foo/bar\\baz.js' => 8,
        );

        $this->assertSame(array(
            '/foo*.js' => 4,
            '/foo/bar*.js' => 6,
            '/foo/bar\\*.js' => 7,
        ), Glob::filter($paths, '/**/*\\*.js', Glob::FILTER_KEY));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage *.css
     */
    public function testFilterKeysFailsIfNotAbsolute()
    {
        Glob::filter(array('/foo/bar.css' => 42), '*.css', Glob::FILTER_KEY);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testFilterFailsIfInvalidFlags()
    {
        Glob::filter(array(42 => '/foo/bar.css'), '/foo/*.css', Glob::FILTER_KEY | Glob::FILTER_VALUE);
    }
}
