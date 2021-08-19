<?php declare(strict_types=1);

namespace PhpParser;

use PhpParser\Internal\DiffElem;
use PhpParser\Internal\PrintableNewAnonClassNode;
use PhpParser\Internal\TokenStream;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\AssignOp;
use PhpParser\Node\Expr\BinaryOp;
use PhpParser\Node\Expr\Cast;
use PhpParser\Node\Scalar;
use PhpParser\Node\Stmt;

abstract class PrettyPrinterAbstract
{
    const FIXUP_PREC_LEFT       = 0; // LHS operand affected by precedence
    const FIXUP_PREC_RIGHT      = 1; // RHS operand affected by precedence
    const FIXUP_CALL_LHS        = 2; // LHS of call
    const FIXUP_DEREF_LHS       = 3; // LHS of dereferencing operation
    const FIXUP_BRACED_NAME     = 4; // Name operand that may require bracing
    const FIXUP_VAR_BRACED_NAME = 5; // Name operand that may require ${} bracing
    const FIXUP_ENCAPSED        = 6; // Encapsed string part

    protected $precedenceMap = [
        // [precedence, associativity]
        // where for precedence -1 is %left, 0 is %nonassoc and 1 is %right
        BinaryOp\Pow::class            => [  0,  1],
        Expr\BitwiseNot::class         => [ 10,  1],
        Expr\PreInc::class             => [ 10,  1],
        Expr\PreDec::class             => [ 10,  1],
        Expr\PostInc::class            => [ 10, -1],
        Expr\PostDec::class            => [ 10, -1],
        Expr\UnaryPlus::class          => [ 10,  1],
        Expr\UnaryMinus::class         => [ 10,  1],
        Cast\Int_::class               => [ 10,  1],
        Cast\Double::class             => [ 10,  1],
        Cast\String_::class            => [ 10,  1],
        Cast\Array_::class             => [ 10,  1],
        Cast\Object_::class            => [ 10,  1],
        Cast\Bool_::class              => [ 10,  1],
        Cast\Unset_::class             => [ 10,  1],
        Expr\ErrorSuppress::class      => [ 10,  1],
        Expr\Instanceof_::class        => [ 20,  0],
        Expr\BooleanNot::class         => [ 30,  1],
        BinaryOp\Mul::class            => [ 40, -1],
        BinaryOp\Div::class            => [ 40, -1],
        BinaryOp\Mod::class            => [ 40, -1],
        BinaryOp\Plus::class           => [ 50, -1],
        BinaryOp\Minus::class          => [ 50, -1],
        BinaryOp\Concat::class         => [ 50, -1],
        BinaryOp\ShiftLeft::class      => [ 60, -1],
        BinaryOp\ShiftRight::class     => [ 60, -1],
        BinaryOp\Smaller::class        => [ 70,  0],
        BinaryOp\SmallerOrEqual::class => [ 70,  0],
        BinaryOp\Greater::class        => [ 70,  0],
        BinaryOp\GreaterOrEqual::class => [ 70,  0],
        BinaryOp\Equal::class          => [ 80,  0],
        BinaryOp\NotEqual::class       => [ 80,  0],
        BinaryOp\Identical::class      => [ 80,  0],
        BinaryOp\NotIdentical::class   => [ 80,  0],
        BinaryOp\Spaceship::class      => [ 80,  0],
        BinaryOp\BitwiseAnd::class     => [ 90, -1],
        BinaryOp\BitwiseXor::class     => [100, -1],
        BinaryOp\BitwiseOr::class      => [110, -1],
        BinaryOp\BooleanAnd::class     => [120, -1],
        BinaryOp\BooleanOr::class      => [130, -1],
        BinaryOp\Coalesce::class       => [140,  1],
        Expr\Ternary::class            => [150,  0],
        // parser uses %left for assignments, but they really behave as %right
        Expr\Assign::class             => [160,  1],
        Expr\AssignRef::class          => [160,  1],
        AssignOp\Plus::class           => [160,  1],
        AssignOp\Minus::class          => [160,  1],
        AssignOp\Mul::class            => [160,  1],
        AssignOp\Div::class            => [160,  1],
        AssignOp\Concat::class         => [160,  1],
        AssignOp\Mod::class            => [160,  1],
        AssignOp\BitwiseAnd::class     => [160,  1],
        AssignOp\BitwiseOr::class      => [160,  1],
        AssignOp\BitwiseXor::class     => [160,  1],
        AssignOp\ShiftLeft::class      => [160,  1],
        AssignOp\ShiftRight::class     => [160,  1],
        AssignOp\Pow::class            => [160,  1],
        AssignOp\Coalesce::class       => [160,  1],
        Expr\YieldFrom::class          => [165,  1],
        Expr\Print_::class             => [168,  1],
        BinaryOp\LogicalAnd::class     => [170, -1],
        BinaryOp\LogicalXor::class     => [180, -1],
        BinaryOp\LogicalOr::class      => [190, -1],
        Expr\Include_::class           => [200, -1],
    ];

    /** @var int Current indentation level. */
    protected $indentLevel;
    /** @var string Newline including current indentation. */
    protected $nl;
    /** @var string Token placed at end of doc string to ensure it is followed by a newline. */
    protected $docStringEndToken;
    /** @var bool Whether semicolon namespaces can be used (i.e. no global namespace is used) */
    protected $canUseSemicolonNamespaces;
    /** @var array Pretty printer options */
    protected $options;

    /** @var TokenStream Original tokens for use in format-preserving pretty print */
    protected $origTokens;
    /** @var Internal\Differ Differ for node lists */
    protected $nodeListDiffer;
    /** @var bool[] Map determining whether a certain character is a label character */
    protected $labelCharMap;
    /**
     * @var int[][] Map from token classes and subnode names to FIXUP_* constants. This is used
     *              during format-preserving prints to place additional parens/braces if necessary.
     */
    protected $fixupMap;
    /**
     * @var int[][] Map from "{$node->getType()}->{$subNode}" to ['left' => $l, 'right' => $r],
     *              where $l and $r specify the token type that needs to be stripped when removing
     *              this node.
     */
    protected $removalMap;
    /**
     * @var mixed[] Map from "{$node->getType()}->{$subNode}" to [$find, $beforeToken, $extraLeft, $extraRight].
     *              $find is an optional token after which the insertion occurs. $extraLeft/Right
     *              are optionally added before/after the main insertions.
     */
    protected $insertionMap;
    /**
     * @var string[] Map From "{$node->getType()}->{$subNode}" to string that should be inserted
     *               between elements of this list subnode.
     */
    protected $listInsertionMap;
    protected $emptyListInsertionMap;
    /** @var int[] Map from "{$node->getType()}->{$subNode}" to token before which the modifiers
     *             should be reprinted. */
    protected $modifierChangeMap;

    /**
     * Creates a pretty printer instance using the given options.
     *
     * Supported options:
     *  * bool $shortArraySyntax = false: Whether to use [] instead of array() as the default array
     *                                    syntax, if the node does not specify a format.
     *
     * @param array $options Dictionary of formatting options
     */
    public function __construct(array $options = []) {
        $this->docStringEndToken = '_DOC_STRING_END_' . mt_rand();

        $defaultOptions = ['shortArraySyntax' => false];
        $this->options = $options + $defaultOptions;
    }

