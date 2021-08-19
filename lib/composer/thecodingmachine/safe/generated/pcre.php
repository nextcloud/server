<?php

namespace Safe;

use Safe\Exceptions\PcreException;

/**
 * Searches subject for all matches to the regular
 * expression given in pattern and puts them in
 * matches in the order specified by
 * flags.
 *
 * After the first match is found, the subsequent searches  are continued
 * on from end of the last match.
 *
 * @param string $pattern The pattern to search for, as a string.
 * @param string $subject The input string.
 * @param array $matches Array of all matches in multi-dimensional array ordered according to
 * flags.
 * @param int $flags Can be a combination of the following flags (note that it doesn't make
 * sense to use PREG_PATTERN_ORDER together with
 * PREG_SET_ORDER):
 *
 *
 * PREG_PATTERN_ORDER
 *
 *
 * Orders results so that $matches[0] is an array of full
 * pattern matches, $matches[1] is an array of strings matched by
 * the first parenthesized subpattern, and so on.
 *
 *
 *
 *
 *
 * ]]>
 *
 * The above example will output:
 *
 * example: , this is a test
 * example: , this is a test
 * ]]>
 *
 *
 * So, $out[0] contains array of strings that matched full pattern,
 * and $out[1] contains array of strings enclosed by tags.
 *
 *
 *
 *
 * If the pattern contains named subpatterns, $matches
 * additionally contains entries for keys with the subpattern name.
 *
 *
 * If the pattern contains duplicate named subpatterns, only the rightmost
 * subpattern is stored in $matches[NAME].
 *
 *
 *
 * ]]>
 *
 * The above example will output:
 *
 *
 * [1] => bar
 * )
 * ]]>
 *
 *
 *
 *
 *
 *
 * PREG_SET_ORDER
 *
 *
 * Orders results so that $matches[0] is an array of first set
 * of matches, $matches[1] is an array of second set of matches,
 * and so on.
 *
 *
 *
 * ]]>
 *
 * The above example will output:
 *
 * example: , example:
 * this is a test, this is a test
 * ]]>
 *
 *
 *
 *
 *
 *
 * PREG_OFFSET_CAPTURE
 *
 *
 * If this flag is passed, for every occurring match the appendant string
 * offset (in bytes) will also be returned. Note that this changes the value of
 * matches into an array of arrays where every element is an
 * array consisting of the matched string at offset 0
 * and its string offset into subject at offset
 * 1.
 *
 *
 *
 * ]]>
 *
 * The above example will output:
 *
 * Array
 * (
 * [0] => Array
 * (
 * [0] => foobarbaz
 * [1] => 0
 * )
 *
 * )
 *
 * [1] => Array
 * (
 * [0] => Array
 * (
 * [0] => foo
 * [1] => 0
 * )
 *
 * )
 *
 * [2] => Array
 * (
 * [0] => Array
 * (
 * [0] => bar
 * [1] => 3
 * )
 *
 * )
 *
 * [3] => Array
 * (
 * [0] => Array
 * (
 * [0] => baz
 * [1] => 6
 * )
 *
 * )
 *
 * )
 * ]]>
 *
 *
 *
 *
 *
 *
 * PREG_UNMATCHED_AS_NULL
 *
 *
 * If this flag is passed, unmatched subpatterns are reported as NULL;
 * otherwise they are reported as an empty string.
 *
 *
 *
 *
 *
 * Orders results so that $matches[0] is an array of full
 * pattern matches, $matches[1] is an array of strings matched by
 * the first parenthesized subpattern, and so on.
 *
 *
 *
 *
 * ]]>
 *
 * The above example will output:
 *
 * example: , this is a test
 * example: , this is a test
 * ]]>
 *
 *
 * So, $out[0] contains array of strings that matched full pattern,
 * and $out[1] contains array of strings enclosed by tags.
 *
 *
 *
 * The above example will output:
 *
 * So, $out[0] contains array of strings that matched full pattern,
 * and $out[1] contains array of strings enclosed by tags.
 *
 * If the pattern contains named subpatterns, $matches
 * additionally contains entries for keys with the subpattern name.
 *
 * If the pattern contains duplicate named subpatterns, only the rightmost
 * subpattern is stored in $matches[NAME].
 *
 *
 *
 * ]]>
 *
 * The above example will output:
 *
 *
 * [1] => bar
 * )
 * ]]>
 *
 *
 *
 * The above example will output:
 *
 * Orders results so that $matches[0] is an array of first set
 * of matches, $matches[1] is an array of second set of matches,
 * and so on.
 *
 *
 *
 * ]]>
 *
 * The above example will output:
 *
 * example: , example:
 * this is a test, this is a test
 * ]]>
 *
 *
 *
 * The above example will output:
 *
 * If this flag is passed, for every occurring match the appendant string
 * offset (in bytes) will also be returned. Note that this changes the value of
 * matches into an array of arrays where every element is an
 * array consisting of the matched string at offset 0
 * and its string offset into subject at offset
 * 1.
 *
 *
 *
 * ]]>
 *
 * The above example will output:
 *
 * Array
 * (
 * [0] => Array
 * (
 * [0] => foobarbaz
 * [1] => 0
 * )
 *
 * )
 *
 * [1] => Array
 * (
 * [0] => Array
 * (
 * [0] => foo
 * [1] => 0
 * )
 *
 * )
 *
 * [2] => Array
 * (
 * [0] => Array
 * (
 * [0] => bar
 * [1] => 3
 * )
 *
 * )
 *
 * [3] => Array
 * (
 * [0] => Array
 * (
 * [0] => baz
 * [1] => 6
 * )
 *
 * )
 *
 * )
 * ]]>
 *
 *
 *
 * The above example will output:
 *
 * If this flag is passed, unmatched subpatterns are reported as NULL;
 * otherwise they are reported as an empty string.
 *
 * If no order flag is given, PREG_PATTERN_ORDER is
 * assumed.
 * @param int $offset Orders results so that $matches[0] is an array of full
 * pattern matches, $matches[1] is an array of strings matched by
 * the first parenthesized subpattern, and so on.
 *
 *
 *
 *
 * ]]>
 *
 * The above example will output:
 *
 * example: , this is a test
 * example: , this is a test
 * ]]>
 *
 *
 * So, $out[0] contains array of strings that matched full pattern,
 * and $out[1] contains array of strings enclosed by tags.
 *
 *
 *
 * The above example will output:
 *
 * So, $out[0] contains array of strings that matched full pattern,
 * and $out[1] contains array of strings enclosed by tags.
 *
 * If the pattern contains named subpatterns, $matches
 * additionally contains entries for keys with the subpattern name.
 *
 * If the pattern contains duplicate named subpatterns, only the rightmost
 * subpattern is stored in $matches[NAME].
 *
 *
 *
 * ]]>
 *
 * The above example will output:
 *
 *
 * [1] => bar
 * )
 * ]]>
 *
 *
 *
 * The above example will output:
 * @return int Returns the number of full pattern matches (which might be zero).
 * @throws PcreException
 *
 */
