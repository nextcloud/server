<?php
namespace Psalm\Internal\Type;

use function array_pop;
use function count;
use function in_array;
use function preg_match;
use Psalm\Exception\TypeParseTreeException;
use function strlen;
use function strtolower;

/**
 * @internal
 */
class ParseTreeCreator
{
    /** @var ParseTree */
    private $parse_tree;

    /** @var ParseTree */
    private $current_leaf;

    /** @var array<int, array{0: string, 1: int}> */
    private $type_tokens;

    /** @var int */
    private $type_token_count;

    /** @var int */
    private $t = 0;

    /**
     * @param list<array{0: string, 1: int}> $type_tokens
     */
    public function __construct(array $type_tokens)
    {
        $this->type_tokens = $type_tokens;
        $this->type_token_count = count($type_tokens);
        $this->parse_tree = new ParseTree\Root();
        $this->current_leaf = $this->parse_tree;
    }

    public function create() : ParseTree
    {
        while ($this->t < $this->type_token_count) {
            $type_token = $this->type_tokens[$this->t];

            switch ($type_token[0]) {
                case '<':
                case '{':
                case ']':
                    throw new TypeParseTreeException('Unexpected token ' . $type_token[0]);

                case '[':
                    $this->handleOpenSquareBracket();
                    break;

                case '(':
                    $this->handleOpenRoundBracket();
                    break;

                case ')':
                    $this->handleClosedRoundBracket();
                    break;

                case '>':
                    do {
                        if ($this->current_leaf->parent === null) {
                            throw new TypeParseTreeException('Cannot parse generic type');
                        }

                        $this->current_leaf = $this->current_leaf->parent;
                    } while (!$this->current_leaf instanceof ParseTree\GenericTree);

                    $this->current_leaf->terminated = true;

                    break;

                case '}':
                    do {
                        if ($this->current_leaf->parent === null) {
                            throw new TypeParseTreeException('Cannot parse array type');
                        }

                        $this->current_leaf = $this->current_leaf->parent;
                    } while (!$this->current_leaf instanceof ParseTree\KeyedArrayTree);

                    $this->current_leaf->terminated = true;

                    break;

                case ',':
                    $this->handleComma();
                    break;

                case '...':
                case '=':
                    $this->handleEllipsisOrEquals($type_token);
                    break;

                case ':':
                    $this->handleColon();
                    break;

                case ' ':
                    $this->handleSpace();
                    break;

                case '?':
                    $this->handleQuestionMark();
                    break;

                case '|':
                    $this->handleBar();
                    break;

                case '&':
                    $this->handleAmpersand();
                    break;

                case 'is':
                case 'as':
                    $this->handleIsOrAs($type_token);
                    break;

                default:
                    $this->handleValue($type_token);
                    break;
            }

            $this->t++;
        }

        $this->parse_tree->cleanParents();

        if ($this->current_leaf !== $this->parse_tree
            && ($this->parse_tree instanceof ParseTree\GenericTree
                || $this->parse_tree instanceof ParseTree\CallableTree
                || $this->parse_tree instanceof ParseTree\KeyedArrayTree)
        ) {
            throw new TypeParseTreeException(
                'Unterminated bracket'
            );
        }

        return $this->parse_tree;
    }