    /**
     * Reset pretty printing state.
     */
    protected function resetState() {
        $this->indentLevel = 0;
        $this->nl = "\n";
        $this->origTokens = null;
    }

    /**
     * Set indentation level
     *
     * @param int $level Level in number of spaces
     */
    protected function setIndentLevel(int $level) {
        $this->indentLevel = $level;
        $this->nl = "\n" . \str_repeat(' ', $level);
    }

    /**
     * Increase indentation level.
     */
    protected function indent() {
        $this->indentLevel += 4;
        $this->nl .= '    ';
    }

    /**
     * Decrease indentation level.
     */
    protected function outdent() {
        assert($this->indentLevel >= 4);
        $this->indentLevel -= 4;
        $this->nl = "\n" . str_repeat(' ', $this->indentLevel);
    }

    /**
     * Pretty prints an array of statements.
     *
     * @param Node[] $stmts Array of statements
     *
     * @return string Pretty printed statements
     */
    public function prettyPrint(array $stmts) : string {
        $this->resetState();
        $this->preprocessNodes($stmts);

        return ltrim($this->handleMagicTokens($this->pStmts($stmts, false)));
    }

    /**
     * Pretty prints an expression.
     *
     * @param Expr $node Expression node
     *
     * @return string Pretty printed node
     */
    public function prettyPrintExpr(Expr $node) : string {
        $this->resetState();
        return $this->handleMagicTokens($this->p($node));
    }

    /**
     * Pretty prints a file of statements (includes the opening <?php tag if it is required).
     *
     * @param Node[] $stmts Array of statements
     *
     * @return string Pretty printed statements
     */
    public function prettyPrintFile(array $stmts) : string {
        if (!$stmts) {
            return "<?php\n\n";
        }

        $p = "<?php\n\n" . $this->prettyPrint($stmts);

        if ($stmts[0] instanceof Stmt\InlineHTML) {
            $p = preg_replace('/^<\?php\s+\?>\n?/', '', $p);
        }
        if ($stmts[count($stmts) - 1] instanceof Stmt\InlineHTML) {
            $p = preg_replace('/<\?php$/', '', rtrim($p));
        }

        return $p;
    }

    /**
     * Preprocesses the top-level nodes to initialize pretty printer state.
     *
     * @param Node[] $nodes Array of nodes
     */
    protected function preprocessNodes(array $nodes) {
        /* We can use semicolon-namespaces unless there is a global namespace declaration */
        $this->canUseSemicolonNamespaces = true;
        foreach ($nodes as $node) {
            if ($node instanceof Stmt\Namespace_ && null === $node->name) {
                $this->canUseSemicolonNamespaces = false;
                break;
            }
        }
    }

    /**
     * Handles (and removes) no-indent and doc-string-end tokens.
     *
     * @param string $str
     * @return string
     */
    protected function handleMagicTokens(string $str) : string {
        // Replace doc-string-end tokens with nothing or a newline
        $str = str_replace($this->docStringEndToken . ";\n", ";\n", $str);
        $str = str_replace($this->docStringEndToken, "\n", $str);

        return $str;
    }

    /**
     * Pretty prints an array of nodes (statements) and indents them optionally.
     *
     * @param Node[] $nodes  Array of nodes
     * @param bool   $indent Whether to indent the printed nodes
     *
     * @return string Pretty printed statements
     */
    protected function pStmts(array $nodes, bool $indent = true) : string {
        if ($indent) {
            $this->indent();
        }

        $result = '';
        foreach ($nodes as $node) {
            $comments = $node->getComments();
            if ($comments) {
                $result .= $this->nl . $this->pComments($comments);
                if ($node instanceof Stmt\Nop) {
                    continue;
                }
            }

            $result .= $this->nl . $this->p($node);
        }

        if ($indent) {
            $this->outdent();
        }

        return $result;
    }

    /**
     * Pretty-print an infix operation while taking precedence into account.
     *
     * @param string $class          Node class of operator
     * @param Node   $leftNode       Left-hand side node
     * @param string $operatorString String representation of the operator
     * @param Node   $rightNode      Right-hand side node
     *
     * @return string Pretty printed infix operation
     */
    protected function pInfixOp(string $class, Node $leftNode, string $operatorString, Node $rightNode) : string {
        list($precedence, $associativity) = $this->precedenceMap[$class];

        return $this->pPrec($leftNode, $precedence, $associativity, -1)
             . $operatorString
             . $this->pPrec($rightNode, $precedence, $associativity, 1);
    }

    /**
     * Pretty-print a prefix operation while taking precedence into account.
     *
     * @param string $class          Node class of operator
     * @param string $operatorString String representation of the operator
     * @param Node   $node           Node
     *
     * @return string Pretty printed prefix operation
     */
    protected function pPrefixOp(string $class, string $operatorString, Node $node) : string {
        list($precedence, $associativity) = $this->precedenceMap[$class];
        return $operatorString . $this->pPrec($node, $precedence, $associativity, 1);
    }

    /**
     * Pretty-print a postfix operation while taking precedence into account.
     *
     * @param string $class          Node class of operator
     * @param string $operatorString String representation of the operator
     * @param Node   $node           Node
     *
     * @return string Pretty printed postfix operation
     */
    protected function pPostfixOp(string $class, Node $node, string $operatorString) : string {
        list($precedence, $associativity) = $this->precedenceMap[$class];
        return $this->pPrec($node, $precedence, $associativity, -1) . $operatorString;
    }

    /**
     * Prints an expression node with the least amount of parentheses necessary to preserve the meaning.
     *
     * @param Node $node                Node to pretty print
     * @param int  $parentPrecedence    Precedence of the parent operator
     * @param int  $parentAssociativity Associativity of parent operator
     *                                  (-1 is left, 0 is nonassoc, 1 is right)
     * @param int  $childPosition       Position of the node relative to the operator
     *                                  (-1 is left, 1 is right)
     *
     * @return string The pretty printed node
     */
    protected function pPrec(Node $node, int $parentPrecedence, int $parentAssociativity, int $childPosition) : string {
        $class = \get_class($node);
        if (isset($this->precedenceMap[$class])) {
            $childPrecedence = $this->precedenceMap[$class][0];
            if ($childPrecedence > $parentPrecedence
                || ($parentPrecedence === $childPrecedence && $parentAssociativity !== $childPosition)
            ) {
                return '(' . $this->p($node) . ')';
            }
        }

        return $this->p($node);
    }

    /**
     * Pretty prints an array of nodes and implodes the printed values.
     *
     * @param Node[] $nodes Array of Nodes to be printed
     * @param string $glue  Character to implode with
     *
     * @return string Imploded pretty printed nodes
     */
    protected function pImplode(array $nodes, string $glue = '') : string {
        $pNodes = [];
        foreach ($nodes as $node) {
            if (null === $node) {
                $pNodes[] = '';
            } else {
                $pNodes[] = $this->p($node);
            }
        }

        return implode($glue, $pNodes);
    }