function preg_match_all(string $pattern, string $subject, array &$matches = null, int $flags = PREG_PATTERN_ORDER, int $offset = 0): int
{
    error_clear_last();
    $result = \preg_match_all($pattern, $subject, $matches, $flags, $offset);
    if ($result === false) {
        throw PcreException::createFromPhpError();
    }
    return $result;
}


/**
 * Searches subject for a match to the regular
 * expression given in pattern.
 *
 * @param string $pattern The pattern to search for, as a string.
 * @param string $subject The input string.
 * @param array $matches If matches is provided, then it is filled with
 * the results of search. $matches[0] will contain the
 * text that matched the full pattern, $matches[1]
 * will have the text that matched the first captured parenthesized
 * subpattern, and so on.
 * @param int $flags flags can be a combination of the following flags:
 *
 *
 * PREG_OFFSET_CAPTURE
 *
 *
 * If this flag is passed, for every occurring match the appendant string
 * offset (in bytes) will also be returned. Note that this changes the value of
 * matches into an array where every element is an
 * array consisting of the matched string at offset 0
 * and its string offset into subject at offset
 * 1.
 *
 *
 *
 * ]]>
 *
 * The above example will output:
 *
 * Array
 * (
 * [0] => foobarbaz
 * [1] => 0
 * )
 *
 * [1] => Array
 * (
 * [0] => foo
 * [1] => 0
 * )
 *
 * [2] => Array
 * (
 * [0] => bar
 * [1] => 3
 * )
 *
 * [3] => Array
 * (
 * [0] => baz
 * [1] => 6
 * )
 *
 * )
 * ]]>
 *
 *
 *
 *
 *
 *
 * PREG_UNMATCHED_AS_NULL
 *
 *
 * If this flag is passed, unmatched subpatterns are reported as NULL;
 * otherwise they are reported as an empty string.
 *
 *
 *
 * ]]>
 *
 * The above example will output:
 *
 *
 * string(2) "ac"
 * [1]=>
 * string(1) "a"
 * [2]=>
 * string(0) ""
 * [3]=>
 * string(1) "c"
 * }
 * array(4) {
 * [0]=>
 * string(2) "ac"
 * [1]=>
 * string(1) "a"
 * [2]=>
 * NULL
 * [3]=>
 * string(1) "c"
 * }
 * ]]>
 *
 *
 *
 *
 *
 *
 *
 * If this flag is passed, for every occurring match the appendant string
 * offset (in bytes) will also be returned. Note that this changes the value of
 * matches into an array where every element is an
 * array consisting of the matched string at offset 0
 * and its string offset into subject at offset
 * 1.
 *
 *
 *
 * ]]>
 *
 * The above example will output:
 *
 * Array
 * (
 * [0] => foobarbaz
 * [1] => 0
 * )
 *
 * [1] => Array
 * (
 * [0] => foo
 * [1] => 0
 * )
 *
 * [2] => Array
 * (
 * [0] => bar
 * [1] => 3
 * )
 *
 * [3] => Array
 * (
 * [0] => baz
 * [1] => 6
 * )
 *
 * )
 * ]]>
 *
 *
 *
 * The above example will output:
 *
 * If this flag is passed, unmatched subpatterns are reported as NULL;
 * otherwise they are reported as an empty string.
 *
 *
 *
 * ]]>
 *
 * The above example will output:
 *
 *
 * string(2) "ac"
 * [1]=>
 * string(1) "a"
 * [2]=>
 * string(0) ""
 * [3]=>
 * string(1) "c"
 * }
 * array(4) {
 * [0]=>
 * string(2) "ac"
 * [1]=>
 * string(1) "a"
 * [2]=>
 * NULL
 * [3]=>
 * string(1) "c"
 * }
 * ]]>
 *
 *
 *
 * The above example will output:
 * @param int $offset If this flag is passed, for every occurring match the appendant string
 * offset (in bytes) will also be returned. Note that this changes the value of
 * matches into an array where every element is an
 * array consisting of the matched string at offset 0
 * and its string offset into subject at offset
 * 1.
 *
 *
 *
 * ]]>
 *
 * The above example will output:
 *
 * Array
 * (
 * [0] => foobarbaz
 * [1] => 0
 * )
 *
 * [1] => Array
 * (
 * [0] => foo
 * [1] => 0
 * )
 *
 * [2] => Array
 * (
 * [0] => bar
 * [1] => 3
 * )
 *
 * [3] => Array
 * (
 * [0] => baz
 * [1] => 6
 * )
 *
 * )
 * ]]>
 *
 *
 *
 * The above example will output:
 * @return int preg_match returns 1 if the pattern
 * matches given subject, 0 if it does not.
 * @throws PcreException
 *
 */