    /**
     * @param  array{0: string, 1: int} $current_token
     */
    private function createMethodParam(array $current_token, ParseTree $current_parent) : void
    {
        $byref = false;
        $variadic = false;
        $has_default = false;
        $default = '';

        if ($current_token[0] === '&') {
            throw new TypeParseTreeException('Magic args cannot be passed by reference');
        }

        if ($current_token[0] === '...') {
            $variadic = true;

            ++$this->t;
            $current_token = $this->t < $this->type_token_count ? $this->type_tokens[$this->t] : null;
        }

        if (!$current_token || $current_token[0][0] !== '$') {
            throw new TypeParseTreeException('Unexpected token after space');
        }

        $new_parent_leaf = new ParseTree\MethodParamTree(
            $current_token[0],
            $byref,
            $variadic,
            $current_parent
        );

        for ($j = $this->t + 1; $j < $this->type_token_count; ++$j) {
            $ahead_type_token = $this->type_tokens[$j];

            if ($ahead_type_token[0] === ','
                || ($ahead_type_token[0] === ')' && $this->type_tokens[$j - 1][0] !== '(')
            ) {
                $this->t = $j - 1;
                break;
            }

            if ($has_default) {
                $default .= $ahead_type_token[0];
            }

            if ($ahead_type_token[0] === '=') {
                $has_default = true;
                continue;
            }

            if ($j === $this->type_token_count - 1) {
                throw new TypeParseTreeException('Unterminated method');
            }
        }

        $new_parent_leaf->default = $default;

        if ($this->current_leaf !== $current_parent) {
            $new_parent_leaf->children = [$this->current_leaf];
            $this->current_leaf->parent = $new_parent_leaf;
            array_pop($current_parent->children);
        }

        $current_parent->children[] = $new_parent_leaf;

        $this->current_leaf = $new_parent_leaf;
    }

    private function handleOpenSquareBracket() : void
    {
        if ($this->current_leaf instanceof ParseTree\Root) {
            throw new TypeParseTreeException('Unexpected token [');
        }

        $indexed_access = false;

        $next_token = $this->t + 1 < $this->type_token_count ? $this->type_tokens[$this->t + 1] : null;

        if (!$next_token || $next_token[0] !== ']') {
            $next_next_token = $this->t + 2 < $this->type_token_count ? $this->type_tokens[$this->t + 2] : null;

            if ($next_next_token !== null && $next_next_token[0] === ']') {
                $indexed_access = true;
                ++$this->t;
            } else {
                throw new TypeParseTreeException('Unexpected token [');
            }
        }

        $current_parent = $this->current_leaf->parent;

        if ($indexed_access) {
            if ($next_token === null) {
                throw new TypeParseTreeException('Unexpected token [');
            }

            $new_parent_leaf = new ParseTree\IndexedAccessTree($next_token[0], $current_parent);
        } else {
            if ($this->current_leaf instanceof ParseTree\KeyedArrayPropertyTree) {
                throw new TypeParseTreeException('Unexpected token [');
            }

            $new_parent_leaf = new ParseTree\GenericTree('array', $current_parent);
        }

        $this->current_leaf->parent = $new_parent_leaf;
        $new_parent_leaf->children = [$this->current_leaf];

        if ($current_parent) {
            array_pop($current_parent->children);
            $current_parent->children[] = $new_parent_leaf;
        } else {
            $this->parse_tree = $new_parent_leaf;
        }

        $this->current_leaf = $new_parent_leaf;
        ++$this->t;
    }

    private function handleOpenRoundBracket() : void
    {
        if ($this->current_leaf instanceof ParseTree\Value) {
            throw new TypeParseTreeException('Unrecognised token (');
        }

        $new_parent = !$this->current_leaf instanceof ParseTree\Root ? $this->current_leaf : null;

        $new_leaf = new ParseTree\EncapsulationTree(
            $new_parent
        );

        if ($this->current_leaf instanceof ParseTree\Root) {
            $this->current_leaf = $this->parse_tree = $new_leaf;
            return;
        }

        if ($new_leaf->parent) {
            $new_leaf->parent->children[] = $new_leaf;
        }

        $this->current_leaf = $new_leaf;
    }

    private function handleClosedRoundBracket() : void
    {
        $prev_token = $this->t > 0 ? $this->type_tokens[$this->t - 1] : null;

        if ($prev_token !== null
            && $prev_token[0] === '('
            && $this->current_leaf instanceof ParseTree\CallableTree
        ) {
            return;
        }

        do {
            if ($this->current_leaf->parent === null) {
                break;
            }

            $this->current_leaf = $this->current_leaf->parent;
        } while (!$this->current_leaf instanceof ParseTree\EncapsulationTree
            && !$this->current_leaf instanceof ParseTree\CallableTree
            && !$this->current_leaf instanceof ParseTree\MethodTree);

        if ($this->current_leaf instanceof ParseTree\EncapsulationTree
            || $this->current_leaf instanceof ParseTree\CallableTree
        ) {
            $this->current_leaf->terminated = true;
        }
    }