    /**
     * Pretty prints an array of nodes and implodes the printed values with commas.
     *
     * @param Node[] $nodes Array of Nodes to be printed
     *
     * @return string Comma separated pretty printed nodes
     */
    protected function pCommaSeparated(array $nodes) : string {
        return $this->pImplode($nodes, ', ');
    }

    /**
     * Pretty prints a comma-separated list of nodes in multiline style, including comments.
     *
     * The result includes a leading newline and one level of indentation (same as pStmts).
     *
     * @param Node[] $nodes         Array of Nodes to be printed
     * @param bool   $trailingComma Whether to use a trailing comma
     *
     * @return string Comma separated pretty printed nodes in multiline style
     */
    protected function pCommaSeparatedMultiline(array $nodes, bool $trailingComma) : string {
        $this->indent();

        $result = '';
        $lastIdx = count($nodes) - 1;
        foreach ($nodes as $idx => $node) {
            if ($node !== null) {
                $comments = $node->getComments();
                if ($comments) {
                    $result .= $this->nl . $this->pComments($comments);
                }

                $result .= $this->nl . $this->p($node);
            } else {
                $result .= $this->nl;
            }
            if ($trailingComma || $idx !== $lastIdx) {
                $result .= ',';
            }
        }

        $this->outdent();
        return $result;
    }

    /**
     * Prints reformatted text of the passed comments.
     *
     * @param Comment[] $comments List of comments
     *
     * @return string Reformatted text of comments
     */
    protected function pComments(array $comments) : string {
        $formattedComments = [];

        foreach ($comments as $comment) {
            $formattedComments[] = str_replace("\n", $this->nl, $comment->getReformattedText());
        }

        return implode($this->nl, $formattedComments);
    }

    /**
     * Perform a format-preserving pretty print of an AST.
     *
     * The format preservation is best effort. For some changes to the AST the formatting will not
     * be preserved (at least not locally).
     *
     * In order to use this method a number of prerequisites must be satisfied:
     *  * The startTokenPos and endTokenPos attributes in the lexer must be enabled.
     *  * The CloningVisitor must be run on the AST prior to modification.
     *  * The original tokens must be provided, using the getTokens() method on the lexer.
     *
     * @param Node[] $stmts      Modified AST with links to original AST
     * @param Node[] $origStmts  Original AST with token offset information
     * @param array  $origTokens Tokens of the original code
     *
     * @return string
     */
    public function printFormatPreserving(array $stmts, array $origStmts, array $origTokens) : string {
        $this->initializeNodeListDiffer();
        $this->initializeLabelCharMap();
        $this->initializeFixupMap();
        $this->initializeRemovalMap();
        $this->initializeInsertionMap();
        $this->initializeListInsertionMap();
        $this->initializeEmptyListInsertionMap();
        $this->initializeModifierChangeMap();

        $this->resetState();
        $this->origTokens = new TokenStream($origTokens);

        $this->preprocessNodes($stmts);

        $pos = 0;
        $result = $this->pArray($stmts, $origStmts, $pos, 0, 'File', 'stmts', null);
        if (null !== $result) {
            $result .= $this->origTokens->getTokenCode($pos, count($origTokens), 0);
        } else {
            // Fallback
            // TODO Add <?php properly
            $result = "<?php\n" . $this->pStmts($stmts, false);
        }

        return ltrim($this->handleMagicTokens($result));
    }

    protected function pFallback(Node $node) {
        return $this->{'p' . $node->getType()}($node);
    }

    /**
     * Pretty prints a node.
     *
     * This method also handles formatting preservation for nodes.
     *
     * @param Node $node Node to be pretty printed
     * @param bool $parentFormatPreserved Whether parent node has preserved formatting
     *
     * @return string Pretty printed node
     */
    protected function p(Node $node, $parentFormatPreserved = false) : string {
        // No orig tokens means this is a normal pretty print without preservation of formatting
        if (!$this->origTokens) {
            return $this->{'p' . $node->getType()}($node);
        }

        /** @var Node $origNode */
        $origNode = $node->getAttribute('origNode');
        if (null === $origNode) {
            return $this->pFallback($node);
        }

        $class = \get_class($node);
        \assert($class === \get_class($origNode));

        $startPos = $origNode->getStartTokenPos();
        $endPos = $origNode->getEndTokenPos();
        \assert($startPos >= 0 && $endPos >= 0);

        $fallbackNode = $node;
        if ($node instanceof Expr\New_ && $node->class instanceof Stmt\Class_) {
            // Normalize node structure of anonymous classes
            $node = PrintableNewAnonClassNode::fromNewNode($node);
            $origNode = PrintableNewAnonClassNode::fromNewNode($origNode);
        }

        // InlineHTML node does not contain closing and opening PHP tags. If the parent formatting
        // is not preserved, then we need to use the fallback code to make sure the tags are
        // printed.
        if ($node instanceof Stmt\InlineHTML && !$parentFormatPreserved) {
            return $this->pFallback($fallbackNode);
        }

        $indentAdjustment = $this->indentLevel - $this->origTokens->getIndentationBefore($startPos);

        $type = $node->getType();
        $fixupInfo = $this->fixupMap[$class] ?? null;

        $result = '';
        $pos = $startPos;
        foreach ($node->getSubNodeNames() as $subNodeName) {
            $subNode = $node->$subNodeName;
            $origSubNode = $origNode->$subNodeName;

            if ((!$subNode instanceof Node && $subNode !== null)
                || (!$origSubNode instanceof Node && $origSubNode !== null)
            ) {
                if ($subNode === $origSubNode) {
                    // Unchanged, can reuse old code
                    continue;
                }

                if (is_array($subNode) && is_array($origSubNode)) {
                    // Array subnode changed, we might be able to reconstruct it
                    $listResult = $this->pArray(
                        $subNode, $origSubNode, $pos, $indentAdjustment, $type, $subNodeName,
                        $fixupInfo[$subNodeName] ?? null
                    );
                    if (null === $listResult) {
                        return $this->pFallback($fallbackNode);
                    }

                    $result .= $listResult;
                    continue;
                }

                if (is_int($subNode) && is_int($origSubNode)) {
                    // Check if this is a modifier change
                    $key = $type . '->' . $subNodeName;
                    if (!isset($this->modifierChangeMap[$key])) {
                        return $this->pFallback($fallbackNode);
                    }

                    $findToken = $this->modifierChangeMap[$key];
                    $result .= $this->pModifiers($subNode);
                    $pos = $this->origTokens->findRight($pos, $findToken);
                    continue;
                }

                // If a non-node, non-array subnode changed, we don't be able to do a partial
                // reconstructions, as we don't have enough offset information. Pretty print the
                // whole node instead.
                return $this->pFallback($fallbackNode);
            }

            $extraLeft = '';
            $extraRight = '';
            if ($origSubNode !== null) {
                $subStartPos = $origSubNode->getStartTokenPos();
                $subEndPos = $origSubNode->getEndTokenPos();
                \assert($subStartPos >= 0 && $subEndPos >= 0);
            } else {
                if ($subNode === null) {
                    // Both null, nothing to do
                    continue;
                }

                // A node has been inserted, check if we have insertion information for it
                $key = $type . '->' . $subNodeName;
                if (!isset($this->insertionMap[$key])) {
                    return $this->pFallback($fallbackNode);
                }

                list($findToken, $beforeToken, $extraLeft, $extraRight) = $this->insertionMap[$key];
                if (null !== $findToken) {
                    $subStartPos = $this->origTokens->findRight($pos, $findToken)
                        + (int) !$beforeToken;
                } else {
                    $subStartPos = $pos;
                }

                if (null === $extraLeft && null !== $extraRight) {
                    // If inserting on the right only, skipping whitespace looks better
                    $subStartPos = $this->origTokens->skipRightWhitespace($subStartPos);
                }
                $subEndPos = $subStartPos - 1;
            }

            if (null === $subNode) {
                // A node has been removed, check if we have removal information for it
                $key = $type . '->' . $subNodeName;
                if (!isset($this->removalMap[$key])) {
                    return $this->pFallback($fallbackNode);
                }

                // Adjust positions to account for additional tokens that must be skipped
                $removalInfo = $this->removalMap[$key];
                if (isset($removalInfo['left'])) {
                    $subStartPos = $this->origTokens->skipLeft($subStartPos - 1, $removalInfo['left']) + 1;
                }
                if (isset($removalInfo['right'])) {
                    $subEndPos = $this->origTokens->skipRight($subEndPos + 1, $removalInfo['right']) - 1;
                }
            }

            $result .= $this->origTokens->getTokenCode($pos, $subStartPos, $indentAdjustment);

            if (null !== $subNode) {
                $result .= $extraLeft;

                $origIndentLevel = $this->indentLevel;
                $this->setIndentLevel($this->origTokens->getIndentationBefore($subStartPos) + $indentAdjustment);

                // If it's the same node that was previously in this position, it certainly doesn't
                // need fixup. It's important to check this here, because our fixup checks are more
                // conservative than strictly necessary.
                if (isset($fixupInfo[$subNodeName])
                    && $subNode->getAttribute('origNode') !== $origSubNode
                ) {
                    $fixup = $fixupInfo[$subNodeName];
                    $res = $this->pFixup($fixup, $subNode, $class, $subStartPos, $subEndPos);
                } else {
                    $res = $this->p($subNode, true);
                }

                $this->safeAppend($result, $res);
                $this->setIndentLevel($origIndentLevel);

                $result .= $extraRight;
            }

            $pos = $subEndPos + 1;
        }

        $result .= $this->origTokens->getTokenCode($pos, $endPos + 1, $indentAdjustment);
        return $result;
    }

