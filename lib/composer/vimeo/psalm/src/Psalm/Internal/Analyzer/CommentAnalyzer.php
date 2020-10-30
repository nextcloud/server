<?php
namespace Psalm\Internal\Analyzer;

use PhpParser;
use Psalm\Aliases;
use Psalm\DocComment;
use Psalm\Exception\DocblockParseException;
use Psalm\Exception\IncorrectDocblockException;
use Psalm\Exception\TypeParseTreeException;
use Psalm\FileSource;
use Psalm\Internal\Scanner\ClassLikeDocblockComment;
use Psalm\Internal\Scanner\FunctionDocblockComment;
use Psalm\Internal\Scanner\VarDocblockComment;
use Psalm\Internal\Scanner\ParsedDocblock;
use Psalm\Type;
use Psalm\Internal\Type\ParseTree;
use Psalm\Internal\Type\ParseTreeCreator;
use Psalm\Internal\Type\TypeAlias;
use Psalm\Internal\Type\TypeParser;
use Psalm\Internal\Type\TypeTokenizer;
use function array_unique;
use function trim;
use function substr_count;
use function strlen;
use function preg_replace;
use function str_replace;
use function preg_match;
use function count;
use function reset;
use function preg_split;
use const PREG_SPLIT_DELIM_CAPTURE;
use const PREG_SPLIT_NO_EMPTY;
use function array_shift;
use function implode;
use function substr;
use function strpos;
use function strtolower;
use function in_array;
use function explode;
use function array_merge;
use const PREG_OFFSET_CAPTURE;
use function rtrim;
use function array_key_first;

/**
 * @internal
 */
class CommentAnalyzer
{
    public const TYPE_REGEX = '(\??\\\?[\(\)A-Za-z0-9_&\<\.=,\>\[\]\-\{\}:|?\\\\]*|\$[a-zA-Z_0-9_]+)';

    /**
     * @param  array<string, array<string, array{Type\Union}>>|null   $template_type_map
     * @param  array<string, TypeAlias> $type_aliases
     *
     * @throws DocblockParseException if there was a problem parsing the docblock
     *
     * @return list<VarDocblockComment>
     */
    public static function getTypeFromComment(
        PhpParser\Comment\Doc $comment,
        FileSource $source,
        Aliases $aliases,
        ?array $template_type_map = null,
        ?array $type_aliases = null
    ): array {
        $parsed_docblock = DocComment::parsePreservingLength($comment);

        return self::arrayToDocblocks(
            $comment,
            $parsed_docblock,
            $source,
            $aliases,
            $template_type_map,
            $type_aliases
        );
    }

    /**
     * @param  array<string, array<string, array{Type\Union}>>|null   $template_type_map
     * @param  array<string, TypeAlias> $type_aliases
     *
     * @return list<VarDocblockComment>
     *
     * @throws DocblockParseException if there was a problem parsing the docblock
     */
    public static function arrayToDocblocks(
        PhpParser\Comment\Doc $comment,
        ParsedDocblock $parsed_docblock,
        FileSource $source,
        Aliases $aliases,
        ?array $template_type_map = null,
        ?array $type_aliases = null
    ) : array {
        $var_id = null;

        $var_type_tokens = null;
        $original_type = null;

        $var_comments = [];

        $comment_text = $comment->getText();

        $var_line_number = $comment->getStartLine();

        if (isset($parsed_docblock->combined_tags['var'])) {
            foreach ($parsed_docblock->combined_tags['var'] as $offset => $var_line) {
                $var_line = trim($var_line);

                if (!$var_line) {
                    continue;
                }

                $type_start = null;
                $type_end = null;

                $line_parts = self::splitDocLine($var_line);

                $line_number = $comment->getStartLine() + substr_count($comment_text, "\n", 0, $offset);

                if ($line_parts && $line_parts[0]) {
                    $type_start = $offset + $comment->getStartFilePos();
                    $type_end = $type_start + strlen($line_parts[0]);

                    $line_parts[0] = self::sanitizeDocblockType($line_parts[0]);

                    if ($line_parts[0] === ''
                        || ($line_parts[0][0] === '$'
                            && !preg_match('/^\$this(\||$)/', $line_parts[0]))
                    ) {
                        throw new IncorrectDocblockException('Misplaced variable');
                    }

                    try {
                        $var_type_tokens = TypeTokenizer::getFullyQualifiedTokens(
                            $line_parts[0],
                            $aliases,
                            $template_type_map,
                            $type_aliases
                        );
                    } catch (TypeParseTreeException $e) {
                        throw new DocblockParseException($line_parts[0] . ' is not a valid type');
                    }

                    $original_type = $line_parts[0];

                    $var_line_number = $line_number;

                    if (count($line_parts) > 1 && $line_parts[1][0] === '$') {
                        $var_id = $line_parts[1];
                    }
                }

                if (!$var_type_tokens || !$original_type) {
                    continue;
                }

                try {
                    $defined_type = TypeParser::parseTokens(
                        $var_type_tokens,
                        null,
                        $template_type_map ?: [],
                        $type_aliases ?: []
                    );
                } catch (TypeParseTreeException $e) {
                    throw new DocblockParseException(
                        $line_parts[0] .
                        ' is not a valid type' .
                        ' (from ' .
                        $source->getFilePath() .
                        ':' .
                        $comment->getStartLine() .
                        ')'
                    );
                }

                $defined_type->setFromDocblock();

                $var_comment = new VarDocblockComment();
                $var_comment->type = $defined_type;
                $var_comment->original_type = $original_type;
                $var_comment->var_id = $var_id;
                $var_comment->line_number = $var_line_number;
                $var_comment->type_start = $type_start;
                $var_comment->type_end = $type_end;

                self::decorateVarDocblockComment($var_comment, $parsed_docblock);

                $var_comments[] = $var_comment;
            }
        }

        if (!$var_comments
            && (isset($parsed_docblock->tags['deprecated'])
                || isset($parsed_docblock->tags['internal'])
                || isset($parsed_docblock->tags['readonly'])
                || isset($parsed_docblock->tags['psalm-readonly'])
                || isset($parsed_docblock->tags['psalm-readonly-allow-private-mutation'])
                || isset($parsed_docblock->tags['psalm-taint-escape'])
                || isset($parsed_docblock->tags['psalm-internal']))
        ) {
            $var_comment = new VarDocblockComment();

            self::decorateVarDocblockComment($var_comment, $parsed_docblock);

            $var_comments[] = $var_comment;
        }

        return $var_comments;
    }

