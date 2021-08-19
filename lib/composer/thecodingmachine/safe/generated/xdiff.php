<?php

namespace Safe;

use Safe\Exceptions\XdiffException;

/**
 * Makes a binary diff of two files and stores the result in a patch file.
 * This function works with both text and binary files. Resulting patch
 * file can be later applied using xdiff_file_bpatch/xdiff_string_bpatch.
 *
 * @param string $old_file Path to the first file. This file acts as "old" file.
 * @param string $new_file Path to the second file. This file acts as "new" file.
 * @param string $dest Path of the resulting patch file. Resulting file contains differences
 * between "old" and "new" files. It is in binary format and is human-unreadable.
 * @throws XdiffException
 *
 */
function xdiff_file_bdiff(string $old_file, string $new_file, string $dest): void
{
    error_clear_last();
    $result = \xdiff_file_bdiff($old_file, $new_file, $dest);
    if ($result === false) {
        throw XdiffException::createFromPhpError();
    }
}


/**
 * Patches a file with a binary
 * patch and stores the result in a file dest.
 * This function accepts patches created both via xdiff_file_bdiff
 * and xdiff_file_rabdiff functions or their string counterparts.
 *
 * @param string $file The original file.
 * @param string $patch The binary patch file.
 * @param string $dest Path of the resulting file.
 * @throws XdiffException
 *
 */
function xdiff_file_bpatch(string $file, string $patch, string $dest): void
{
    error_clear_last();
    $result = \xdiff_file_bpatch($file, $patch, $dest);
    if ($result === false) {
        throw XdiffException::createFromPhpError();
    }
}


/**
 * Makes a binary diff of two files and stores the result in a patch file.
 * This function works with both text and binary files. Resulting patch
 * file can be later applied using xdiff_file_bpatch.
 *
 * Starting with version 1.5.0 this function is an alias of xdiff_file_bdiff.
 *
 * @param string $old_file Path to the first file. This file acts as "old" file.
 * @param string $new_file Path to the second file. This file acts as "new" file.
 * @param string $dest Path of the resulting patch file. Resulting file contains differences
 * between "old" and "new" files. It is in binary format and is human-unreadable.
 * @throws XdiffException
 *
 */
function xdiff_file_diff_binary(string $old_file, string $new_file, string $dest): void
{
    error_clear_last();
    $result = \xdiff_file_diff_binary($old_file, $new_file, $dest);
    if ($result === false) {
        throw XdiffException::createFromPhpError();
    }
}


/**
 * Makes an unified diff containing differences between old_file and
 * new_file and stores it in dest file. The
 * resulting file is human-readable. An optional context parameter
 * specifies how many lines of context should be added around each change.
 * Setting minimal parameter to true will result in outputting the shortest
 * patch file possible (can take a long time).
 *
 * @param string $old_file Path to the first file. This file acts as "old" file.
 * @param string $new_file Path to the second file. This file acts as "new" file.
 * @param string $dest Path of the resulting patch file.
 * @param int $context Indicates how many lines of context you want to include in diff
 * result.
 * @param bool $minimal Set this parameter to TRUE if you want to minimalize size of the result
 * (can take a long time).
 * @throws XdiffException
 *
 */
function xdiff_file_diff(string $old_file, string $new_file, string $dest, int $context = 3, bool $minimal = false): void
{
    error_clear_last();
    $result = \xdiff_file_diff($old_file, $new_file, $dest, $context, $minimal);
    if ($result === false) {
        throw XdiffException::createFromPhpError();
    }
}


/**
 * Patches a file with a binary
 * patch and stores the result in a file dest.
 * This function accepts patches created both via xdiff_file_bdiff
 * or xdiff_file_rabdiff functions or their string counterparts.
 *
 * Starting with version 1.5.0 this function is an alias of xdiff_file_bpatch.
 *
 * @param string $file The original file.
 * @param string $patch The binary patch file.
 * @param string $dest Path of the resulting file.
 * @throws XdiffException
 *
 */