    /**
     * Perform a format-preserving pretty print of an array.
     *
     * @param array       $nodes            New nodes
     * @param array       $origNodes        Original nodes
     * @param int         $pos              Current token position (updated by reference)
     * @param int         $indentAdjustment Adjustment for indentation
     * @param string      $parentNodeType   Type of the containing node.
     * @param string      $subNodeName      Name of array subnode.
     * @param null|int    $fixup            Fixup information for array item nodes
     *
     * @return null|string Result of pretty print or null if cannot preserve formatting
     */
    protected function pArray(
        array $nodes, array $origNodes, int &$pos, int $indentAdjustment,
        string $parentNodeType, string $subNodeName, $fixup
    ) {
        $diff = $this->nodeListDiffer->diffWithReplacements($origNodes, $nodes);

        $mapKey = $parentNodeType . '->' . $subNodeName;
        $insertStr = $this->listInsertionMap[$mapKey] ?? null;
        $isStmtList = $subNodeName === 'stmts';

        $beforeFirstKeepOrReplace = true;
        $skipRemovedNode = false;
        $delayedAdd = [];
        $lastElemIndentLevel = $this->indentLevel;

        $insertNewline = false;
        if ($insertStr === "\n") {
            $insertStr = '';
            $insertNewline = true;
        }

        if ($isStmtList && \count($origNodes) === 1 && \count($nodes) !== 1) {
            $startPos = $origNodes[0]->getStartTokenPos();
            $endPos = $origNodes[0]->getEndTokenPos();
            \assert($startPos >= 0 && $endPos >= 0);
            if (!$this->origTokens->haveBraces($startPos, $endPos)) {
                // This was a single statement without braces, but either additional statements
                // have been added, or the single statement has been removed. This requires the
                // addition of braces. For now fall back.
                // TODO: Try to preserve formatting
                return null;
            }
        }

        $result = '';
        foreach ($diff as $i => $diffElem) {
            $diffType = $diffElem->type;
            /** @var Node|null $arrItem */
            $arrItem = $diffElem->new;
            /** @var Node|null $origArrItem */
            $origArrItem = $diffElem->old;

            if ($diffType === DiffElem::TYPE_KEEP || $diffType === DiffElem::TYPE_REPLACE) {
                $beforeFirstKeepOrReplace = false;

                if ($origArrItem === null || $arrItem === null) {
                    // We can only handle the case where both are null
                    if ($origArrItem === $arrItem) {
                        continue;
                    }
                    return null;
                }

                if (!$arrItem instanceof Node || !$origArrItem instanceof Node) {
                    // We can only deal with nodes. This can occur for Names, which use string arrays.
                    return null;
                }

                $itemStartPos = $origArrItem->getStartTokenPos();
                $itemEndPos = $origArrItem->getEndTokenPos();
                \assert($itemStartPos >= 0 && $itemEndPos >= 0 && $itemStartPos >= $pos);

                $origIndentLevel = $this->indentLevel;
                $lastElemIndentLevel = $this->origTokens->getIndentationBefore($itemStartPos) + $indentAdjustment;
                $this->setIndentLevel($lastElemIndentLevel);

                $comments = $arrItem->getComments();
                $origComments = $origArrItem->getComments();
                $commentStartPos = $origComments ? $origComments[0]->getStartTokenPos() : $itemStartPos;
                \assert($commentStartPos >= 0);

                if ($commentStartPos < $pos) {
                    // Comments may be assigned to multiple nodes if they start at the same position.
                    // Make sure we don't try to print them multiple times.
                    $commentStartPos = $itemStartPos;
                }

                if ($skipRemovedNode) {
                    if ($isStmtList && $this->origTokens->haveBracesInRange($pos, $itemStartPos)) {
                        // We'd remove the brace of a code block.
                        // TODO: Preserve formatting.
                        $this->setIndentLevel($origIndentLevel);
                        return null;
                    }
                } else {
                    $result .= $this->origTokens->getTokenCode(
                        $pos, $commentStartPos, $indentAdjustment);
                }

                if (!empty($delayedAdd)) {
                    /** @var Node $delayedAddNode */
                    foreach ($delayedAdd as $delayedAddNode) {
                        if ($insertNewline) {
                            $delayedAddComments = $delayedAddNode->getComments();
                            if ($delayedAddComments) {
                                $result .= $this->pComments($delayedAddComments) . $this->nl;
                            }
                        }

                        $this->safeAppend($result, $this->p($delayedAddNode, true));

                        if ($insertNewline) {
                            $result .= $insertStr . $this->nl;
                        } else {
                            $result .= $insertStr;
                        }
                    }

                    $delayedAdd = [];
                }

                if ($comments !== $origComments) {
                    if ($comments) {
                        $result .= $this->pComments($comments) . $this->nl;
                    }
                } else {
                    $result .= $this->origTokens->getTokenCode(
                        $commentStartPos, $itemStartPos, $indentAdjustment);
                }

                // If we had to remove anything, we have done so now.
                $skipRemovedNode = false;
            } elseif ($diffType === DiffElem::TYPE_ADD) {
                if (null === $insertStr) {
                    // We don't have insertion information for this list type
                    return null;
                }

                if ($insertStr === ', ' && $this->isMultiline($origNodes)) {
                    $insertStr = ',';
                    $insertNewline = true;
                }

                if ($beforeFirstKeepOrReplace) {
                    // Will be inserted at the next "replace" or "keep" element
                    $delayedAdd[] = $arrItem;
                    continue;
                }

                $itemStartPos = $pos;
                $itemEndPos = $pos - 1;

                $origIndentLevel = $this->indentLevel;
                $this->setIndentLevel($lastElemIndentLevel);

                if ($insertNewline) {
                    $comments = $arrItem->getComments();
                    if ($comments) {
                        $result .= $this->nl . $this->pComments($comments);
                    }
                    $result .= $insertStr . $this->nl;
                } else {
                    $result .= $insertStr;
                }
            } elseif ($diffType === DiffElem::TYPE_REMOVE) {
                if (!$origArrItem instanceof Node) {
                    // We only support removal for nodes
                    return null;
                }

                $itemStartPos = $origArrItem->getStartTokenPos();
                $itemEndPos = $origArrItem->getEndTokenPos();
                \assert($itemStartPos >= 0 && $itemEndPos >= 0);

                // Consider comments part of the node.
                $origComments = $origArrItem->getComments();
                if ($origComments) {
                    $itemStartPos = $origComments[0]->getStartTokenPos();
                }

                if ($i === 0) {
                    // If we're removing from the start, keep the tokens before the node and drop those after it,
                    // instead of the other way around.
                    $result .= $this->origTokens->getTokenCode(
                        $pos, $itemStartPos, $indentAdjustment);
                    $skipRemovedNode = true;
                } else {
                    if ($isStmtList && $this->origTokens->haveBracesInRange($pos, $itemStartPos)) {
                        // We'd remove the brace of a code block.
                        // TODO: Preserve formatting.
                        return null;
                    }
                }

                $pos = $itemEndPos + 1;
                continue;
            } else {
                throw new \Exception("Shouldn't happen");
            }

            if (null !== $fixup && $arrItem->getAttribute('origNode') !== $origArrItem) {
                $res = $this->pFixup($fixup, $arrItem, null, $itemStartPos, $itemEndPos);
            } else {
                $res = $this->p($arrItem, true);
            }
            $this->safeAppend($result, $res);

            $this->setIndentLevel($origIndentLevel);
            $pos = $itemEndPos + 1;
        }

        if ($skipRemovedNode) {
            // TODO: Support removing single node.
            return null;
        }

        if (!empty($delayedAdd)) {
            if (!isset($this->emptyListInsertionMap[$mapKey])) {
                return null;
            }

            list($findToken, $extraLeft, $extraRight) = $this->emptyListInsertionMap[$mapKey];
            if (null !== $findToken) {
                $insertPos = $this->origTokens->findRight($pos, $findToken) + 1;
                $result .= $this->origTokens->getTokenCode($pos, $insertPos, $indentAdjustment);
                $pos = $insertPos;
            }

            $first = true;
            $result .= $extraLeft;
            foreach ($delayedAdd as $delayedAddNode) {
                if (!$first) {
                    $result .= $insertStr;
                }
                $result .= $this->p($delayedAddNode, true);
                $first = false;
            }
            $result .= $extraRight;
        }

        return $result;
    }

