<?php
namespace Psalm\Internal\PhpVisitor;

use function count;
use PhpParser;
use function preg_replace;
use function reset;
use function strlen;
use function strpos;
use function strrpos;
use function substr;
use function substr_count;

/**
 * Given a list of file diffs, this scans an AST to find the sections it can replace, and parses
 * just those methods.
 */
class PartialParserVisitor extends PhpParser\NodeVisitorAbstract
{
    /** @var array<int, array{0: int, 1: int, 2: int, 3: int, 4:int}> */
    private $offset_map;

    /** @var bool */
    private $must_rescan = false;

    /** @var int */
    private $non_method_changes;

    /** @var string */
    private $a_file_contents;

    /** @var string */
    private $b_file_contents;

    /** @var int */
    private $a_file_contents_length;

    /** @var PhpParser\Parser */
    private $parser;

    /** @var PhpParser\ErrorHandler\Collecting */
    private $error_handler;

    /** @param array<int, array{0: int, 1: int, 2: int, 3: int, 4:int}> $offset_map */
    public function __construct(
        PhpParser\Parser $parser,
        PhpParser\ErrorHandler\Collecting $error_handler,
        array $offset_map,
        string $a_file_contents,
        string $b_file_contents
    ) {
        $this->parser = $parser;
        $this->error_handler = $error_handler;
        $this->offset_map = $offset_map;
        $this->a_file_contents = $a_file_contents;
        $this->a_file_contents_length = strlen($a_file_contents);
        $this->b_file_contents = $b_file_contents;
        $this->non_method_changes = count($offset_map);
    }