function xdiff_file_patch_binary(string $file, string $patch, string $dest): void
{
    error_clear_last();
    $result = \xdiff_file_patch_binary($file, $patch, $dest);
    if ($result === false) {
        throw XdiffException::createFromPhpError();
    }
}


/**
 * Makes a binary diff of two files and stores the result in a patch file.
 * The difference between this function and xdiff_file_bdiff is different
 * algorithm used which should result in faster execution and smaller diff produced.
 * This function works with both text and binary files. Resulting patch
 * file can be later applied using xdiff_file_bpatch/xdiff_string_bpatch.
 *
 * For more details about differences between algorithm used please check libxdiff
 * website.
 *
 * @param string $old_file Path to the first file. This file acts as "old" file.
 * @param string $new_file Path to the second file. This file acts as "new" file.
 * @param string $dest Path of the resulting patch file. Resulting file contains differences
 * between "old" and "new" files. It is in binary format and is human-unreadable.
 * @throws XdiffException
 *
 */
function xdiff_file_rabdiff(string $old_file, string $new_file, string $dest): void
{
    error_clear_last();
    $result = \xdiff_file_rabdiff($old_file, $new_file, $dest);
    if ($result === false) {
        throw XdiffException::createFromPhpError();
    }
}


/**
 * Patches a string str with a binary patch.
 * This function accepts patches created both via xdiff_string_bdiff
 * and xdiff_string_rabdiff functions or their file counterparts.
 *
 * @param string $str The original binary string.
 * @param string $patch The binary patch string.
 * @return string Returns the patched string.
 * @throws XdiffException
 *
 */
function xdiff_string_bpatch(string $str, string $patch): string
{
    error_clear_last();
    $result = \xdiff_string_bpatch($str, $patch);
    if ($result === false) {
        throw XdiffException::createFromPhpError();
    }
    return $result;
}


/**
 * Patches a string str with a binary patch.
 * This function accepts patches created both via xdiff_string_bdiff
 * and xdiff_string_rabdiff functions or their file counterparts.
 *
 * Starting with version 1.5.0 this function is an alias of xdiff_string_bpatch.
 *
 * @param string $str The original binary string.
 * @param string $patch The binary patch string.
 * @return string Returns the patched string.
 * @throws XdiffException
 *
 */
function xdiff_string_patch_binary(string $str, string $patch): string
{
    error_clear_last();
    $result = \xdiff_string_patch_binary($str, $patch);
    if ($result === false) {
        throw XdiffException::createFromPhpError();
    }
    return $result;
}


/**
 * Patches a str string with an unified patch in patch parameter
 * and returns the result. patch has to be an unified diff created by
 * xdiff_file_diff/xdiff_string_diff function.
 * An optional flags parameter specifies mode of operation. Any
 * rejected parts of the patch will be stored inside error variable if
 * it is provided.
 *
 * @param string $str The original string.
 * @param string $patch The unified patch string. It has to be created using xdiff_string_diff,
 * xdiff_file_diff functions or compatible tools.
 * @param int $flags flags can be either
 * XDIFF_PATCH_NORMAL (default mode, normal patch)
 * or XDIFF_PATCH_REVERSE (reversed patch).
 *
 * Starting from version 1.5.0, you can also use binary OR to enable
 * XDIFF_PATCH_IGNORESPACE flag.
 * @param string|null $error If provided then rejected parts are stored inside this variable.
 * @return string Returns the patched string.
 * @throws XdiffException
 *
 */
function xdiff_string_patch(string $str, string $patch, int $flags = null, ?string &$error = null): string
{
    error_clear_last();
    if ($error !== null) {
        $result = \xdiff_string_patch($str, $patch, $flags, $error);
    } elseif ($flags !== null) {
        $result = \xdiff_string_patch($str, $patch, $flags);
    } else {
        $result = \xdiff_string_patch($str, $patch);
    }
    if ($result === false) {
        throw XdiffException::createFromPhpError();
    }
    return $result;
}