    /**
     * Print node with fixups.
     *
     * Fixups here refer to the addition of extra parentheses, braces or other characters, that
     * are required to preserve program semantics in a certain context (e.g. to maintain precedence
     * or because only certain expressions are allowed in certain places).
     *
     * @param int         $fixup       Fixup type
     * @param Node        $subNode     Subnode to print
     * @param string|null $parentClass Class of parent node
     * @param int         $subStartPos Original start pos of subnode
     * @param int         $subEndPos   Original end pos of subnode
     *
     * @return string Result of fixed-up print of subnode
     */
    protected function pFixup(int $fixup, Node $subNode, $parentClass, int $subStartPos, int $subEndPos) : string {
        switch ($fixup) {
            case self::FIXUP_PREC_LEFT:
            case self::FIXUP_PREC_RIGHT:
                if (!$this->origTokens->haveParens($subStartPos, $subEndPos)) {
                    list($precedence, $associativity) = $this->precedenceMap[$parentClass];
                    return $this->pPrec($subNode, $precedence, $associativity,
                        $fixup === self::FIXUP_PREC_LEFT ? -1 : 1);
                }
                break;
            case self::FIXUP_CALL_LHS:
                if ($this->callLhsRequiresParens($subNode)
                    && !$this->origTokens->haveParens($subStartPos, $subEndPos)
                ) {
                    return '(' . $this->p($subNode) . ')';
                }
                break;
            case self::FIXUP_DEREF_LHS:
                if ($this->dereferenceLhsRequiresParens($subNode)
                    && !$this->origTokens->haveParens($subStartPos, $subEndPos)
                ) {
                    return '(' . $this->p($subNode) . ')';
                }
                break;
            case self::FIXUP_BRACED_NAME:
            case self::FIXUP_VAR_BRACED_NAME:
                if ($subNode instanceof Expr
                    && !$this->origTokens->haveBraces($subStartPos, $subEndPos)
                ) {
                    return ($fixup === self::FIXUP_VAR_BRACED_NAME ? '$' : '')
                        . '{' . $this->p($subNode) . '}';
                }
                break;
            case self::FIXUP_ENCAPSED:
                if (!$subNode instanceof Scalar\EncapsedStringPart
                    && !$this->origTokens->haveBraces($subStartPos, $subEndPos)
                ) {
                    return '{' . $this->p($subNode) . '}';
                }
                break;
            default:
                throw new \Exception('Cannot happen');
        }

        // Nothing special to do
        return $this->p($subNode);
    }

    /**
     * Appends to a string, ensuring whitespace between label characters.
     *
     * Example: "echo" and "$x" result in "echo$x", but "echo" and "x" result in "echo x".
     * Without safeAppend the result would be "echox", which does not preserve semantics.
     *
     * @param string $str
     * @param string $append
     */
    protected function safeAppend(string &$str, string $append) {
        if ($str === "") {
            $str = $append;
            return;
        }

        if ($append === "") {
            return;
        }

        if (!$this->labelCharMap[$append[0]]
                || !$this->labelCharMap[$str[\strlen($str) - 1]]) {
            $str .= $append;
        } else {
            $str .= " " . $append;
        }
    }

    /**
     * Determines whether the LHS of a call must be wrapped in parenthesis.
     *
     * @param Node $node LHS of a call
     *
     * @return bool Whether parentheses are required
     */
    protected function callLhsRequiresParens(Node $node) : bool {
        return !($node instanceof Node\Name
            || $node instanceof Expr\Variable
            || $node instanceof Expr\ArrayDimFetch
            || $node instanceof Expr\FuncCall
            || $node instanceof Expr\MethodCall
            || $node instanceof Expr\NullsafeMethodCall
            || $node instanceof Expr\StaticCall
            || $node instanceof Expr\Array_);
    }