    private function handleComma() : void
    {
        if ($this->current_leaf instanceof ParseTree\Root) {
            throw new TypeParseTreeException('Unexpected token ,');
        }

        if (!$this->current_leaf->parent) {
            throw new TypeParseTreeException('Cannot parse comma without a parent node');
        }

        $context_node = $this->current_leaf;

        if ($context_node instanceof ParseTree\GenericTree
            || $context_node instanceof ParseTree\KeyedArrayTree
            || $context_node instanceof ParseTree\CallableTree
            || $context_node instanceof ParseTree\MethodTree
        ) {
            $context_node = $context_node->parent;
        }

        while ($context_node
            && !$context_node instanceof ParseTree\GenericTree
            && !$context_node instanceof ParseTree\KeyedArrayTree
            && !$context_node instanceof ParseTree\CallableTree
            && !$context_node instanceof ParseTree\MethodTree
        ) {
            $context_node = $context_node->parent;
        }

        if (!$context_node) {
            throw new TypeParseTreeException('Cannot parse comma in non-generic/array type');
        }

        $this->current_leaf = $context_node;
    }

    /** @param array{0: string, 1: int} $type_token */
    private function handleEllipsisOrEquals(array $type_token) : void
    {
        $prev_token = $this->t > 0 ? $this->type_tokens[$this->t - 1] : null;

        if ($prev_token && ($prev_token[0] === '...' || $prev_token[0] === '=')) {
            throw new TypeParseTreeException('Cannot have duplicate tokens');
        }

        $current_parent = $this->current_leaf->parent;

        if ($this->current_leaf instanceof ParseTree\MethodTree && $type_token[0] === '...') {
            $this->createMethodParam($type_token, $this->current_leaf);
            return;
        }

        while ($current_parent
            && !$current_parent instanceof ParseTree\CallableTree
            && !$current_parent instanceof ParseTree\CallableParamTree
        ) {
            $this->current_leaf = $current_parent;
            $current_parent = $current_parent->parent;
        }

        if (!$current_parent) {
            if ($this->current_leaf instanceof ParseTree\CallableTree
                && $type_token[0] === '...'
            ) {
                $current_parent = $this->current_leaf;
            } else {
                throw new TypeParseTreeException('Unexpected token ' . $type_token[0]);
            }
        }

        if ($current_parent instanceof ParseTree\CallableParamTree) {
            throw new TypeParseTreeException('Cannot have variadic param with a default');
        }

        $new_leaf = new ParseTree\CallableParamTree($current_parent);
        $new_leaf->has_default = $type_token[0] === '=';
        $new_leaf->variadic = $type_token[0] === '...';

        if ($current_parent !== $this->current_leaf) {
            $new_leaf->children = [$this->current_leaf];
            $this->current_leaf->parent = $new_leaf;

            array_pop($current_parent->children);
            $current_parent->children[] = $new_leaf;
        } else {
            $current_parent->children[] = $new_leaf;
        }

        $this->current_leaf = $new_leaf;
    }