function preg_match(string $pattern, string $subject, array &$matches = null, int $flags = 0, int $offset = 0): int
{
    error_clear_last();
    $result = \preg_match($pattern, $subject, $matches, $flags, $offset);
    if ($result === false) {
        throw PcreException::createFromPhpError();
    }
    return $result;
}


/**
 * Split the given string by a regular expression.
 *
 * @param string $pattern The pattern to search for, as a string.
 * @param string $subject The input string.
 * @param int|null $limit If specified, then only substrings up to limit
 * are returned with the rest of the string being placed in the last
 * substring.  A limit of -1 or 0 means "no limit".
 * @param int $flags flags can be any combination of the following
 * flags (combined with the | bitwise operator):
 *
 *
 * PREG_SPLIT_NO_EMPTY
 *
 *
 * If this flag is set, only non-empty pieces will be returned by
 * preg_split.
 *
 *
 *
 *
 * PREG_SPLIT_DELIM_CAPTURE
 *
 *
 * If this flag is set, parenthesized expression in the delimiter pattern
 * will be captured and returned as well.
 *
 *
 *
 *
 * PREG_SPLIT_OFFSET_CAPTURE
 *
 *
 * If this flag is set, for every occurring match the appendant string
 * offset will also be returned. Note that this changes the return
 * value in an array where every element is an array consisting of the
 * matched string at offset 0 and its string offset
 * into subject at offset 1.
 *
 *
 *
 *
 *
 * If this flag is set, for every occurring match the appendant string
 * offset will also be returned. Note that this changes the return
 * value in an array where every element is an array consisting of the
 * matched string at offset 0 and its string offset
 * into subject at offset 1.
 * @return array Returns an array containing substrings of subject
 * split along boundaries matched by pattern.
 * @throws PcreException
 *
 */
function preg_split(string $pattern, string $subject, ?int $limit = -1, int $flags = 0): array
{
    error_clear_last();
    $result = \preg_split($pattern, $subject, $limit, $flags);
    if ($result === false) {
        throw PcreException::createFromPhpError();
    }
    return $result;
}