    /**
     * Determines whether the LHS of a dereferencing operation must be wrapped in parenthesis.
     *
     * @param Node $node LHS of dereferencing operation
     *
     * @return bool Whether parentheses are required
     */
    protected function dereferenceLhsRequiresParens(Node $node) : bool {
        return !($node instanceof Expr\Variable
            || $node instanceof Node\Name
            || $node instanceof Expr\ArrayDimFetch
            || $node instanceof Expr\PropertyFetch
            || $node instanceof Expr\NullsafePropertyFetch
            || $node instanceof Expr\StaticPropertyFetch
            || $node instanceof Expr\FuncCall
            || $node instanceof Expr\MethodCall
            || $node instanceof Expr\NullsafeMethodCall
            || $node instanceof Expr\StaticCall
            || $node instanceof Expr\Array_
            || $node instanceof Scalar\String_
            || $node instanceof Expr\ConstFetch
            || $node instanceof Expr\ClassConstFetch);
    }

    /**
     * Print modifiers, including trailing whitespace.
     *
     * @param int $modifiers Modifier mask to print
     *
     * @return string Printed modifiers
     */
    protected function pModifiers(int $modifiers) {
        return ($modifiers & Stmt\Class_::MODIFIER_PUBLIC    ? 'public '    : '')
             . ($modifiers & Stmt\Class_::MODIFIER_PROTECTED ? 'protected ' : '')
             . ($modifiers & Stmt\Class_::MODIFIER_PRIVATE   ? 'private '   : '')
             . ($modifiers & Stmt\Class_::MODIFIER_STATIC    ? 'static '    : '')
             . ($modifiers & Stmt\Class_::MODIFIER_ABSTRACT  ? 'abstract '  : '')
             . ($modifiers & Stmt\Class_::MODIFIER_FINAL     ? 'final '     : '')
             . ($modifiers & Stmt\Class_::MODIFIER_READONLY  ? 'readonly '  : '');
    }

    /**
     * Determine whether a list of nodes uses multiline formatting.
     *
     * @param (Node|null)[] $nodes Node list
     *
     * @return bool Whether multiline formatting is used
     */
    protected function isMultiline(array $nodes) : bool {
        if (\count($nodes) < 2) {
            return false;
        }

        $pos = -1;
        foreach ($nodes as $node) {
            if (null === $node) {
                continue;
            }

            $endPos = $node->getEndTokenPos() + 1;
            if ($pos >= 0) {
                $text = $this->origTokens->getTokenCode($pos, $endPos, 0);
                if (false === strpos($text, "\n")) {
                    // We require that a newline is present between *every* item. If the formatting
                    // is inconsistent, with only some items having newlines, we don't consider it
                    // as multiline
                    return false;
                }
            }
            $pos = $endPos;
        }

        return true;
    }

    /**
     * Lazily initializes label char map.
     *
     * The label char map determines whether a certain character may occur in a label.
     */
    protected function initializeLabelCharMap() {
        if ($this->labelCharMap) return;

        $this->labelCharMap = [];
        for ($i = 0; $i < 256; $i++) {
            // Since PHP 7.1 The lower range is 0x80. However, we also want to support code for
            // older versions.
            $chr = chr($i);
            $this->labelCharMap[$chr] = $i >= 0x7f || ctype_alnum($chr);
        }
    }

    /**
     * Lazily initializes node list differ.
     *
     * The node list differ is used to determine differences between two array subnodes.
     */
    protected function initializeNodeListDiffer() {
        if ($this->nodeListDiffer) return;

        $this->nodeListDiffer = new Internal\Differ(function ($a, $b) {
            if ($a instanceof Node && $b instanceof Node) {
                return $a === $b->getAttribute('origNode');
            }
            // Can happen for array destructuring
            return $a === null && $b === null;
        });
    }

    /**
     * Lazily initializes fixup map.
     *
     * The fixup map is used to determine whether a certain subnode of a certain node may require
     * some kind of "fixup" operation, e.g. the addition of parenthesis or braces.
     */
    protected function initializeFixupMap() {
        if ($this->fixupMap) return;

        $this->fixupMap = [
            Expr\PreInc::class => ['var' => self::FIXUP_PREC_RIGHT],
            Expr\PreDec::class => ['var' => self::FIXUP_PREC_RIGHT],
            Expr\PostInc::class => ['var' => self::FIXUP_PREC_LEFT],
            Expr\PostDec::class => ['var' => self::FIXUP_PREC_LEFT],
            Expr\Instanceof_::class => [
                'expr' => self::FIXUP_PREC_LEFT,
                'class' => self::FIXUP_PREC_RIGHT, // TODO: FIXUP_NEW_VARIABLE
            ],
            Expr\Ternary::class => [
                'cond' => self::FIXUP_PREC_LEFT,
                'else' => self::FIXUP_PREC_RIGHT,
            ],

            Expr\FuncCall::class => ['name' => self::FIXUP_CALL_LHS],
            Expr\StaticCall::class => ['class' => self::FIXUP_DEREF_LHS],
            Expr\ArrayDimFetch::class => ['var' => self::FIXUP_DEREF_LHS],
            Expr\ClassConstFetch::class => ['var' => self::FIXUP_DEREF_LHS],
            Expr\New_::class => ['class' => self::FIXUP_DEREF_LHS], // TODO: FIXUP_NEW_VARIABLE
            Expr\MethodCall::class => [
                'var' => self::FIXUP_DEREF_LHS,
                'name' => self::FIXUP_BRACED_NAME,
            ],
            Expr\NullsafeMethodCall::class => [
                'var' => self::FIXUP_DEREF_LHS,
                'name' => self::FIXUP_BRACED_NAME,
            ],
            Expr\StaticPropertyFetch::class => [
                'class' => self::FIXUP_DEREF_LHS,
                'name' => self::FIXUP_VAR_BRACED_NAME,
            ],
            Expr\PropertyFetch::class => [
                'var' => self::FIXUP_DEREF_LHS,
                'name' => self::FIXUP_BRACED_NAME,
            ],
            Expr\NullsafePropertyFetch::class => [
                'var' => self::FIXUP_DEREF_LHS,
                'name' => self::FIXUP_BRACED_NAME,
            ],
            Scalar\Encapsed::class => [
                'parts' => self::FIXUP_ENCAPSED,
            ],
        ];

        $binaryOps = [
            BinaryOp\Pow::class, BinaryOp\Mul::class, BinaryOp\Div::class, BinaryOp\Mod::class,
            BinaryOp\Plus::class, BinaryOp\Minus::class, BinaryOp\Concat::class,
            BinaryOp\ShiftLeft::class, BinaryOp\ShiftRight::class, BinaryOp\Smaller::class,
            BinaryOp\SmallerOrEqual::class, BinaryOp\Greater::class, BinaryOp\GreaterOrEqual::class,
            BinaryOp\Equal::class, BinaryOp\NotEqual::class, BinaryOp\Identical::class,
            BinaryOp\NotIdentical::class, BinaryOp\Spaceship::class, BinaryOp\BitwiseAnd::class,
            BinaryOp\BitwiseXor::class, BinaryOp\BitwiseOr::class, BinaryOp\BooleanAnd::class,
            BinaryOp\BooleanOr::class, BinaryOp\Coalesce::class, BinaryOp\LogicalAnd::class,
            BinaryOp\LogicalXor::class, BinaryOp\LogicalOr::class,
        ];
        foreach ($binaryOps as $binaryOp) {
            $this->fixupMap[$binaryOp] = [
                'left' => self::FIXUP_PREC_LEFT,
                'right' => self::FIXUP_PREC_RIGHT
            ];
        }

        $assignOps = [
            Expr\Assign::class, Expr\AssignRef::class, AssignOp\Plus::class, AssignOp\Minus::class,
            AssignOp\Mul::class, AssignOp\Div::class, AssignOp\Concat::class, AssignOp\Mod::class,
            AssignOp\BitwiseAnd::class, AssignOp\BitwiseOr::class, AssignOp\BitwiseXor::class,
            AssignOp\ShiftLeft::class, AssignOp\ShiftRight::class, AssignOp\Pow::class, AssignOp\Coalesce::class
        ];
        foreach ($assignOps as $assignOp) {
            $this->fixupMap[$assignOp] = [
                'var' => self::FIXUP_PREC_LEFT,
                'expr' => self::FIXUP_PREC_RIGHT,
            ];
        }

        $prefixOps = [
            Expr\BitwiseNot::class, Expr\BooleanNot::class, Expr\UnaryPlus::class, Expr\UnaryMinus::class,
            Cast\Int_::class, Cast\Double::class, Cast\String_::class, Cast\Array_::class,
            Cast\Object_::class, Cast\Bool_::class, Cast\Unset_::class, Expr\ErrorSuppress::class,
            Expr\YieldFrom::class, Expr\Print_::class, Expr\Include_::class,
        ];
        foreach ($prefixOps as $prefixOp) {
            $this->fixupMap[$prefixOp] = ['expr' => self::FIXUP_PREC_RIGHT];
        }
    }