    private static function decorateVarDocblockComment(
        VarDocblockComment $var_comment,
        ParsedDocblock $parsed_docblock
    ) : void {
        $var_comment->deprecated = isset($parsed_docblock->tags['deprecated']);
        $var_comment->internal = isset($parsed_docblock->tags['internal']);
        $var_comment->readonly = isset($parsed_docblock->tags['readonly'])
            || isset($parsed_docblock->tags['psalm-readonly'])
            || isset($parsed_docblock->tags['psalm-readonly-allow-private-mutation']);

        $var_comment->allow_private_mutation
            = isset($parsed_docblock->tags['psalm-allow-private-mutation'])
            || isset($parsed_docblock->tags['psalm-readonly-allow-private-mutation']);

        if (isset($parsed_docblock->tags['psalm-taint-escape'])) {
            foreach ($parsed_docblock->tags['psalm-taint-escape'] as $param) {
                $param = trim($param);
                $var_comment->removed_taints[] = $param;
            }
        }

        if (isset($parsed_docblock->tags['psalm-internal'])) {
            $psalm_internal = reset($parsed_docblock->tags['psalm-internal']);

            if (!$psalm_internal) {
                throw new DocblockParseException('psalm-internal annotation used without specifying namespace');
            }

            $var_comment->psalm_internal = reset($parsed_docblock->tags['psalm-internal']);
            $var_comment->internal = true;
        }
    }

    /**
     * @psalm-pure
     */
    private static function sanitizeDocblockType(string $docblock_type) : string
    {
        $docblock_type = preg_replace('@^[ \t]*\*@m', '', $docblock_type);
        $docblock_type = preg_replace('/,\n\s+\}/', '}', $docblock_type);
        return str_replace("\n", '', $docblock_type);
    }

    /**
     * @param  array<string, TypeAlias> $type_aliases
     *
     * @return array<string, TypeAlias\InlineTypeAlias>
     *
     * @throws DocblockParseException if there was a problem parsing the docblock
     */
    public static function getTypeAliasesFromComment(
        PhpParser\Comment\Doc $comment,
        Aliases $aliases,
        ?array $type_aliases,
        ?string $self_fqcln
    ): array {
        $parsed_docblock = DocComment::parsePreservingLength($comment);

        if (!isset($parsed_docblock->tags['psalm-type'])) {
            return [];
        }

        return self::getTypeAliasesFromCommentLines(
            $parsed_docblock->tags['psalm-type'],
            $aliases,
            $type_aliases,
            $self_fqcln
        );
    }

