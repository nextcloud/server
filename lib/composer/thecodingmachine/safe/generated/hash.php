<?php

namespace Safe;

use Safe\Exceptions\HashException;

/**
 *
 *
 * @param string $algo Name of selected hashing algorithm (i.e. "sha256", "sha512", "haval160,4", etc..)
 * See hash_algos for a list of supported algorithms.
 *
 *
 * Non-cryptographic hash functions are not allowed.
 *
 *
 *
 * Non-cryptographic hash functions are not allowed.
 * @param string $ikm Input keying material (raw binary). Cannot be empty.
 * @param int $length Desired output length in bytes.
 * Cannot be greater than 255 times the chosen hash function size.
 *
 * If length is 0, the output length
 * will default to the chosen hash function size.
 * @param string $info Application/context-specific info string.
 * @param string $salt Salt to use during derivation.
 *
 * While optional, adding random salt significantly improves the strength of HKDF.
 * @return string Returns a string containing a raw binary representation of the derived key
 * (also known as output keying material - OKM);.
 * @throws HashException
 *
 */
function hash_hkdf(string $algo, string $ikm, int $length = 0, string $info = '', string $salt = ''): string
{
    error_clear_last();
    $result = \hash_hkdf($algo, $ikm, $length, $info, $salt);
    if ($result === false) {
        throw HashException::createFromPhpError();
    }
    return $result;
}


/**
 *
 *
 * @param \HashContext $hcontext Hashing context returned by hash_init.
 * @param string $filename URL describing location of file to be hashed; Supports fopen wrappers.
 * @param \HashContext|null $scontext Stream context as returned by stream_context_create.
 * @throws HashException
 *
 */
function hash_update_file(\HashContext $hcontext, string $filename, ?\HashContext $scontext = null): void
{
    error_clear_last();
    $result = \hash_update_file($hcontext, $filename, $scontext);
    if ($result === false) {
        throw HashException::createFromPhpError();
    }
}