    /**
     * @param  bool $traverseChildren
     *
     * @return null|int|PhpParser\Node
     */
    public function enterNode(PhpParser\Node $node, &$traverseChildren = true)
    {
        /** @var array{startFilePos: int, endFilePos: int, startLine: int} */
        $attrs = $node->getAttributes();

        if ($cs = $node->getComments()) {
            $stmt_start_pos = $cs[0]->getStartFilePos();
        } else {
            $stmt_start_pos = $attrs['startFilePos'];
        }

        $stmt_end_pos = $attrs['endFilePos'];

        $start_offset = 0;
        $end_offset = 0;

        $line_offset = 0;

        foreach ($this->offset_map as [$a_s, $a_e, $b_s, $b_e, $line_diff]) {
            if ($a_s > $stmt_end_pos) {
                break;
            }

            $end_offset = $b_e - $a_e;

            if ($a_s < $stmt_start_pos) {
                $start_offset = $b_s - $a_s;
            }

            if ($a_e < $stmt_start_pos) {
                $start_offset = $end_offset;

                $line_offset = $line_diff;

                continue;
            }

            if ($node instanceof PhpParser\Node\Stmt\ClassMethod
                || $node instanceof PhpParser\Node\Stmt\Namespace_
                || $node instanceof PhpParser\Node\Stmt\ClassLike
            ) {
                if ($node instanceof PhpParser\Node\Stmt\ClassMethod) {
                    if ($a_s >= $stmt_start_pos && $a_e <= $stmt_end_pos) {
                        foreach ($this->offset_map as [$a_s2, $a_e2, $b_s2, $b_e2]) {
                            if ($a_s2 > $stmt_end_pos) {
                                break;
                            }

                            // we have a diff that goes outside the bounds that we care about
                            if ($a_e2 > $stmt_end_pos) {
                                $this->must_rescan = true;

                                return PhpParser\NodeTraverser::STOP_TRAVERSAL;
                            }

                            $end_offset = $b_e2 - $a_e2;

                            if ($a_s2 < $stmt_start_pos) {
                                $start_offset = $b_s2 - $a_s2;
                            }

                            if ($a_e2 < $stmt_start_pos) {
                                $start_offset = $end_offset;

                                $line_offset = $line_diff;

                                continue;
                            }

                            if ($a_s2 >= $stmt_start_pos && $a_e2 <= $stmt_end_pos) {
                                --$this->non_method_changes;
                            }
                        }

                        $stmt_start_pos += $start_offset;
                        $stmt_end_pos += $end_offset;

                        $current_line = substr_count(substr($this->b_file_contents, 0, $stmt_start_pos), "\n");

                        $method_contents = substr(
                            $this->b_file_contents,
                            $stmt_start_pos,
                            $stmt_end_pos - $stmt_start_pos + 1
                        );

                        if (!$method_contents) {
                            $this->must_rescan = true;

                            return PhpParser\NodeTraverser::STOP_TRAVERSAL;
                        }

                        $error_handler = new \PhpParser\ErrorHandler\Collecting();

                        $fake_class = '<?php class _ {' . $method_contents . '}';

                        $replacement_stmts = $this->parser->parse(
                            $fake_class,
                            $error_handler
                        ) ?: [];

                        if (!$replacement_stmts
                            || !$replacement_stmts[0] instanceof PhpParser\Node\Stmt\ClassLike
                            || count($replacement_stmts[0]->stmts) !== 1
                        ) {
                            $hacky_class_fix = self::balanceBrackets($fake_class);

                            if ($replacement_stmts
                                && $replacement_stmts[0] instanceof PhpParser\Node\Stmt\ClassLike
                                && count($replacement_stmts[0]->stmts) !== 1
                            ) {
                                $this->must_rescan = true;

                                return PhpParser\NodeTraverser::STOP_TRAVERSAL;
                            }

                            // changes "): {" to ") {"
                            $hacky_class_fix = preg_replace('/(\)[\s]*):([\s]*\{)/', '$1 $2', $hacky_class_fix);

                            // allows autocompletion
                            $hacky_class_fix = preg_replace('/(->|::)(\n\s*if\s*\()/', '~;$2', $hacky_class_fix);

                            if ($hacky_class_fix !== $fake_class) {
                                $replacement_stmts = $this->parser->parse(
                                    $hacky_class_fix,
                                    $error_handler
                                ) ?: [];
                            }

                            if (!$replacement_stmts
                                || !$replacement_stmts[0] instanceof PhpParser\Node\Stmt\ClassLike
                                || count($replacement_stmts[0]->stmts) > 1
                            ) {
                                $this->must_rescan = true;

                                return PhpParser\NodeTraverser::STOP_TRAVERSAL;
                            }
                        }

                        $replacement_stmts = $replacement_stmts[0]->stmts;

                        $renumbering_traverser = new PhpParser\NodeTraverser;
                        $position_shifter = new \Psalm\Internal\PhpVisitor\OffsetShifterVisitor(
                            $stmt_start_pos - 15,
                            $current_line
                        );
                        $renumbering_traverser->addVisitor($position_shifter);
                        $replacement_stmts = $renumbering_traverser->traverse($replacement_stmts);

                        if ($error_handler->hasErrors()) {
                            foreach ($error_handler->getErrors() as $error) {
                                if ($error->hasColumnInfo()) {
                                    /** @var array{startFilePos: int, endFilePos: int} */
                                    $error_attrs = $error->getAttributes();
                                    /** @psalm-suppress MixedOperand */
                                    $error = new PhpParser\Error(
                                        $error->getRawMessage(),
                                        [
                                            'startFilePos' => $stmt_start_pos + $error_attrs['startFilePos'] - 15,
                                            'endFilePos' => $stmt_start_pos + $error_attrs['endFilePos'] - 15,
                                            'startLine' => $error->getStartLine() + $current_line + $line_offset,
                                        ]
                                    );
                                }

                                $this->error_handler->handleError($error);
                            }
                        }

                        $error_handler->clearErrors();

                        $traverseChildren = false;

                        return reset($replacement_stmts);
                    }

                    $this->must_rescan = true;

                    return PhpParser\NodeTraverser::STOP_TRAVERSAL;
                }

                if ($node->stmts) {
                    /** @var int */
                    $stmt_inner_start_pos = $node->stmts[0]->getAttribute('startFilePos');

                    /** @var int */
                    $stmt_inner_end_pos = $node->stmts[count($node->stmts) - 1]->getAttribute('endFilePos');

                    if ($node instanceof PhpParser\Node\Stmt\ClassLike) {
                        /** @psalm-suppress PossiblyFalseOperand */
                        $stmt_inner_start_pos = strrpos(
                            $this->a_file_contents,
                            '{',
                            $stmt_inner_start_pos - $this->a_file_contents_length
                        ) + 1;

                        if ($stmt_inner_end_pos < $this->a_file_contents_length) {
                            $stmt_inner_end_pos = strpos($this->a_file_contents, '}', $stmt_inner_end_pos + 1);
                        }
                    }

                    if ($a_s > $stmt_inner_start_pos && $a_e < $stmt_inner_end_pos) {
                        continue;
                    }
                }
            }

            $this->must_rescan = true;

            return PhpParser\NodeTraverser::STOP_TRAVERSAL;
        }

        if ($start_offset !== 0 || $end_offset !== 0 || $line_offset !== 0) {
            if ($start_offset !== 0) {
                if ($cs) {
                    $new_comments = [];

                    foreach ($cs as $c) {
                        if ($c instanceof PhpParser\Comment\Doc) {
                            $new_comments[] = new PhpParser\Comment\Doc(
                                $c->getText(),
                                $c->getStartLine() + $line_offset,
                                $c->getStartFilePos() + $start_offset
                            );
                        } else {
                            $new_comments[] = new PhpParser\Comment(
                                $c->getText(),
                                $c->getStartLine() + $line_offset,
                                $c->getStartFilePos() + $start_offset
                            );
                        }
                    }

                    $node->setAttribute('comments', $new_comments);

                    /** @psalm-suppress MixedOperand */
                    $node->setAttribute('startFilePos', $attrs['startFilePos'] + $start_offset);
                } else {
                    $node->setAttribute('startFilePos', $stmt_start_pos + $start_offset);
                }
            }

            if ($end_offset !== 0) {
                $node->setAttribute('endFilePos', $stmt_end_pos + $end_offset);
            }

            if ($line_offset !== 0) {
                /** @psalm-suppress MixedOperand */
                $node->setAttribute('startLine', $attrs['startLine'] + $line_offset);
            }

            return $node;
        }

        return null;
    }

    public function mustRescan() : bool
    {
        return $this->must_rescan || $this->non_method_changes;
    }

    /**
     * @psalm-pure
     */
    private static function balanceBrackets(string $fake_class) : string
    {
        $tokens = \token_get_all($fake_class);

        $brace_count = 0;

        foreach ($tokens as $token) {
            if ($token === '{') {
                ++$brace_count;
            } elseif ($token === '}') {
                --$brace_count;
            }
        }

        if ($brace_count > 0) {
            $fake_class .= \str_repeat('}', $brace_count);
        }

        return $fake_class;
    }
}
