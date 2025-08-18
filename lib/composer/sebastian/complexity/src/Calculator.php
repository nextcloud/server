<?php declare(strict_types=1);
/*
 * This file is part of sebastian/complexity.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\Complexity;

use function assert;
use function file_get_contents;
use PhpParser\Error;
use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\NodeVisitor\ParentConnectingVisitor;
use PhpParser\ParserFactory;

final class Calculator
{
    /**
     * @throws RuntimeException
     */
    public function calculateForSourceFile(string $sourceFile): ComplexityCollection
    {
        return $this->calculateForSourceString(file_get_contents($sourceFile));
    }

    /**
     * @throws RuntimeException
     */
    public function calculateForSourceString(string $source): ComplexityCollection
    {
        try {
            $nodes = (new ParserFactory)->createForHostVersion()->parse($source);

            assert($nodes !== null);

            return $this->calculateForAbstractSyntaxTree($nodes);

            // @codeCoverageIgnoreStart
        } catch (Error $error) {
            throw new RuntimeException(
                $error->getMessage(),
                $error->getCode(),
                $error,
            );
        }
        // @codeCoverageIgnoreEnd
    }

    /**
     * @param Node[] $nodes
     *
     * @throws RuntimeException
     */
    public function calculateForAbstractSyntaxTree(array $nodes): ComplexityCollection
    {
        $traverser                    = new NodeTraverser;
        $complexityCalculatingVisitor = new ComplexityCalculatingVisitor(true);

        $traverser->addVisitor(new NameResolver);
        $traverser->addVisitor(new ParentConnectingVisitor);
        $traverser->addVisitor($complexityCalculatingVisitor);

        try {
            /* @noinspection UnusedFunctionResultInspection */
            $traverser->traverse($nodes);
            // @codeCoverageIgnoreStart
        } catch (Error $error) {
            throw new RuntimeException(
                $error->getMessage(),
                $error->getCode(),
                $error,
            );
        }
        // @codeCoverageIgnoreEnd

        return $complexityCalculatingVisitor->result();
    }
}