    private function handleColon() : void
    {
        if ($this->current_leaf instanceof ParseTree\Root) {
            throw new TypeParseTreeException('Unexpected token :');
        }

        $current_parent = $this->current_leaf->parent;

        if ($this->current_leaf instanceof ParseTree\CallableTree) {
            $new_parent_leaf = new ParseTree\CallableWithReturnTypeTree($current_parent);
            $this->current_leaf->parent = $new_parent_leaf;
            $new_parent_leaf->children = [$this->current_leaf];

            if ($current_parent) {
                array_pop($current_parent->children);
                $current_parent->children[] = $new_parent_leaf;
            } else {
                $this->parse_tree = $new_parent_leaf;
            }

            $this->current_leaf = $new_parent_leaf;
            return;
        }

        if ($this->current_leaf instanceof ParseTree\MethodTree) {
            $new_parent_leaf = new ParseTree\MethodWithReturnTypeTree($current_parent);
            $this->current_leaf->parent = $new_parent_leaf;
            $new_parent_leaf->children = [$this->current_leaf];

            if ($current_parent) {
                array_pop($current_parent->children);
                $current_parent->children[] = $new_parent_leaf;
            } else {
                $this->parse_tree = $new_parent_leaf;
            }

            $this->current_leaf = $new_parent_leaf;
            return;
        }

        if ($current_parent && $current_parent instanceof ParseTree\KeyedArrayPropertyTree) {
            return;
        }

        while (($current_parent instanceof ParseTree\UnionTree
                || $current_parent instanceof ParseTree\CallableWithReturnTypeTree)
            && $this->current_leaf->parent
        ) {
            $this->current_leaf = $this->current_leaf->parent;
            $current_parent = $this->current_leaf->parent;
        }

        if ($current_parent && $current_parent instanceof ParseTree\ConditionalTree) {
            if (count($current_parent->children) > 1) {
                throw new TypeParseTreeException('Cannot process colon in conditional twice');
            }

            $this->current_leaf = $current_parent;
            return;
        }

        if (!$current_parent) {
            throw new TypeParseTreeException('Cannot process colon without parent');
        }

        if (!$this->current_leaf instanceof ParseTree\Value) {
            throw new TypeParseTreeException('Unexpected LHS of property');
        }

        if (!$current_parent instanceof ParseTree\KeyedArrayTree) {
            throw new TypeParseTreeException('Saw : outside of object-like array');
        }

        $prev_token = $this->t > 0 ? $this->type_tokens[$this->t - 1] : null;

        $new_parent_leaf = new ParseTree\KeyedArrayPropertyTree($this->current_leaf->value, $current_parent);
        $new_parent_leaf->possibly_undefined = $prev_token !== null && $prev_token[0] === '?';
        $this->current_leaf->parent = $new_parent_leaf;

        array_pop($current_parent->children);
        $current_parent->children[] = $new_parent_leaf;

        $this->current_leaf = $new_parent_leaf;
    }

    private function handleSpace() : void
    {
        if ($this->current_leaf instanceof ParseTree\Root) {
            throw new TypeParseTreeException('Unexpected space');
        }

        if ($this->current_leaf instanceof ParseTree\KeyedArrayTree) {
            return;
        }

        $current_parent = $this->current_leaf->parent;

        if ($current_parent instanceof ParseTree\CallableTree) {
            return;
        }

        while ($current_parent && !$current_parent instanceof ParseTree\MethodTree) {
            $this->current_leaf = $current_parent;
            $current_parent = $current_parent->parent;
        }

        $next_token = $this->t + 1 < $this->type_token_count ? $this->type_tokens[$this->t + 1] : null;

        if (!$current_parent instanceof ParseTree\MethodTree || !$next_token) {
            throw new TypeParseTreeException('Unexpected space');
        }

        ++$this->t;

        $this->createMethodParam($next_token, $current_parent);
    }

    private function handleQuestionMark() : void
    {
        $next_token = $this->t + 1 < $this->type_token_count ? $this->type_tokens[$this->t + 1] : null;

        if ($next_token === null || $next_token[0] !== ':') {
            while (($this->current_leaf instanceof ParseTree\Value
                    || $this->current_leaf instanceof ParseTree\UnionTree
                    || ($this->current_leaf instanceof ParseTree\KeyedArrayTree
                        && $this->current_leaf->terminated)
                    || ($this->current_leaf instanceof ParseTree\GenericTree
                        && $this->current_leaf->terminated)
                    || ($this->current_leaf instanceof ParseTree\EncapsulationTree
                        && $this->current_leaf->terminated)
                    || ($this->current_leaf instanceof ParseTree\CallableTree
                        && $this->current_leaf->terminated)
                    || $this->current_leaf instanceof ParseTree\IntersectionTree)
                && $this->current_leaf->parent
            ) {
                $this->current_leaf = $this->current_leaf->parent;
            }

            if ($this->current_leaf instanceof ParseTree\TemplateIsTree && $this->current_leaf->parent) {
                $current_parent = $this->current_leaf->parent;

                $new_leaf = new ParseTree\ConditionalTree(
                    $this->current_leaf,
                    $this->current_leaf->parent
                );

                $this->current_leaf->parent = $new_leaf;

                array_pop($current_parent->children);
                $current_parent->children[] = $new_leaf;
                $this->current_leaf = $new_leaf;
            } else {
                $new_parent = !$this->current_leaf instanceof ParseTree\Root ? $this->current_leaf : null;

                if (!$next_token) {
                    throw new TypeParseTreeException('Unexpected token ?');
                }

                $new_leaf = new ParseTree\NullableTree(
                    $new_parent
                );

                if ($this->current_leaf instanceof ParseTree\Root) {
                    $this->current_leaf = $this->parse_tree = $new_leaf;
                    return;
                }

                if ($new_leaf->parent) {
                    $new_leaf->parent->children[] = $new_leaf;
                }

                $this->current_leaf = $new_leaf;
            }
        }
    }

