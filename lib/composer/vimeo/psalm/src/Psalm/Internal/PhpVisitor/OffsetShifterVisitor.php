<?php
namespace Psalm\Internal\PhpVisitor;

use PhpParser;

/**
 * Shifts all nodes in a given AST by a set amount
 */
class OffsetShifterVisitor extends PhpParser\NodeVisitorAbstract
{
    /** @var int */
    private $file_offset;

    /** @var int */
    private $line_offset;

    public function __construct(int $offset, int $line_offset)
    {
        $this->file_offset = $offset;
        $this->line_offset = $line_offset;
    }

    /**
     * @return null|int
     */
    public function enterNode(PhpParser\Node $node)
    {
        /** @var array{startFilePos: int, endFilePos: int, startLine: int} */
        $attrs = $node->getAttributes();

        if ($cs = $node->getComments()) {
            $new_comments = [];

            foreach ($cs as $c) {
                if ($c instanceof PhpParser\Comment\Doc) {
                    $new_comments[] = new PhpParser\Comment\Doc(
                        $c->getText(),
                        $c->getStartLine() + $this->line_offset,
                        $c->getStartFilePos() + $this->file_offset
                    );
                } else {
                    $new_comments[] = new PhpParser\Comment(
                        $c->getText(),
                        $c->getStartLine() + $this->line_offset,
                        $c->getStartFilePos() + $this->file_offset
                    );
                }
            }

            $node->setAttribute('comments', $new_comments);
        }

        /**
         * @psalm-suppress MixedOperand
         */
        $node->setAttribute('startFilePos', $attrs['startFilePos'] + $this->file_offset);
        /** @psalm-suppress MixedOperand */
        $node->setAttribute('endFilePos', $attrs['endFilePos'] + $this->file_offset);
        /** @psalm-suppress MixedOperand */
        $node->setAttribute('startLine', $attrs['startLine'] + $this->line_offset);
    }
}
