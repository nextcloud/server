<?php

namespace Safe;

use Safe\Exceptions\SsdeepException;

/**
 * Calculates the match score between signature1
 * and signature2 using
 * context-triggered piecewise hashing, and returns the match
 * score.
 *
 * @param string $signature1 The first fuzzy hash signature string.
 * @param string $signature2 The second fuzzy hash signature string.
 * @return int Returns an integer from 0 to 100 on success, FALSE otherwise.
 * @throws SsdeepException
 *
 */
function ssdeep_fuzzy_compare(string $signature1, string $signature2): int
{
    error_clear_last();
    $result = \ssdeep_fuzzy_compare($signature1, $signature2);
    if ($result === false) {
        throw SsdeepException::createFromPhpError();
    }
    return $result;
}


/**
 * ssdeep_fuzzy_hash_filename calculates the hash
 * of the file specified by file_name using
 * context-triggered piecewise
 * hashing, and returns that hash.
 *
 * @param string $file_name The filename of the file to hash.
 * @return string Returns a string on success, FALSE otherwise.
 * @throws SsdeepException
 *
 */
function ssdeep_fuzzy_hash_filename(string $file_name): string
{
    error_clear_last();
    $result = \ssdeep_fuzzy_hash_filename($file_name);
    if ($result === false) {
        throw SsdeepException::createFromPhpError();
    }
    return $result;
}


/**
 * ssdeep_fuzzy_hash calculates the hash of
 * to_hash using
 * context-triggered piecewise hashing, and returns that hash.
 *
 * @param string $to_hash The input string.
 * @return string Returns a string on success, FALSE otherwise.
 * @throws SsdeepException
 *
 */
function ssdeep_fuzzy_hash(string $to_hash): string
{
    error_clear_last();
    $result = \ssdeep_fuzzy_hash($to_hash);
    if ($result === false) {
        throw SsdeepException::createFromPhpError();
    }
    return $result;
}