    private function handleBar() : void
    {
        if ($this->current_leaf instanceof ParseTree\Root) {
            throw new TypeParseTreeException('Unexpected token |');
        }

        $current_parent = $this->current_leaf->parent;

        if ($current_parent instanceof ParseTree\CallableWithReturnTypeTree) {
            $this->current_leaf = $current_parent;
            $current_parent = $current_parent->parent;
        }

        if ($current_parent instanceof ParseTree\NullableTree) {
            $this->current_leaf = $current_parent;
            $current_parent = $current_parent->parent;
        }

        if ($this->current_leaf instanceof ParseTree\UnionTree) {
            throw new TypeParseTreeException('Unexpected token |');
        }

        if ($current_parent && $current_parent instanceof ParseTree\UnionTree) {
            $this->current_leaf = $current_parent;
            return;
        }

        if ($current_parent && $current_parent instanceof ParseTree\IntersectionTree) {
            $this->current_leaf = $current_parent;
            $current_parent = $this->current_leaf->parent;
        }

        if ($current_parent instanceof ParseTree\TemplateIsTree) {
            $new_parent_leaf = new ParseTree\UnionTree($this->current_leaf);
            $new_parent_leaf->children = [$this->current_leaf];
            $new_parent_leaf->parent = $current_parent;
            $this->current_leaf->parent = $new_parent_leaf;
        } else {
            $new_parent_leaf = new ParseTree\UnionTree($current_parent);
            $new_parent_leaf->children = [$this->current_leaf];
            $this->current_leaf->parent = $new_parent_leaf;
        }

        if ($current_parent) {
            array_pop($current_parent->children);
            $current_parent->children[] = $new_parent_leaf;
        } else {
            $this->parse_tree = $new_parent_leaf;
        }

        $this->current_leaf = $new_parent_leaf;
    }

    private function handleAmpersand() : void
    {
        if ($this->current_leaf instanceof ParseTree\Root) {
            throw new TypeParseTreeException(
                'Unexpected &'
            );
        }

        $current_parent = $this->current_leaf->parent;

        if ($this->current_leaf instanceof ParseTree\MethodTree && $current_parent) {
            $this->createMethodParam($this->type_tokens[$this->t], $current_parent);
            return;
        }

        if ($current_parent && $current_parent instanceof ParseTree\IntersectionTree) {
            $this->current_leaf = $current_parent;
            return;
        }

        $new_parent_leaf = new ParseTree\IntersectionTree($current_parent);
        $new_parent_leaf->children = [$this->current_leaf];
        $this->current_leaf->parent = $new_parent_leaf;

        if ($current_parent) {
            array_pop($current_parent->children);
            $current_parent->children[] = $new_parent_leaf;
        } else {
            $this->parse_tree = $new_parent_leaf;
        }

        $this->current_leaf = $new_parent_leaf;
    }

