<?php
namespace Psalm;

use function array_filter;
use function explode;
use function implode;
use function in_array;
use function min;
use function preg_match;
use function preg_match_all;
use function preg_replace;
use const PREG_SET_ORDER;
use Psalm\Internal\Scanner\ParsedDocblock;
use Psalm\Exception\DocblockParseException;
use function rtrim;
use function str_repeat;
use function str_replace;
use function strlen;
use function substr;
use function trim;
use function strspn;

class DocComment
{
    private const PSALM_ANNOTATIONS = [
        'return', 'param', 'template', 'var', 'type',
        'template-covariant', 'property', 'property-read', 'property-write', 'method',
        'assert', 'assert-if-true', 'assert-if-false', 'suppress',
        'ignore-nullable-return', 'override-property-visibility',
        'override-method-visibility', 'seal-properties', 'seal-methods',
        'generator-return', 'ignore-falsable-return', 'variadic', 'pure',
        'ignore-variable-method', 'ignore-variable-property', 'internal',
        'taint-sink', 'taint-source', 'assert-untainted', 'scope-this',
        'mutation-free', 'external-mutation-free', 'immutable', 'readonly',
        'allow-private-mutation', 'readonly-allow-private-mutation',
        'yield', 'trace', 'import-type', 'flow', 'taint-specialize', 'taint-escape',
        'taint-unescape', 'self-out', 'consistent-constructor', 'stub-override',
        'require-extends', 'require-implements',
    ];

    /**
     * Parse a docblock comment into its parts.
     *
     * Taken from advanced api docmaker, which was taken from
     * https://github.com/facebook/libphutil/blob/master/src/parser/docblock/PhutilDocblockParser.php
     *
     * @return array Array of the main comment and specials
     *
     * @psalm-return array{description:string, specials:array<string, array<int, string>>}
     * @psalm-suppress PossiblyUnusedMethod
     *
     * @deprecated use parsePreservingLength instead
     *
     * @psalm-pure
     */
    public static function parse(string $docblock, ?int $line_number = null, bool $preserve_format = false): array
    {
        // Strip off comments.
        $docblock = trim($docblock);
        $docblock = preg_replace('@^/\*\*@', '', $docblock);
        $docblock = preg_replace('@\*/$@', '', $docblock);
        $docblock = preg_replace('@^[ \t]*\*@m', '', $docblock);

        // Normalize multi-line @specials.
        $lines = explode("\n", $docblock);

        $line_map = [];

        $last = false;
        foreach ($lines as $k => $line) {
            if (preg_match('/^\s?@\w/i', $line)) {
                $last = $k;
            } elseif (preg_match('/^\s*$/', $line)) {
                $last = false;
            } elseif ($last !== false) {
                $old_last_line = $lines[$last];
                $lines[$last] = rtrim($old_last_line)
                    . ($preserve_format || trim($old_last_line) === '@return' ? "\n" . $line : ' ' . trim($line));

                if ($line_number) {
                    $old_line_number = $line_map[$old_last_line];
                    unset($line_map[$old_last_line]);
                    $line_map[$lines[$last]] = $old_line_number;
                }

                unset($lines[$k]);
            }

            if ($line_number) {
                $line_map[$line] = $line_number++;
            }
        }

        $special = [];

        if ($preserve_format) {
            foreach ($lines as $m => $line) {
                if (preg_match('/^\s?@([\w\-:]+)[\t ]*(.*)$/sm', $line, $matches)) {
                    [$full_match, $type, $data] = $matches;

                    $docblock = str_replace($full_match, '', $docblock);

                    if (empty($special[$type])) {
                        $special[$type] = [];
                    }

                    $line_number = $line_map && isset($line_map[$full_match]) ? $line_map[$full_match] : (int)$m;

                    $special[$type][$line_number] = rtrim($data);
                }
            }
        } else {
            $docblock = implode("\n", $lines);

            // Parse @specials.
            if (preg_match_all('/^\s?@([\w\-:]+)[\t ]*([^\n]*)/m', $docblock, $matches, PREG_SET_ORDER)) {
                $docblock = preg_replace('/^\s?@([\w\-:]+)\s*([^\n]*)/m', '', $docblock);
                foreach ($matches as $m => $match) {
                    [$_, $type, $data] = $match;

                    if (empty($special[$type])) {
                        $special[$type] = [];
                    }

                    $line_number = $line_map && isset($line_map[$_]) ? $line_map[$_] : (int)$m;

                    $special[$type][$line_number] = $data;
                }
            }
        }

        $docblock = str_replace("\t", '  ', $docblock);

        // Smush the whole docblock to the left edge.
        $min_indent = 80;
        $indent = 0;
        foreach (array_filter(explode("\n", $docblock)) as $line) {
            for ($ii = 0, $iiMax = strlen($line); $ii < $iiMax; ++$ii) {
                if ($line[$ii] !== ' ') {
                    break;
                }
                ++$indent;
            }

            $min_indent = min($indent, $min_indent);
        }

        $docblock = preg_replace('/^' . str_repeat(' ', $min_indent) . '/m', '', $docblock);
        $docblock = rtrim($docblock);

        // Trim any empty lines off the front, but leave the indent level if there
        // is one.
        $docblock = preg_replace('/^\s*\n/', '', $docblock);

        foreach ($special as $special_key => $_) {
            if (substr($special_key, 0, 6) === 'psalm-') {
                $special_key = substr($special_key, 6);

                if (!in_array(
                    $special_key,
                    self::PSALM_ANNOTATIONS,
                    true
                )) {
                    throw new DocblockParseException('Unrecognised annotation @psalm-' . $special_key);
                }
            }
        }

        return [
            'description' => $docblock,
            'specials' => $special,
        ];
    }

    /**
     * Parse a docblock comment into its parts.
     *
     * @param  bool    $preserve_format
     */
    public static function parsePreservingLength(\PhpParser\Comment\Doc $docblock) : ParsedDocblock
    {
        $parsed_docblock = \Psalm\Internal\Scanner\DocblockParser::parse($docblock->getText());

        foreach ($parsed_docblock->tags as $special_key => $_) {
            if (substr($special_key, 0, 6) === 'psalm-') {
                $special_key = substr($special_key, 6);

                if (!in_array(
                    $special_key,
                    self::PSALM_ANNOTATIONS,
                    true
                )) {
                    throw new DocblockParseException('Unrecognised annotation @psalm-' . $special_key);
                }
            }
        }

        return $parsed_docblock;
    }

    /**
     * @psalm-pure
     * @return array<int,string>
     */
    public static function parseSuppressList(string $suppress_entry): array
    {
        preg_match(
            '/
                (?(DEFINE)
                    # either a single issue or comma separated list of issues
                    (?<issue_list> (?&issue) \s* , \s* (?&issue_list) | (?&issue) )

                    # definition of a single issue
                    (?<issue> [A-Za-z0-9_-]+ )
                )
                ^ (?P<issues> (?&issue_list) ) (?P<description> .* ) $
            /xm',
            $suppress_entry,
            $matches
        );

        if (!isset($matches['issues'])) {
            return [];
        }

        $issue_offset = 0;
        $ret = [];

        foreach (explode(',', $matches['issues']) as $suppressed_issue) {
            $issue_offset += strspn($suppressed_issue, "\t\n\f\r ");
            $ret[$issue_offset] = trim($suppressed_issue);
            $issue_offset += strlen($suppressed_issue) + 1;
        }

        return $ret;
    }
}