    /**
     * Lazily initializes the removal map.
     *
     * The removal map is used to determine which additional tokens should be removed when a
     * certain node is replaced by null.
     */
    protected function initializeRemovalMap() {
        if ($this->removalMap) return;

        $stripBoth = ['left' => \T_WHITESPACE, 'right' => \T_WHITESPACE];
        $stripLeft = ['left' => \T_WHITESPACE];
        $stripRight = ['right' => \T_WHITESPACE];
        $stripDoubleArrow = ['right' => \T_DOUBLE_ARROW];
        $stripColon = ['left' => ':'];
        $stripEquals = ['left' => '='];
        $this->removalMap = [
            'Expr_ArrayDimFetch->dim' => $stripBoth,
            'Expr_ArrayItem->key' => $stripDoubleArrow,
            'Expr_ArrowFunction->returnType' => $stripColon,
            'Expr_Closure->returnType' => $stripColon,
            'Expr_Exit->expr' => $stripBoth,
            'Expr_Ternary->if' => $stripBoth,
            'Expr_Yield->key' => $stripDoubleArrow,
            'Expr_Yield->value' => $stripBoth,
            'Param->type' => $stripRight,
            'Param->default' => $stripEquals,
            'Stmt_Break->num' => $stripBoth,
            'Stmt_Catch->var' => $stripLeft,
            'Stmt_ClassMethod->returnType' => $stripColon,
            'Stmt_Class->extends' => ['left' => \T_EXTENDS],
            'Stmt_Enum->scalarType' => $stripColon,
            'Stmt_EnumCase->expr' => $stripEquals,
            'Expr_PrintableNewAnonClass->extends' => ['left' => \T_EXTENDS],
            'Stmt_Continue->num' => $stripBoth,
            'Stmt_Foreach->keyVar' => $stripDoubleArrow,
            'Stmt_Function->returnType' => $stripColon,
            'Stmt_If->else' => $stripLeft,
            'Stmt_Namespace->name' => $stripLeft,
            'Stmt_Property->type' => $stripRight,
            'Stmt_PropertyProperty->default' => $stripEquals,
            'Stmt_Return->expr' => $stripBoth,
            'Stmt_StaticVar->default' => $stripEquals,
            'Stmt_TraitUseAdaptation_Alias->newName' => $stripLeft,
            'Stmt_TryCatch->finally' => $stripLeft,
            // 'Stmt_Case->cond': Replace with "default"
            // 'Stmt_Class->name': Unclear what to do
            // 'Stmt_Declare->stmts': Not a plain node
            // 'Stmt_TraitUseAdaptation_Alias->newModifier': Not a plain node
        ];
    }

    protected function initializeInsertionMap() {
        if ($this->insertionMap) return;

        // TODO: "yield" where both key and value are inserted doesn't work
        // [$find, $beforeToken, $extraLeft, $extraRight]
        $this->insertionMap = [
            'Expr_ArrayDimFetch->dim' => ['[', false, null, null],
            'Expr_ArrayItem->key' => [null, false, null, ' => '],
            'Expr_ArrowFunction->returnType' => [')', false, ' : ', null],
            'Expr_Closure->returnType' => [')', false, ' : ', null],
            'Expr_Ternary->if' => ['?', false, ' ', ' '],
            'Expr_Yield->key' => [\T_YIELD, false, null, ' => '],
            'Expr_Yield->value' => [\T_YIELD, false, ' ', null],
            'Param->type' => [null, false, null, ' '],
            'Param->default' => [null, false, ' = ', null],
            'Stmt_Break->num' => [\T_BREAK, false, ' ', null],
            'Stmt_Catch->var' => [null, false, ' ', null],
            'Stmt_ClassMethod->returnType' => [')', false, ' : ', null],
            'Stmt_Class->extends' => [null, false, ' extends ', null],
            'Stmt_Enum->scalarType' => [null, false, ' : ', null],
            'Stmt_EnumCase->expr' => [null, false, ' = ', null],
            'Expr_PrintableNewAnonClass->extends' => [null, ' extends ', null],
            'Stmt_Continue->num' => [\T_CONTINUE, false, ' ', null],
            'Stmt_Foreach->keyVar' => [\T_AS, false, null, ' => '],
            'Stmt_Function->returnType' => [')', false, ' : ', null],
            'Stmt_If->else' => [null, false, ' ', null],
            'Stmt_Namespace->name' => [\T_NAMESPACE, false, ' ', null],
            'Stmt_Property->type' => [\T_VARIABLE, true, null, ' '],
            'Stmt_PropertyProperty->default' => [null, false, ' = ', null],
            'Stmt_Return->expr' => [\T_RETURN, false, ' ', null],
            'Stmt_StaticVar->default' => [null, false, ' = ', null],
            //'Stmt_TraitUseAdaptation_Alias->newName' => [T_AS, false, ' ', null], // TODO
            'Stmt_TryCatch->finally' => [null, false, ' ', null],

            // 'Expr_Exit->expr': Complicated due to optional ()
            // 'Stmt_Case->cond': Conversion from default to case
            // 'Stmt_Class->name': Unclear
            // 'Stmt_Declare->stmts': Not a proper node
            // 'Stmt_TraitUseAdaptation_Alias->newModifier': Not a proper node
        ];
    }