    /** @param array{0: string, 1: int} $type_token */
    private function handleIsOrAs(array $type_token) : void
    {
        if ($this->t === 0) {
            $this->handleValue($type_token);
        } else {
            $current_parent = $this->current_leaf->parent;

            if ($current_parent) {
                array_pop($current_parent->children);
            }

            if ($type_token[0] === 'as') {
                $next_token = $this->t + 1 < $this->type_token_count ? $this->type_tokens[$this->t + 1] : null;

                if (!$this->current_leaf instanceof ParseTree\Value
                    || !$current_parent instanceof ParseTree\GenericTree
                    || !$next_token
                ) {
                    throw new TypeParseTreeException('Unexpected token ' . $type_token[0]);
                }

                $this->current_leaf = new ParseTree\TemplateAsTree(
                    $this->current_leaf->value,
                    $next_token[0],
                    $current_parent
                );

                $current_parent->children[] = $this->current_leaf;
                ++$this->t;
            } elseif ($this->current_leaf instanceof ParseTree\Value) {
                $this->current_leaf = new ParseTree\TemplateIsTree(
                    $this->current_leaf->value,
                    $current_parent
                );

                if ($current_parent) {
                    $current_parent->children[] = $this->current_leaf;
                }
            }
        }
    }

    /** @param array{0: string, 1: int} $type_token */
    private function handleValue(array $type_token) : void
    {
        $new_parent = !$this->current_leaf instanceof ParseTree\Root ? $this->current_leaf : null;

        if ($this->current_leaf instanceof ParseTree\MethodTree && $type_token[0][0] === '$') {
            $this->createMethodParam($type_token, $this->current_leaf);
            return;
        }

        $next_token = $this->t + 1 < $this->type_token_count ? $this->type_tokens[$this->t + 1] : null;

        switch ($next_token[0] ?? null) {
            case '<':
                $new_leaf = new ParseTree\GenericTree(
                    $type_token[0],
                    $new_parent
                );
                ++$this->t;
                break;

            case '{':
                $new_leaf = new ParseTree\KeyedArrayTree(
                    $type_token[0],
                    $new_parent
                );
                ++$this->t;
                break;

            case '(':
                if (in_array(
                    $type_token[0],
                    ['callable', 'pure-callable', 'Closure', '\Closure', 'pure-Closure'],
                    true
                )) {
                    $new_leaf = new ParseTree\CallableTree(
                        $type_token[0],
                        $new_parent
                    );
                } elseif ($type_token[0] !== 'array'
                    && $type_token[0][0] !== '\\'
                    && $this->current_leaf instanceof ParseTree\Root
                ) {
                    $new_leaf = new ParseTree\MethodTree(
                        $type_token[0],
                        $new_parent
                    );
                } else {
                    throw new TypeParseTreeException(
                        'Bracket must be preceded by “Closure”, “callable”, "pure-callable" or a valid @method name'
                    );
                }

                ++$this->t;
                break;

            case '::':
                $nexter_token = $this->t + 2 < $this->type_token_count ? $this->type_tokens[$this->t + 2] : null;

                if ($this->current_leaf instanceof ParseTree\KeyedArrayTree) {
                    throw new TypeParseTreeException(
                        'Unexpected :: in array key'
                    );
                }

                if (!$nexter_token
                    || (!preg_match('/^([a-zA-Z_][a-zA-Z_0-9]*\*?|\*)$/', $nexter_token[0])
                        && strtolower($nexter_token[0]) !== 'class')
                ) {
                    throw new TypeParseTreeException(
                        'Invalid class constant ' . ($nexter_token[0] ?? '<empty>')
                    );
                }

                $new_leaf = new ParseTree\Value(
                    $type_token[0] . '::' . $nexter_token[0],
                    $type_token[1],
                    $type_token[1] + 2 + strlen($nexter_token[0]),
                    $new_parent
                );

                $this->t += 2;

                break;

            default:
                if ($type_token[0] === '$this') {
                    $type_token[0] = 'static';
                }

                $new_leaf = new ParseTree\Value(
                    $type_token[0],
                    $type_token[1],
                    $type_token[1] + strlen($type_token[0]),
                    $new_parent
                );
                break;
        }

        if ($this->current_leaf instanceof ParseTree\Root) {
            $this->current_leaf = $this->parse_tree = $new_leaf;
            return;
        }

        if ($new_leaf->parent) {
            $new_leaf->parent->children[] = $new_leaf;
        }

        $this->current_leaf = $new_leaf;
    }
}