    /**
     * @param  array<string>    $type_alias_comment_lines
     * @param  array<string, TypeAlias> $type_aliases
     *
     * @return array<string, TypeAlias\InlineTypeAlias>
     *
     * @throws DocblockParseException if there was a problem parsing the docblock
     */
    private static function getTypeAliasesFromCommentLines(
        array $type_alias_comment_lines,
        Aliases $aliases,
        ?array $type_aliases,
        ?string $self_fqcln
    ): array {
        $type_alias_tokens = [];

        foreach ($type_alias_comment_lines as $var_line) {
            $var_line = trim($var_line);

            if (!$var_line) {
                continue;
            }

            $var_line = preg_replace('/[ \t]+/', ' ', preg_replace('@^[ \t]*\*@m', '', $var_line));
            $var_line = preg_replace('/,\n\s+\}/', '}', $var_line);
            $var_line = str_replace("\n", '', $var_line);

            $var_line_parts = preg_split('/( |=)/', $var_line, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

            if (!$var_line_parts) {
                continue;
            }

            $type_alias = array_shift($var_line_parts);

            if (!isset($var_line_parts[0])) {
                continue;
            }

            if ($var_line_parts[0] === ' ') {
                array_shift($var_line_parts);
            }

            if ($var_line_parts[0] === '=') {
                array_shift($var_line_parts);
            }

            if (!isset($var_line_parts[0])) {
                continue;
            }

            if ($var_line_parts[0] === ' ') {
                array_shift($var_line_parts);
            }

            $type_string = str_replace("\n", '', implode('', $var_line_parts));

            $type_string = preg_replace('/>[^>^\}]*$/', '>', $type_string);
            $type_string = preg_replace('/\}[^>^\}]*$/', '}', $type_string);

            try {
                $type_tokens = TypeTokenizer::getFullyQualifiedTokens(
                    $type_string,
                    $aliases,
                    null,
                    $type_alias_tokens + $type_aliases,
                    $self_fqcln
                );
            } catch (TypeParseTreeException $e) {
                throw new DocblockParseException($type_string . ' is not a valid type');
            }

            $type_alias_tokens[$type_alias] = new TypeAlias\InlineTypeAlias($type_tokens);
        }

        return $type_alias_tokens;
    }

    /**
     * @throws DocblockParseException if there was a problem parsing the docblock
     */
    public static function extractFunctionDocblockInfo(PhpParser\Comment\Doc $comment): FunctionDocblockComment
    {
        $parsed_docblock = DocComment::parsePreservingLength($comment);

        $comment_text = $comment->getText();

        $info = new FunctionDocblockComment();

        self::checkDuplicatedTags($parsed_docblock);

        if (isset($parsed_docblock->combined_tags['return'])) {
            self::extractReturnType(
                $comment,
                $parsed_docblock->combined_tags['return'],
                $info
            );
        }

        if (isset($parsed_docblock->combined_tags['param'])) {
            foreach ($parsed_docblock->combined_tags['param'] as $offset => $param) {
                $line_parts = self::splitDocLine($param);

                if (count($line_parts) === 1 && isset($line_parts[0][0]) && $line_parts[0][0] === '$') {
                    continue;
                }

                if (count($line_parts) > 1) {
                    if (preg_match('/^&?(\.\.\.)?&?\$[A-Za-z0-9_]+,?$/', $line_parts[1])
                        && $line_parts[0][0] !== '{'
                    ) {
                        $line_parts[1] = str_replace('&', '', $line_parts[1]);

                        $line_parts[1] = preg_replace('/,$/', '', $line_parts[1]);

                        $start = $offset + $comment->getStartFilePos();
                        $end = $start + strlen($line_parts[0]);

                        $line_parts[0] = self::sanitizeDocblockType($line_parts[0]);

                        if ($line_parts[0] === ''
                            || ($line_parts[0][0] === '$'
                                && !preg_match('/^\$this(\||$)/', $line_parts[0]))
                        ) {
                            throw new IncorrectDocblockException('Misplaced variable');
                        }

                        $info->params[] = [
                            'name' => trim($line_parts[1]),
                            'type' => $line_parts[0],
                            'line_number' => $comment->getStartLine() + substr_count($comment_text, "\n", 0, $offset),
                            'start' => $start,
                            'end' => $end,
                        ];
                    }
                } else {
                    throw new DocblockParseException('Badly-formatted @param');
                }
            }
        }

        if (isset($parsed_docblock->tags['param-out'])) {
            foreach ($parsed_docblock->tags['param-out'] as $offset => $param) {
                $line_parts = self::splitDocLine($param);

                if (count($line_parts) === 1 && isset($line_parts[0][0]) && $line_parts[0][0] === '$') {
                    continue;
                }

                if (count($line_parts) > 1) {
                    if (!preg_match('/\[[^\]]+\]/', $line_parts[0])
                        && preg_match('/^(\.\.\.)?&?\$[A-Za-z0-9_]+,?$/', $line_parts[1])
                        && $line_parts[0][0] !== '{'
                    ) {
                        if ($line_parts[1][0] === '&') {
                            $line_parts[1] = substr($line_parts[1], 1);
                        }

                        $line_parts[0] = str_replace("\n", '', preg_replace('@^[ \t]*\*@m', '', $line_parts[0]));

                        if ($line_parts[0] === ''
                            || ($line_parts[0][0] === '$'
                                && !preg_match('/^\$this(\||$)/', $line_parts[0]))
                        ) {
                            throw new IncorrectDocblockException('Misplaced variable');
                        }

                        $line_parts[1] = preg_replace('/,$/', '', $line_parts[1]);

                        $info->params_out[] = [
                            'name' => trim($line_parts[1]),
                            'type' => str_replace("\n", '', $line_parts[0]),
                            'line_number' => $comment->getStartLine() + substr_count($comment_text, "\n", 0, $offset),
                        ];
                    }
                } else {
                    throw new DocblockParseException('Badly-formatted @param');
                }
            }
        }

        if (isset($parsed_docblock->tags['psalm-self-out'])) {
            foreach ($parsed_docblock->tags['psalm-self-out'] as $offset => $param) {
                $line_parts = self::splitDocLine($param);

                if (count($line_parts) > 0) {
                    $line_parts[0] = str_replace("\n", '', preg_replace('@^[ \t]*\*@m', '', $line_parts[0]));

                    $info->self_out = [
                        'type' => str_replace("\n", '', $line_parts[0]),
                        'line_number' => $comment->getStartLine() + substr_count($comment_text, "\n", 0, $offset),
                    ];
                }
            }
        }

        if (isset($parsed_docblock->tags['psalm-flow'])) {
            foreach ($parsed_docblock->tags['psalm-flow'] as $param) {
                $info->flows[] = trim($param);
            }
        }

        if (isset($parsed_docblock->tags['psalm-taint-sink'])) {
            foreach ($parsed_docblock->tags['psalm-taint-sink'] as $param) {
                $param_parts = preg_split('/\s+/', trim($param));

                if (count($param_parts) === 2) {
                    $info->taint_sink_params[] = ['name' => $param_parts[1], 'taint' => $param_parts[0]];
                }
            }
        }

        // support for MediaWiki taint plugin
        if (isset($parsed_docblock->tags['param-taint'])) {
            foreach ($parsed_docblock->tags['param-taint'] as $param) {
                $param_parts = preg_split('/\s+/', trim($param));

                if (count($param_parts) === 2) {
                    $taint_type = $param_parts[1];

                    if (substr($taint_type, 0, 5) === 'exec_') {
                        $taint_type = substr($taint_type, 5);

                        if ($taint_type === 'tainted') {
                            $taint_type = 'input';
                        }

                        if ($taint_type === 'misc') {
                            $taint_type = 'text';
                        }

                        $info->taint_sink_params[] = ['name' => $param_parts[0], 'taint' => $taint_type];
                    }
                }
            }
        }

        if (isset($parsed_docblock->tags['psalm-taint-source'])) {
            foreach ($parsed_docblock->tags['psalm-taint-source'] as $param) {
                $param_parts = preg_split('/\s+/', trim($param));

                if ($param_parts[0]) {
                    $info->taint_source_types[] = $param_parts[0];
                }
            }
        } elseif (isset($parsed_docblock->tags['return-taint'])) {
            // support for MediaWiki taint plugin
            foreach ($parsed_docblock->tags['return-taint'] as $param) {
                $param_parts = preg_split('/\s+/', trim($param));

                if ($param_parts[0]) {
                    if ($param_parts[0] === 'tainted') {
                        $param_parts[0] = 'input';
                    }

                    if ($param_parts[0] === 'misc') {
                        $param_parts[0] = 'text';
                    }

                    if ($param_parts[0] !== 'none') {
                        $info->taint_source_types[] = $param_parts[0];
                    }
                }
            }
        }

        if (isset($parsed_docblock->tags['psalm-taint-unescape'])) {
            foreach ($parsed_docblock->tags['psalm-taint-unescape'] as $param) {
                $param = trim($param);
                $info->added_taints[] = $param;
            }
        }

        if (isset($parsed_docblock->tags['psalm-taint-escape'])) {
            foreach ($parsed_docblock->tags['psalm-taint-escape'] as $param) {
                $param = trim($param);
                $info->removed_taints[] = $param;
            }
        }

        if (isset($parsed_docblock->tags['psalm-assert-untainted'])) {
            foreach ($parsed_docblock->tags['psalm-assert-untainted'] as $param) {
                $param = trim($param);

                $info->assert_untainted_params[] = ['name' => $param];
            }
        }

        if (isset($parsed_docblock->tags['psalm-taint-specialize'])) {
            $info->specialize_call = true;
        }

        if (isset($parsed_docblock->tags['global'])) {
            foreach ($parsed_docblock->tags['global'] as $offset => $global) {
                $line_parts = self::splitDocLine($global);

                if (count($line_parts) === 1 && isset($line_parts[0][0]) && $line_parts[0][0] === '$') {
                    continue;
                }

                if (count($line_parts) > 1) {
                    if (!preg_match('/\[[^\]]+\]/', $line_parts[0])
                        && preg_match('/^(\.\.\.)?&?\$[A-Za-z0-9_]+,?$/', $line_parts[1])
                        && $line_parts[0][0] !== '{'
                    ) {
                        if ($line_parts[1][0] === '&') {
                            $line_parts[1] = substr($line_parts[1], 1);
                        }

                        if ($line_parts[0][0] === '$' && !preg_match('/^\$this(\||$)/', $line_parts[0])) {
                            throw new IncorrectDocblockException('Misplaced variable');
                        }

                        $line_parts[1] = preg_replace('/,$/', '', $line_parts[1]);

                        $info->globals[] = [
                            'name' => $line_parts[1],
                            'type' => $line_parts[0],
                            'line_number' => $comment->getStartLine() + substr_count($comment_text, "\n", 0, $offset),
                        ];
                    }
                } else {
                    throw new DocblockParseException('Badly-formatted @param');
                }
            }
        }

        if (isset($parsed_docblock->tags['deprecated'])) {
            $info->deprecated = true;
        }

        if (isset($parsed_docblock->tags['internal'])) {
            $info->internal = true;
        }

        if (isset($parsed_docblock->tags['psalm-internal'])) {
            $psalm_internal = reset($parsed_docblock->tags['psalm-internal']);
            if ($psalm_internal) {
                $info->psalm_internal = $psalm_internal;
            } else {
                throw new DocblockParseException('@psalm-internal annotation used without specifying namespace');
            }
            $info->psalm_internal = reset($parsed_docblock->tags['psalm-internal']);
            $info->internal = true;
        }

        if (isset($parsed_docblock->tags['psalm-suppress'])) {
            foreach ($parsed_docblock->tags['psalm-suppress'] as $offset => $suppress_entry) {
                foreach (DocComment::parseSuppressList($suppress_entry) as $issue_offset => $suppressed_issue) {
                    $info->suppressed_issues[$issue_offset + $offset + $comment->getStartFilePos()] = $suppressed_issue;
                }
            }
        }

        if (isset($parsed_docblock->tags['throws'])) {
            foreach ($parsed_docblock->tags['throws'] as $offset => $throws_entry) {
                $throws_class = preg_split('/[\s]+/', $throws_entry)[0];

                if (!$throws_class) {
                    throw new IncorrectDocblockException('Unexpectedly empty @throws');
                }

                $info->throws[] = [
                    $throws_class,
                    $offset + $comment->getStartFilePos(),
                    $comment->getStartLine() + substr_count($comment->getText(), "\n", 0, $offset)
                ];
            }
        }

        if (strpos(strtolower($parsed_docblock->description), '@inheritdoc') !== false
            || isset($parsed_docblock->tags['inheritdoc'])
            || isset($parsed_docblock->tags['inheritDoc'])
        ) {
            $info->inheritdoc = true;
        }

        if (isset($parsed_docblock->combined_tags['template'])) {
            foreach ($parsed_docblock->combined_tags['template'] as $template_line) {
                $template_type = preg_split('/[\s]+/', preg_replace('@^[ \t]*\*@m', '', $template_line));

                $template_name = array_shift($template_type);

                if (!$template_name) {
                    throw new IncorrectDocblockException('Empty @template tag');
                }

                if (count($template_type) > 1
                    && in_array(strtolower($template_type[0]), ['as', 'super', 'of'], true)
                ) {
                    $template_modifier = strtolower(array_shift($template_type));
                    $info->templates[] = [
                        $template_name,
                        $template_modifier,
                        implode(' ', $template_type),
                        false
                    ];
                } else {
                    $info->templates[] = [$template_name, null, null, false];
                }
            }
        }

        if (isset($parsed_docblock->tags['psalm-assert'])) {
            foreach ($parsed_docblock->tags['psalm-assert'] as $assertion) {
                $line_parts = self::splitDocLine($assertion);

                if (count($line_parts) < 2 || $line_parts[1][0] !== '$') {
                    throw new IncorrectDocblockException('Misplaced variable');
                }

                $line_parts[0] = self::sanitizeDocblockType($line_parts[0]);

                $info->assertions[] = [
                    'type' => $line_parts[0],
                    'param_name' => substr($line_parts[1], 1),
                ];
            }
        }

        if (isset($parsed_docblock->tags['psalm-assert-if-true'])) {
            foreach ($parsed_docblock->tags['psalm-assert-if-true'] as $assertion) {
                $line_parts = self::splitDocLine($assertion);

                if (count($line_parts) < 2 || $line_parts[1][0] !== '$') {
                    throw new IncorrectDocblockException('Misplaced variable');
                }

                $info->if_true_assertions[] = [
                    'type' => $line_parts[0],
                    'param_name' => substr($line_parts[1], 1),
                ];
            }
        }

        if (isset($parsed_docblock->tags['psalm-assert-if-false'])) {
            foreach ($parsed_docblock->tags['psalm-assert-if-false'] as $assertion) {
                $line_parts = self::splitDocLine($assertion);

                if (count($line_parts) < 2 || $line_parts[1][0] !== '$') {
                    throw new IncorrectDocblockException('Misplaced variable');
                }

                $info->if_false_assertions[] = [
                    'type' => $line_parts[0],
                    'param_name' => substr($line_parts[1], 1),
                ];
            }
        }

        $info->variadic = isset($parsed_docblock->tags['psalm-variadic']);
        $info->pure = isset($parsed_docblock->tags['psalm-pure'])
            || isset($parsed_docblock->tags['pure']);

        if (isset($parsed_docblock->tags['psalm-mutation-free'])) {
            $info->mutation_free = true;
        }

        if (isset($parsed_docblock->tags['psalm-external-mutation-free'])) {
            $info->external_mutation_free = true;
        }

        if (isset($parsed_docblock->tags['no-named-arguments'])) {
            $info->no_named_args = true;
        }

        $info->ignore_nullable_return = isset($parsed_docblock->tags['psalm-ignore-nullable-return']);
        $info->ignore_falsable_return = isset($parsed_docblock->tags['psalm-ignore-falsable-return']);
        $info->stub_override = isset($parsed_docblock->tags['psalm-stub-override']);

        return $info;
    }

    /**
     * @param array<int, string> $return_specials
     */
    private static function extractReturnType(
        PhpParser\Comment\Doc $comment,
        array $return_specials,
        FunctionDocblockComment $info
    ): void {
        foreach ($return_specials as $offset => $return_block) {
            $return_lines = explode("\n", $return_block);

            if (!trim($return_lines[0])) {
                return;
            }

            $return_block = trim($return_block);

            if (!$return_block) {
                return;
            }

            $line_parts = self::splitDocLine($return_block);

            if ($line_parts[0][0] !== '{') {
                if ($line_parts[0][0] === '$' && !preg_match('/^\$this(\||$)/', $line_parts[0])) {
                    throw new IncorrectDocblockException('Misplaced variable');
                }

                $start = $offset + $comment->getStartFilePos();
                $end = $start + strlen($line_parts[0]);

                $line_parts[0] = self::sanitizeDocblockType($line_parts[0]);

                $info->return_type = array_shift($line_parts);
                $info->return_type_description = $line_parts ? implode(' ', $line_parts) : null;

                $info->return_type_line_number
                    = $comment->getStartLine() + substr_count($comment->getText(), "\n", 0, $offset);
                $info->return_type_start = $start;
                $info->return_type_end = $end;
            } else {
                throw new DocblockParseException('Badly-formatted @return type');
            }

            break;
        }
    }

    /**
     * @throws DocblockParseException if there was a problem parsing the docblock
     *
     * @psalm-suppress MixedArrayAccess
     */
    public static function extractClassLikeDocblockInfo(
        \PhpParser\Node $node,
        PhpParser\Comment\Doc $comment,
        Aliases $aliases
    ): ClassLikeDocblockComment {
        $parsed_docblock = DocComment::parsePreservingLength($comment);
        $codebase = ProjectAnalyzer::getInstance()->getCodebase();

        $info = new ClassLikeDocblockComment();

        if (isset($parsed_docblock->combined_tags['template'])) {
            foreach ($parsed_docblock->combined_tags['template'] as $offset => $template_line) {
                $template_type = preg_split('/[\s]+/', preg_replace('@^[ \t]*\*@m', '', $template_line));

                $template_name = array_shift($template_type);

                if (!$template_name) {
                    throw new IncorrectDocblockException('Empty @template tag');
                }

                if (count($template_type) > 1
                    && in_array(strtolower($template_type[0]), ['as', 'super', 'of'], true)
                ) {
                    $template_modifier = strtolower(array_shift($template_type));
                    $info->templates[] = [
                        $template_name,
                        $template_modifier,
                        implode(' ', $template_type),
                        false,
                        $offset
                    ];
                } else {
                    $info->templates[] = [$template_name, null, null, false, $offset];
                }
            }
        }

        if (isset($parsed_docblock->combined_tags['template-covariant'])) {
            foreach ($parsed_docblock->combined_tags['template-covariant'] as $offset => $template_line) {
                $template_type = preg_split('/[\s]+/', preg_replace('@^[ \t]*\*@m', '', $template_line));

                $template_name = array_shift($template_type);

                if (!$template_name) {
                    throw new IncorrectDocblockException('Empty @template-covariant tag');
                }

                if (count($template_type) > 1
                    && in_array(strtolower($template_type[0]), ['as', 'super', 'of'], true)
                ) {
                    $template_modifier = strtolower(array_shift($template_type));
                    $info->templates[] = [
                        $template_name,
                        $template_modifier,
                        implode(' ', $template_type),
                        true,
                        $offset
                    ];
                } else {
                    $info->templates[] = [$template_name, null, null, true, $offset];
                }
            }
        }

        if (isset($parsed_docblock->combined_tags['extends'])) {
            foreach ($parsed_docblock->combined_tags['extends'] as $template_line) {
                $info->template_extends[] = trim(preg_replace('@^[ \t]*\*@m', '', $template_line));
            }
        }

        if (isset($parsed_docblock->tags['psalm-require-extends'])
            && count($extension_requirements = $parsed_docblock->tags['psalm-require-extends']) > 0) {
            $info->extension_requirement = trim(preg_replace(
                '@^[ \t]*\*@m',
                '',
                $extension_requirements[array_key_first($extension_requirements)]
            ));
        }

        if (isset($parsed_docblock->tags['psalm-require-implements'])) {
            foreach ($parsed_docblock->tags['psalm-require-implements'] as $implementation_requirement) {
                $info->implementation_requirements[] = trim(preg_replace(
                    '@^[ \t]*\*@m',
                    '',
                    $implementation_requirement
                ));
            }
        }

        if (isset($parsed_docblock->combined_tags['implements'])) {
            foreach ($parsed_docblock->combined_tags['implements'] as $template_line) {
                $info->template_implements[] = trim(preg_replace('@^[ \t]*\*@m', '', $template_line));
            }
        }

        if (isset($parsed_docblock->tags['psalm-yield'])
        ) {
            $yield = reset($parsed_docblock->tags['psalm-yield']);

            $info->yield = trim(preg_replace('@^[ \t]*\*@m', '', $yield));
        }

        if (isset($parsed_docblock->tags['deprecated'])) {
            $info->deprecated = true;
        }

        if (isset($parsed_docblock->tags['internal'])) {
            $info->internal = true;
        }

        if (isset($parsed_docblock->tags['final'])) {
            $info->final = true;
        }

        if (isset($parsed_docblock->tags['psalm-consistent-constructor'])) {
            $info->consistent_constructor = true;
        }

        if (isset($parsed_docblock->tags['psalm-internal'])) {
            $psalm_internal = reset($parsed_docblock->tags['psalm-internal']);
            if ($psalm_internal) {
                $info->psalm_internal = $psalm_internal;
            } else {
                throw new DocblockParseException('psalm-internal annotation used without specifying namespace');
            }

            $info->internal = true;
        }

        if (isset($parsed_docblock->tags['mixin'])) {
            foreach ($parsed_docblock->tags['mixin'] as $rawMixin) {
                $mixin = trim($rawMixin);
                $doc_line_parts = self::splitDocLine($mixin);
                $mixin = $doc_line_parts[0];

                if ($mixin) {
                    $info->mixins[] = $mixin;
                } else {
                    throw new DocblockParseException('@mixin annotation used without specifying class');
                }
            }

            // backwards compatibility
            if ($info->mixins) {
                /** @psalm-suppress DeprecatedProperty */
                $info->mixin = reset($info->mixins);
            }
        }

        if (isset($parsed_docblock->tags['psalm-seal-properties'])) {
            $info->sealed_properties = true;
        }

        if (isset($parsed_docblock->tags['psalm-seal-methods'])) {
            $info->sealed_methods = true;
        }

        if (isset($parsed_docblock->tags['psalm-immutable'])
            || isset($parsed_docblock->tags['psalm-mutation-free'])
        ) {
            $info->mutation_free = true;
            $info->external_mutation_free = true;
            $info->taint_specialize = true;
        }

        if (isset($parsed_docblock->tags['psalm-external-mutation-free'])) {
            $info->external_mutation_free = true;
        }

        if (isset($parsed_docblock->tags['psalm-taint-specialize'])) {
            $info->taint_specialize = true;
        }

        if (isset($parsed_docblock->tags['psalm-override-property-visibility'])) {
            $info->override_property_visibility = true;
        }

        if (isset($parsed_docblock->tags['psalm-override-method-visibility'])) {
            $info->override_method_visibility = true;
        }

        if (isset($parsed_docblock->tags['psalm-suppress'])) {
            foreach ($parsed_docblock->tags['psalm-suppress'] as $offset => $suppress_entry) {
                foreach (DocComment::parseSuppressList($suppress_entry) as $issue_offset => $suppressed_issue) {
                    $info->suppressed_issues[$issue_offset + $offset + $comment->getStartFilePos()] = $suppressed_issue;
                }
            }
        }

        if (isset($parsed_docblock->tags['psalm-import-type'])) {
            foreach ($parsed_docblock->tags['psalm-import-type'] as $offset => $imported_type_entry) {
                $info->imported_types[] = [
                    'line_number' => $comment->getStartLine() + substr_count($comment->getText(), "\n", 0, $offset),
                    'start_offset' => $comment->getStartFilePos() + $offset,
                    'end_offset' => $comment->getStartFilePos() + $offset + strlen($imported_type_entry),
                    'parts' => self::splitDocLine($imported_type_entry) ?: []
                ];
            }
        }

        if (isset($parsed_docblock->combined_tags['method'])) {
            foreach ($parsed_docblock->combined_tags['method'] as $offset => $method_entry) {
                $method_entry = preg_replace('/[ \t]+/', ' ', trim($method_entry));

                $docblock_lines = [];

                $is_static = false;

                $has_return = false;

                if (!preg_match('/^([a-z_A-Z][a-z_0-9A-Z]+) *\(/', $method_entry, $matches)) {
                    $doc_line_parts = self::splitDocLine($method_entry);

                    if ($doc_line_parts[0] === 'static' && !strpos($doc_line_parts[1], '(')) {
                        $is_static = true;
                        array_shift($doc_line_parts);
                    }

                    if (count($doc_line_parts) > 1) {
                        $docblock_lines[] = '@return ' . array_shift($doc_line_parts);
                        $has_return = true;

                        $method_entry = implode(' ', $doc_line_parts);
                    }
                }

                $method_entry = trim(preg_replace('/\/\/.*/', '', $method_entry));

                $method_entry = preg_replace(
                    '/array\(([0-9a-zA-Z_\'\" ]+,)*([0-9a-zA-Z_\'\" ]+)\)/',
                    '[]',
                    $method_entry
                );

                $end_of_method_regex = '/(?<!array\()\) ?(\: ?(\??[\\\\a-zA-Z0-9_]+))?/';

                if (preg_match($end_of_method_regex, $method_entry, $matches, PREG_OFFSET_CAPTURE)) {
                    $method_entry = substr($method_entry, 0, (int) $matches[0][1] + strlen((string) $matches[0][0]));
                }

                $method_entry = str_replace([', ', '( '], [',', '('], $method_entry);
                $method_entry = preg_replace('/ (?!(\$|\.\.\.|&))/', '', trim($method_entry));

                // replace array bracket contents
                $method_entry = preg_replace('/\[([0-9a-zA-Z_\'\" ]+,)*([0-9a-zA-Z_\'\" ]+)\]/', '[]', $method_entry);

                if (!$method_entry) {
                    throw new DocblockParseException('No @method entry specified');
                }

                try {
                    $parse_tree_creator = new ParseTreeCreator(
                        TypeTokenizer::getFullyQualifiedTokens(
                            $method_entry,
                            $aliases,
                            null
                        )
                    );

                    $method_tree = $parse_tree_creator->create();
                } catch (TypeParseTreeException $e) {
                    throw new DocblockParseException($method_entry . ' is not a valid method');
                }

                if (!$method_tree instanceof ParseTree\MethodWithReturnTypeTree
                    && !$method_tree instanceof ParseTree\MethodTree) {
                    throw new DocblockParseException($method_entry . ' is not a valid method');
                }

                if ($method_tree instanceof ParseTree\MethodWithReturnTypeTree) {
                    if (!$has_return) {
                        $docblock_lines[] = '@return ' . TypeParser::getTypeFromTree(
                            $method_tree->children[1],
                            $codebase
                        )->toNamespacedString($aliases->namespace, $aliases->uses, null, false);
                    }

                    $method_tree = $method_tree->children[0];
                }

                if (!$method_tree instanceof ParseTree\MethodTree) {
                    throw new DocblockParseException($method_entry . ' is not a valid method');
                }

                $args = [];

                foreach ($method_tree->children as $method_tree_child) {
                    if (!$method_tree_child instanceof ParseTree\MethodParamTree) {
                        throw new DocblockParseException($method_entry . ' is not a valid method');
                    }

                    $args[] = ($method_tree_child->byref ? '&' : '')
                        . ($method_tree_child->variadic ? '...' : '')
                        . $method_tree_child->name
                        . ($method_tree_child->default != '' ? ' = ' . $method_tree_child->default : '');


                    if ($method_tree_child->children) {
                        try {
                            $param_type = TypeParser::getTypeFromTree($method_tree_child->children[0], $codebase);
                        } catch (\Exception $e) {
                            throw new DocblockParseException(
                                'Badly-formatted @method string ' . $method_entry . ' - ' . $e
                            );
                        }

                        $param_type_string = $param_type->toNamespacedString('\\', [], null, false);
                        $docblock_lines[] = '@param ' . $param_type_string . ' '
                            . ($method_tree_child->variadic ? '...' : '')
                            . $method_tree_child->name;
                    }
                }

                $function_string = 'function ' . $method_tree->value . '(' . implode(', ', $args) . ')';

                if ($is_static) {
                    $function_string = 'static ' . $function_string;
                }

                $function_docblock = $docblock_lines ? "/**\n * " . implode("\n * ", $docblock_lines) . "\n*/\n" : "";

                $php_string = '<?php class A { ' . $function_docblock . ' public ' . $function_string . '{} }';

                try {
                    $statements = \Psalm\Internal\Provider\StatementsProvider::parseStatements(
                        $php_string,
                        $codebase->php_major_version . '.' . $codebase->php_minor_version
                    );
                } catch (\Exception $e) {
                    throw new DocblockParseException('Badly-formatted @method string ' . $method_entry);
                }

                if (!$statements
                    || !$statements[0] instanceof \PhpParser\Node\Stmt\Class_
                    || !isset($statements[0]->stmts[0])
                    || !$statements[0]->stmts[0] instanceof \PhpParser\Node\Stmt\ClassMethod
                ) {
                    throw new DocblockParseException('Badly-formatted @method string ' . $method_entry);
                }

                /** @var \PhpParser\Comment\Doc */
                $node_doc_comment = $node->getDocComment();

                $statements[0]->stmts[0]->setAttribute('startLine', $node_doc_comment->getStartLine());
                $statements[0]->stmts[0]->setAttribute('startFilePos', $node_doc_comment->getStartFilePos());
                $statements[0]->stmts[0]->setAttribute('endFilePos', $node->getAttribute('startFilePos'));

                if ($doc_comment = $statements[0]->stmts[0]->getDocComment()) {
                    $statements[0]->stmts[0]->setDocComment(
                        new \PhpParser\Comment\Doc(
                            $doc_comment->getText(),
                            $comment->getStartLine() + substr_count($comment->getText(), "\n", 0, $offset),
                            $node_doc_comment->getStartFilePos()
                        )
                    );
                }

                $info->methods[] = $statements[0]->stmts[0];
            }
        }

        if (isset($parsed_docblock->tags['psalm-stub-override'])) {
            $info->stub_override = true;
        }

        self::addMagicPropertyToInfo($comment, $info, $parsed_docblock->tags, 'property');
        self::addMagicPropertyToInfo($comment, $info, $parsed_docblock->tags, 'psalm-property');
        self::addMagicPropertyToInfo($comment, $info, $parsed_docblock->tags, 'property-read');
        self::addMagicPropertyToInfo($comment, $info, $parsed_docblock->tags, 'psalm-property-read');
        self::addMagicPropertyToInfo($comment, $info, $parsed_docblock->tags, 'property-write');
        self::addMagicPropertyToInfo($comment, $info, $parsed_docblock->tags, 'psalm-property-write');

        return $info;
    }

    /**
     * @param array<string, array<int, string>> $specials
     * @param 'property'|'psalm-property'|'property-read'|
     *     'psalm-property-read'|'property-write'|'psalm-property-write' $property_tag
     *
     * @throws DocblockParseException
     *
     */
    protected static function addMagicPropertyToInfo(
        PhpParser\Comment\Doc $comment,
        ClassLikeDocblockComment $info,
        array $specials,
        string $property_tag
    ) : void {
        $magic_property_comments = isset($specials[$property_tag]) ? $specials[$property_tag] : [];

        foreach ($magic_property_comments as $offset => $property) {
            $line_parts = self::splitDocLine($property);

            if (count($line_parts) === 1 && isset($line_parts[0][0]) && $line_parts[0][0] === '$') {
                continue;
            }

            if (count($line_parts) > 1) {
                if (preg_match('/^&?\$[A-Za-z0-9_]+,?$/', $line_parts[1])
                    && $line_parts[0][0] !== '{'
                ) {
                    $line_parts[1] = str_replace('&', '', $line_parts[1]);

                    $line_parts[1] = preg_replace('/,$/', '', $line_parts[1]);

                    $start = $offset + $comment->getStartFilePos();
                    $end = $start + strlen($line_parts[0]);

                    $line_parts[0] = str_replace("\n", '', preg_replace('@^[ \t]*\*@m', '', $line_parts[0]));

                    if ($line_parts[0] === ''
                        || ($line_parts[0][0] === '$'
                            && !preg_match('/^\$this(\||$)/', $line_parts[0]))
                    ) {
                        throw new IncorrectDocblockException('Misplaced variable');
                    }

                    $name = trim($line_parts[1]);

                    if (!preg_match('/^\$([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)$/', $name)) {
                        throw new DocblockParseException('Badly-formatted @property name');
                    }

                    $info->properties[] = [
                        'name' => $name,
                        'type' => $line_parts[0],
                        'line_number' => $comment->getStartLine() + substr_count($comment->getText(), "\n", 0, $offset),
                        'tag' => $property_tag,
                        'start' => $start,
                        'end' => $end,
                    ];
                }
            } else {
                throw new DocblockParseException('Badly-formatted @property');
            }
        }
    }

    /**
     * @throws DocblockParseException if an invalid string is found
     *
     * @return list<string>
     *
     * @psalm-pure
     */
    public static function splitDocLine(string $return_block): array
    {
        $brackets = '';

        $type = '';

        $expects_callable_return = false;

        $return_block = str_replace("\t", ' ', $return_block);

        $quote_char = null;
        $escaped = false;

        for ($i = 0, $l = strlen($return_block); $i < $l; ++$i) {
            $char = $return_block[$i];
            $next_char = $i < $l - 1 ? $return_block[$i + 1] : null;
            $last_char = $i > 0 ? $return_block[$i - 1] : null;

            if ($quote_char) {
                if ($char === $quote_char && $i > 1 && !$escaped) {
                    $quote_char = null;

                    $type .= $char;

                    continue;
                }

                if ($char === '\\' && !$escaped && ($next_char === $quote_char || $next_char === '\\')) {
                    $escaped = true;

                    $type .= $char;

                    continue;
                }

                $escaped = false;

                $type .= $char;

                continue;
            }

            if ($char === '"' || $char === '\'') {
                $quote_char = $char;

                $type .= $char;

                continue;
            }

            if ($char === ':' && $last_char === ')') {
                $expects_callable_return = true;

                $type .= $char;

                continue;
            }

            if ($char === '[' || $char === '{' || $char === '(' || $char === '<') {
                $brackets .= $char;
            } elseif ($char === ']' || $char === '}' || $char === ')' || $char === '>') {
                $last_bracket = substr($brackets, -1);
                $brackets = substr($brackets, 0, -1);

                if (($char === ']' && $last_bracket !== '[')
                    || ($char === '}' && $last_bracket !== '{')
                    || ($char === ')' && $last_bracket !== '(')
                    || ($char === '>' && $last_bracket !== '<')
                ) {
                    throw new DocblockParseException('Invalid string ' . $return_block);
                }
            } elseif ($char === ' ') {
                if ($brackets) {
                    $expects_callable_return = false;
                    $type .= ' ';
                    continue;
                }

                if ($next_char === '|' || $next_char === '&') {
                    $nexter_char = $i < $l - 2 ? $return_block[$i + 2] : null;

                    if ($nexter_char === ' ') {
                        ++$i;
                        $type .= $next_char . ' ';
                        continue;
                    }
                }

                if ($last_char === '|' || $last_char === '&') {
                    $type .= ' ';
                    continue;
                }

                if ($next_char === ':') {
                    ++$i;
                    $type .= ' :';
                    $expects_callable_return = true;
                    continue;
                }

                if ($expects_callable_return) {
                    $type .= ' ';
                    $expects_callable_return = false;
                    continue;
                }

                $remaining = trim(preg_replace('@^[ \t]*\* *@m', ' ', substr($return_block, $i + 1)));

                if ($remaining) {
                    return array_merge([rtrim($type)], preg_split('/[ \s]+/', $remaining));
                }

                return [$type];
            }

            $expects_callable_return = false;

            $type .= $char;
        }

        return [$type];
    }

    /**
     * @throws DocblockParseException if a duplicate is found
     */
    private static function checkDuplicatedTags(ParsedDocblock $parsed_docblock): void
    {
        if (count($parsed_docblock->tags['return'] ?? []) > 1
            || count($parsed_docblock->tags['psalm-return'] ?? []) > 1
            || count($parsed_docblock->tags['phpstan-return'] ?? []) > 1
        ) {
            throw new DocblockParseException('Found duplicated @return or prefixed @return tag');
        }

        self::checkDuplicatedParams($parsed_docblock->tags['param'] ?? []);
        self::checkDuplicatedParams($parsed_docblock->tags['psalm-param'] ?? []);
        self::checkDuplicatedParams($parsed_docblock->tags['phpstan-param'] ?? []);
    }

    /**
     * @param array<int, string> $param
     *
     *
     * @throws DocblockParseException  if a duplicate is found
     */
    private static function checkDuplicatedParams(array $param): void
    {
        $list_names = self::extractAllParamNames($param);

        if (count($list_names) !== count(array_unique($list_names))) {
            throw new DocblockParseException('Found duplicated @param or prefixed @param tag');
        }
    }

    /**
     * @param array<int, string> $lines
     *
     * @return list<string>
     *
     * @psalm-pure
     */
    private static function extractAllParamNames(array $lines): array
    {
        $names = [];

        foreach ($lines as $line) {
            $split_by_dollar = explode('$', $line, 2);
            if (count($split_by_dollar) > 1) {
                $split_by_space = explode(' ', $split_by_dollar[1], 2);
                $names[] = $split_by_space[0];
            }
        }

        return $names;
    }
}