    protected function initializeListInsertionMap() {
        if ($this->listInsertionMap) return;

        $this->listInsertionMap = [
            // special
            //'Expr_ShellExec->parts' => '', // TODO These need to be treated more carefully
            //'Scalar_Encapsed->parts' => '',
            'Stmt_Catch->types' => '|',
            'UnionType->types' => '|',
            'Stmt_If->elseifs' => ' ',
            'Stmt_TryCatch->catches' => ' ',

            // comma-separated lists
            'Expr_Array->items' => ', ',
            'Expr_ArrowFunction->params' => ', ',
            'Expr_Closure->params' => ', ',
            'Expr_Closure->uses' => ', ',
            'Expr_FuncCall->args' => ', ',
            'Expr_Isset->vars' => ', ',
            'Expr_List->items' => ', ',
            'Expr_MethodCall->args' => ', ',
            'Expr_NullsafeMethodCall->args' => ', ',
            'Expr_New->args' => ', ',
            'Expr_PrintableNewAnonClass->args' => ', ',
            'Expr_StaticCall->args' => ', ',
            'Stmt_ClassConst->consts' => ', ',
            'Stmt_ClassMethod->params' => ', ',
            'Stmt_Class->implements' => ', ',
            'Stmt_Enum->implements' => ', ',
            'Expr_PrintableNewAnonClass->implements' => ', ',
            'Stmt_Const->consts' => ', ',
            'Stmt_Declare->declares' => ', ',
            'Stmt_Echo->exprs' => ', ',
            'Stmt_For->init' => ', ',
            'Stmt_For->cond' => ', ',
            'Stmt_For->loop' => ', ',
            'Stmt_Function->params' => ', ',
            'Stmt_Global->vars' => ', ',
            'Stmt_GroupUse->uses' => ', ',
            'Stmt_Interface->extends' => ', ',
            'Stmt_Match->arms' => ', ',
            'Stmt_Property->props' => ', ',
            'Stmt_StaticVar->vars' => ', ',
            'Stmt_TraitUse->traits' => ', ',
            'Stmt_TraitUseAdaptation_Precedence->insteadof' => ', ',
            'Stmt_Unset->vars' => ', ',
            'Stmt_Use->uses' => ', ',
            'MatchArm->conds' => ', ',
            'AttributeGroup->attrs' => ', ',

            // statement lists
            'Expr_Closure->stmts' => "\n",
            'Stmt_Case->stmts' => "\n",
            'Stmt_Catch->stmts' => "\n",
            'Stmt_Class->stmts' => "\n",
            'Stmt_Enum->stmts' => "\n",
            'Expr_PrintableNewAnonClass->stmts' => "\n",
            'Stmt_Interface->stmts' => "\n",
            'Stmt_Trait->stmts' => "\n",
            'Stmt_ClassMethod->stmts' => "\n",
            'Stmt_Declare->stmts' => "\n",
            'Stmt_Do->stmts' => "\n",
            'Stmt_ElseIf->stmts' => "\n",
            'Stmt_Else->stmts' => "\n",
            'Stmt_Finally->stmts' => "\n",
            'Stmt_Foreach->stmts' => "\n",
            'Stmt_For->stmts' => "\n",
            'Stmt_Function->stmts' => "\n",
            'Stmt_If->stmts' => "\n",
            'Stmt_Namespace->stmts' => "\n",
            'Stmt_Class->attrGroups' => "\n",
            'Stmt_Enum->attrGroups' => "\n",
            'Stmt_EnumCase->attrGroups' => "\n",
            'Stmt_Interface->attrGroups' => "\n",
            'Stmt_Trait->attrGroups' => "\n",
            'Stmt_Function->attrGroups' => "\n",
            'Stmt_ClassMethod->attrGroups' => "\n",
            'Stmt_ClassConst->attrGroups' => "\n",
            'Stmt_Property->attrGroups' => "\n",
            'Expr_PrintableNewAnonClass->attrGroups' => ' ',
            'Expr_Closure->attrGroups' => ' ',
            'Expr_ArrowFunction->attrGroups' => ' ',
            'Param->attrGroups' => ' ',
            'Stmt_Switch->cases' => "\n",
            'Stmt_TraitUse->adaptations' => "\n",
            'Stmt_TryCatch->stmts' => "\n",
            'Stmt_While->stmts' => "\n",

            // dummy for top-level context
            'File->stmts' => "\n",
        ];
    }

    protected function initializeEmptyListInsertionMap() {
        if ($this->emptyListInsertionMap) return;

        // TODO Insertion into empty statement lists.

        // [$find, $extraLeft, $extraRight]
        $this->emptyListInsertionMap = [
            'Expr_ArrowFunction->params' => ['(', '', ''],
            'Expr_Closure->uses' => [')', ' use(', ')'],
            'Expr_Closure->params' => ['(', '', ''],
            'Expr_FuncCall->args' => ['(', '', ''],
            'Expr_MethodCall->args' => ['(', '', ''],
            'Expr_NullsafeMethodCall->args' => ['(', '', ''],
            'Expr_New->args' => ['(', '', ''],
            'Expr_PrintableNewAnonClass->args' => ['(', '', ''],
            'Expr_PrintableNewAnonClass->implements' => [null, ' implements ', ''],
            'Expr_StaticCall->args' => ['(', '', ''],
            'Stmt_Class->implements' => [null, ' implements ', ''],
            'Stmt_Enum->implements' => [null, ' implements ', ''],
            'Stmt_ClassMethod->params' => ['(', '', ''],
            'Stmt_Interface->extends' => [null, ' extends ', ''],
            'Stmt_Function->params' => ['(', '', ''],

            /* These cannot be empty to start with:
             * Expr_Isset->vars
             * Stmt_Catch->types
             * Stmt_Const->consts
             * Stmt_ClassConst->consts
             * Stmt_Declare->declares
             * Stmt_Echo->exprs
             * Stmt_Global->vars
             * Stmt_GroupUse->uses
             * Stmt_Property->props
             * Stmt_StaticVar->vars
             * Stmt_TraitUse->traits
             * Stmt_TraitUseAdaptation_Precedence->insteadof
             * Stmt_Unset->vars
             * Stmt_Use->uses
             * UnionType->types
             */

            /* TODO
             * Stmt_If->elseifs
             * Stmt_TryCatch->catches
             * Expr_Array->items
             * Expr_List->items
             * Stmt_For->init
             * Stmt_For->cond
             * Stmt_For->loop
             */
        ];
    }

    protected function initializeModifierChangeMap() {
        if ($this->modifierChangeMap) return;

        $this->modifierChangeMap = [
            'Stmt_ClassConst->flags' => \T_CONST,
            'Stmt_ClassMethod->flags' => \T_FUNCTION,
            'Stmt_Class->flags' => \T_CLASS,
            'Stmt_Property->flags' => \T_VARIABLE,
            'Param->flags' => \T_VARIABLE,
            //'Stmt_TraitUseAdaptation_Alias->newModifier' => 0, // TODO
        ];

        // List of integer subnodes that are not modifiers:
        // Expr_Include->type
        // Stmt_GroupUse->type
        // Stmt_Use->type
        // Stmt_UseUse->type
    }
}
