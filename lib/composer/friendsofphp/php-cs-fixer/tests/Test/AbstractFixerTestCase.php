<?php

/*
 * This file is part of PHP CS Fixer.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *     Dariusz Rumiński <dariusz.ruminski@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace PhpCsFixer\Tests\Test;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\Linter\CachingLinter;
use PhpCsFixer\Linter\Linter;
use PhpCsFixer\Linter\LinterInterface;
use PhpCsFixer\Tests\Test\Assert\AssertTokensTrait;
use PhpCsFixer\Tests\TestCase;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use Prophecy\Argument;

/**
 * @author Dariusz Rumiński <dariusz.ruminski@gmail.com>
 *
 * @internal
 */
abstract class AbstractFixerTestCase extends TestCase
{
    use AssertTokensTrait;
    use IsIdenticalConstraint;

    /**
     * @var null|LinterInterface
     */
    protected $linter;

    /**
     * @var null|AbstractFixer
     */
    protected $fixer;

    protected function setUp()
    {
        parent::setUp();

        $this->linter = $this->getLinter();
        $this->fixer = $this->createFixer();

        // @todo remove at 3.0 together with env var itself
        if (getenv('PHP_CS_FIXER_TEST_USE_LEGACY_TOKENIZER')) {
            Tokens::setLegacyMode(true);
        }
    }

    protected function tearDown()
    {
        parent::tearDown();

        $this->linter = null;
        $this->fixer = null;

        // @todo remove at 3.0
        Tokens::setLegacyMode(false);
    }

    /**
     * @return AbstractFixer
     */
    protected function createFixer()
    {
        $fixerClassName = preg_replace('/^(PhpCsFixer)\\\\Tests(\\\\.+)Test$/', '$1$2', static::class);

        return new $fixerClassName();
    }

    /**
     * @param string $filename
     *
     * @return \SplFileInfo
     */
    protected function getTestFile($filename = __FILE__)
    {
        static $files = [];

        if (!isset($files[$filename])) {
            $files[$filename] = new \SplFileInfo($filename);
        }

        return $files[$filename];
    }

    /**
     * Tests if a fixer fixes a given string to match the expected result.
     *
     * It is used both if you want to test if something is fixed or if it is not touched by the fixer.
     * It also makes sure that the expected output does not change when run through the fixer. That means that you
     * do not need two test cases like [$expected] and [$expected, $input] (where $expected is the same in both cases)
     * as the latter covers both of them.
     * This method throws an exception if $expected and $input are equal to prevent test cases that accidentally do
     * not test anything.
     *
     * @param string            $expected The expected fixer output
     * @param null|string       $input    The fixer input, or null if it should intentionally be equal to the output
     * @param null|\SplFileInfo $file     The file to fix, or null if unneeded
     */
    protected function doTest($expected, $input = null, \SplFileInfo $file = null)
    {
        if ($expected === $input) {
            throw new \InvalidArgumentException('Input parameter must not be equal to expected parameter.');
        }

        $file = $file ?: $this->getTestFile();
        $fileIsSupported = $this->fixer->supports($file);

        if (null !== $input) {
            static::assertNull($this->lintSource($input));

            Tokens::clearCache();
            $tokens = Tokens::fromCode($input);

            if ($fileIsSupported) {
                static::assertTrue($this->fixer->isCandidate($tokens), 'Fixer must be a candidate for input code.');
                static::assertFalse($tokens->isChanged(), 'Fixer must not touch Tokens on candidate check.');
                $fixResult = $this->fixer->fix($file, $tokens);
                static::assertNull($fixResult, '->fix method must return null.');
            }

            static::assertThat(
                $tokens->generateCode(),
                self::createIsIdenticalStringConstraint($expected),
                'Code build on input code must match expected code.'
            );
            static::assertTrue($tokens->isChanged(), 'Tokens collection built on input code must be marked as changed after fixing.');

            $tokens->clearEmptyTokens();

            static::assertSame(
                \count($tokens),
                \count(array_unique(array_map(static function (Token $token) {
                    return spl_object_hash($token);
                }, $tokens->toArray()))),
                'Token items inside Tokens collection must be unique.'
            );

            Tokens::clearCache();
            $expectedTokens = Tokens::fromCode($expected);
            static::assertTokens($expectedTokens, $tokens);
        }

        static::assertNull($this->lintSource($expected));

        Tokens::clearCache();
        $tokens = Tokens::fromCode($expected);

        if ($fileIsSupported) {
            $fixResult = $this->fixer->fix($file, $tokens);
            static::assertNull($fixResult, '->fix method must return null.');
        }

        static::assertThat(
            $tokens->generateCode(),
            self::createIsIdenticalStringConstraint($expected),
            'Code build on expected code must not change.'
        );
        static::assertFalse($tokens->isChanged(), 'Tokens collection built on expected code must not be marked as changed after fixing.');
    }

    /**
     * @param string $source
     *
     * @return null|string
     */
    protected function lintSource($source)
    {
        try {
            $this->linter->lintSource($source)->check();
        } catch (\Exception $e) {
            return $e->getMessage()."\n\nSource:\n{$source}";
        }

        return null;
    }

    /**
     * @return LinterInterface
     */
    private function getLinter()
    {
        static $linter = null;

        if (null === $linter) {
            if (getenv('SKIP_LINT_TEST_CASES')) {
                $linterProphecy = $this->prophesize(\PhpCsFixer\Linter\LinterInterface::class);
                $linterProphecy
                    ->lintSource(Argument::type('string'))
                    ->willReturn($this->prophesize(\PhpCsFixer\Linter\LintingResultInterface::class)->reveal())
                ;

                $linter = $linterProphecy->reveal();
            } else {
                $linter = new CachingLinter(new Linter());
            }
        }

        return $linter;
    }
}
