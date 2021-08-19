<?php

namespace Safe;

use Safe\Exceptions\ZlibException;

/**
 * Incrementally deflates data in the specified context.
 *
 * @param resource $context A context created with deflate_init.
 * @param string $data A chunk of data to compress.
 * @param int $flush_mode One of ZLIB_BLOCK,
 * ZLIB_NO_FLUSH,
 * ZLIB_PARTIAL_FLUSH,
 * ZLIB_SYNC_FLUSH (default),
 * ZLIB_FULL_FLUSH, ZLIB_FINISH.
 * Normally you will want to set ZLIB_NO_FLUSH to
 * maximize compression, and ZLIB_FINISH to terminate
 * with the last chunk of data. See the zlib manual for a
 * detailed description of these constants.
 * @return string Returns a chunk of compressed data.
 * @throws ZlibException
 *
 */
function deflate_add($context, string $data, int $flush_mode = ZLIB_SYNC_FLUSH): string
{
    error_clear_last();
    $result = \deflate_add($context, $data, $flush_mode);
    if ($result === false) {
        throw ZlibException::createFromPhpError();
    }
    return $result;
}


/**
 * Initializes an incremental deflate context using the specified
 * encoding.
 *
 * Note that the window option here only sets the window size
 * of the algorithm, differently from the zlib filters where the same parameter
 * also sets the encoding to use; the encoding must be set with the
 * encoding parameter.
 *
 * Limitation: there is currently no way to set the header information on a GZIP
 * compressed stream, which are set as follows: GZIP signature
 * (\x1f\x8B); compression method (\x08
 * == DEFLATE); 6 zero bytes; the operating system set to the current system
 * (\x00 = Windows, \x03 = Unix, etc.)
 *
 * @param int $encoding One of the ZLIB_ENCODING_* constants.
 * @param array $options An associative array which may contain the following elements:
 *
 *
 * level
 *
 *
 * The compression level in range -1..9; defaults to -1.
 *
 *
 *
 *
 * memory
 *
 *
 * The compression memory level in range 1..9; defaults to 8.
 *
 *
 *
 *
 * window
 *
 *
 * The zlib window size (logarithmic) in range 8..15; defaults to 15.
 *
 *
 *
 *
 * strategy
 *
 *
 * One of ZLIB_FILTERED,
 * ZLIB_HUFFMAN_ONLY, ZLIB_RLE,
 * ZLIB_FIXED or
 * ZLIB_DEFAULT_STRATEGY (the default).
 *
 *
 *
 *
 * dictionary
 *
 *
 * A string or an array of strings
 * of the preset dictionary (default: no preset dictionary).
 *
 *
 *
 *
 *
 * The compression level in range -1..9; defaults to -1.
 *
 * The compression memory level in range 1..9; defaults to 8.
 *
 * The zlib window size (logarithmic) in range 8..15; defaults to 15.
 *
 * One of ZLIB_FILTERED,
 * ZLIB_HUFFMAN_ONLY, ZLIB_RLE,
 * ZLIB_FIXED or
 * ZLIB_DEFAULT_STRATEGY (the default).
 *
 * A string or an array of strings
 * of the preset dictionary (default: no preset dictionary).
 * @return resource Returns a deflate context resource (zlib.deflate) on
 * success.
 * @throws ZlibException
 *
 */
function deflate_init(int $encoding, array $options = null)
{
    error_clear_last();
    $result = \deflate_init($encoding, $options);
    if ($result === false) {
        throw ZlibException::createFromPhpError();
    }
    return $result;
}


/**
 * Closes the given gz-file pointer.
 *
 * @param resource $zp The gz-file pointer. It must be valid, and must point to a file
 * successfully opened by gzopen.
 * @throws ZlibException
 *
 */
function gzclose($zp): void
{
    error_clear_last();
    $result = \gzclose($zp);
    if ($result === false) {
        throw ZlibException::createFromPhpError();
    }
}


/**
 * This function compresses the given string using the ZLIB
 * data format.
 *
 * For details on the ZLIB compression algorithm see the document
 * "ZLIB Compressed Data Format
 * Specification version 3.3" (RFC 1950).
 *
 * @param string $data The data to compress.
 * @param int $level The level of compression. Can be given as 0 for no compression up to 9
 * for maximum compression.
 *
 * If -1 is used, the default compression of the zlib library is used which is 6.
 * @param int $encoding One of ZLIB_ENCODING_* constants.
 * @return string The compressed string.
 * @throws ZlibException
 *
 */
function gzcompress(string $data, int $level = -1, int $encoding = ZLIB_ENCODING_DEFLATE): string
{
    error_clear_last();
    $result = \gzcompress($data, $level, $encoding);
    if ($result === false) {
        throw ZlibException::createFromPhpError();
    }
    return $result;
}


/**
 * This function returns a decoded version of the input
 * data.
 *
 * @param string $data The data to decode, encoded by gzencode.
 * @param int $length The maximum length of data to decode.
 * @return string The decoded string.
 * @throws ZlibException
 *
 */
function gzdecode(string $data, int $length = null): string
{
    error_clear_last();
    if ($length !== null) {
        $result = \gzdecode($data, $length);
    } else {
        $result = \gzdecode($data);
    }
    if ($result === false) {
        throw ZlibException::createFromPhpError();
    }
    return $result;
}


/**
 * This function compresses the given string using the DEFLATE
 * data format.
 *
 * For details on the DEFLATE compression algorithm see the document
 * "DEFLATE Compressed Data Format
 * Specification version 1.3" (RFC 1951).
 *
 * @param string $data The data to deflate.
 * @param int $level The level of compression. Can be given as 0 for no compression up to 9
 * for maximum compression. If not given, the default compression level will
 * be the default compression level of the zlib library.
 * @param int $encoding One of ZLIB_ENCODING_* constants.
 * @return string The deflated string.
 * @throws ZlibException
 *
 */
function gzdeflate(string $data, int $level = -1, int $encoding = ZLIB_ENCODING_RAW): string
{
    error_clear_last();
    $result = \gzdeflate($data, $level, $encoding);
    if ($result === false) {
        throw ZlibException::createFromPhpError();
    }
    return $result;
}


/**
 * This function returns a compressed version of the input
 * data compatible with the output of the
 * gzip program.
 *
 * For more information on the GZIP file format, see the document:
 * GZIP file format specification
 * version 4.3 (RFC 1952).
 *
 * @param string $data The data to encode.
 * @param int $level The level of compression. Can be given as 0 for no compression up to 9
 * for maximum compression. If not given, the default compression level will
 * be the default compression level of the zlib library.
 * @param int $encoding_mode The encoding mode. Can be FORCE_GZIP (the default)
 * or FORCE_DEFLATE.
 *
 * Prior to PHP 5.4.0, using FORCE_DEFLATE results in
 * a standard zlib deflated string (inclusive zlib headers) after a gzip
 * file header but without the trailing crc32 checksum.
 *
 * In PHP 5.4.0 and later, FORCE_DEFLATE generates
 * RFC 1950 compliant output, consisting of a zlib header, the deflated
 * data, and an Adler checksum.
 * @return string The encoded string.
 * @throws ZlibException
 *
 */
function gzencode(string $data, int $level = -1, int $encoding_mode = FORCE_GZIP): string
{
    error_clear_last();
    $result = \gzencode($data, $level, $encoding_mode);
    if ($result === false) {
        throw ZlibException::createFromPhpError();
    }
    return $result;
}


/**
 * Gets a (uncompressed) string of up to length - 1 bytes read from the given
 * file pointer. Reading ends when length - 1 bytes have been read, on a
 * newline, or on EOF (whichever comes first).
 *
 * @param resource $zp The gz-file pointer. It must be valid, and must point to a file
 * successfully opened by gzopen.
 * @param int $length The length of data to get.
 * @return string The uncompressed string.
 * @throws ZlibException
 *
 */
function gzgets($zp, int $length = null): string
{
    error_clear_last();
    if ($length !== null) {
        $result = \gzgets($zp, $length);
    } else {
        $result = \gzgets($zp);
    }
    if ($result === false) {
        throw ZlibException::createFromPhpError();
    }
    return $result;
}


/**
 * Identical to gzgets, except that
 * gzgetss attempts to strip any HTML and PHP
 * tags from the text it reads.
 *
 * @param resource $zp The gz-file pointer. It must be valid, and must point to a file
 * successfully opened by gzopen.
 * @param int $length The length of data to get.
 * @param string $allowable_tags You can use this optional parameter to specify tags which should not
 * be stripped.
 * @return string The uncompressed and stripped string.
 * @throws ZlibException
 *
 */
function gzgetss($zp, int $length, string $allowable_tags = null): string
{
    error_clear_last();
    if ($allowable_tags !== null) {
        $result = \gzgetss($zp, $length, $allowable_tags);
    } else {
        $result = \gzgetss($zp, $length);
    }
    if ($result === false) {
        throw ZlibException::createFromPhpError();
    }
    return $result;
}


/**
 * This function inflates a deflated string.
 *
 * @param string $data The data compressed by gzdeflate.
 * @param int $length The maximum length of data to decode.
 * @return string The original uncompressed data.
 *
 * The function will return an error if the uncompressed data is more than
 * 32768 times the length of the compressed input data
 * or more than the optional parameter length.
 * @throws ZlibException
 *
 */
function gzinflate(string $data, int $length = 0): string
{
    error_clear_last();
    $result = \gzinflate($data, $length);
    if ($result === false) {
        throw ZlibException::createFromPhpError();
    }
    return $result;
}


/**
 * Reads to EOF on the given gz-file pointer from the current position and
 * writes the (uncompressed) results to standard output.
 *
 * @param resource $zp The gz-file pointer. It must be valid, and must point to a file
 * successfully opened by gzopen.
 * @return int The number of uncompressed characters read from gz
 * and passed through to the input.
 * @throws ZlibException
 *
 */
function gzpassthru($zp): int
{
    error_clear_last();
    $result = \gzpassthru($zp);
    if ($result === false) {
        throw ZlibException::createFromPhpError();
    }
    return $result;
}


/**
 * Sets the file position indicator of the given gz-file pointer to the
 * beginning of the file stream.
 *
 * @param resource $zp The gz-file pointer. It must be valid, and must point to a file
 * successfully opened by gzopen.
 * @throws ZlibException
 *
 */
function gzrewind($zp): void
{
    error_clear_last();
    $result = \gzrewind($zp);
    if ($result === false) {
        throw ZlibException::createFromPhpError();
    }
}


/**
 * This function uncompress a compressed string.
 *
 * @param string $data The data compressed by gzcompress.
 * @param int $length The maximum length of data to decode.
 * @return string The original uncompressed data.
 *
 * The function will return an error if the uncompressed data is more than
 * 32768 times the length of the compressed input data
 * or more than the optional parameter length.
 * @throws ZlibException
 *
 */
function gzuncompress(string $data, int $length = 0): string
{
    error_clear_last();
    $result = \gzuncompress($data, $length);
    if ($result === false) {
        throw ZlibException::createFromPhpError();
    }
    return $result;
}


/**
 *
 *
 * @param resource $resource
 * @return int Returns number of bytes read so far.
 * @throws ZlibException
 *
 */
function inflate_get_read_len($resource): int
{
    error_clear_last();
    $result = \inflate_get_read_len($resource);
    if ($result === false) {
        throw ZlibException::createFromPhpError();
    }
    return $result;
}


/**
 * Usually returns either ZLIB_OK or ZLIB_STREAM_END.
 *
 * @param resource $resource
 * @return int Returns decompression status.
 * @throws ZlibException
 *
 */
function inflate_get_status($resource): int
{
    error_clear_last();
    $result = \inflate_get_status($resource);
    if ($result === false) {
        throw ZlibException::createFromPhpError();
    }
    return $result;
}


/**
 * Incrementally inflates encoded data in the specified context.
 *
 * Limitation: header information from GZIP compressed data are not made
 * available.
 *
 * @param resource $context A context created with inflate_init.
 * @param string $encoded_data A chunk of compressed data.
 * @param int $flush_mode One of ZLIB_BLOCK,
 * ZLIB_NO_FLUSH,
 * ZLIB_PARTIAL_FLUSH,
 * ZLIB_SYNC_FLUSH (default),
 * ZLIB_FULL_FLUSH, ZLIB_FINISH.
 * Normally you will want to set ZLIB_NO_FLUSH to
 * maximize compression, and ZLIB_FINISH to terminate
 * with the last chunk of data. See the zlib manual for a
 * detailed description of these constants.
 * @return string Returns a chunk of uncompressed data.
 * @throws ZlibException
 *
 */
function inflate_add($context, string $encoded_data, int $flush_mode = ZLIB_SYNC_FLUSH): string
{
    error_clear_last();
    $result = \inflate_add($context, $encoded_data, $flush_mode);
    if ($result === false) {
        throw ZlibException::createFromPhpError();
    }
    return $result;
}


/**
 * Initialize an incremental inflate context with the specified
 * encoding.
 *
 * @param int $encoding One of the ZLIB_ENCODING_* constants.
 * @param array $options An associative array which may contain the following elements:
 *
 *
 * level
 *
 *
 * The compression level in range -1..9; defaults to -1.
 *
 *
 *
 *
 * memory
 *
 *
 * The compression memory level in range 1..9; defaults to 8.
 *
 *
 *
 *
 * window
 *
 *
 * The zlib window size (logarithmic) in range 8..15; defaults to 15.
 *
 *
 *
 *
 * strategy
 *
 *
 * One of ZLIB_FILTERED,
 * ZLIB_HUFFMAN_ONLY, ZLIB_RLE,
 * ZLIB_FIXED or
 * ZLIB_DEFAULT_STRATEGY (the default).
 *
 *
 *
 *
 * dictionary
 *
 *
 * A string or an array of strings
 * of the preset dictionary (default: no preset dictionary).
 *
 *
 *
 *
 *
 * The compression level in range -1..9; defaults to -1.
 *
 * The compression memory level in range 1..9; defaults to 8.
 *
 * The zlib window size (logarithmic) in range 8..15; defaults to 15.
 *
 * One of ZLIB_FILTERED,
 * ZLIB_HUFFMAN_ONLY, ZLIB_RLE,
 * ZLIB_FIXED or
 * ZLIB_DEFAULT_STRATEGY (the default).
 *
 * A string or an array of strings
 * of the preset dictionary (default: no preset dictionary).
 * @return resource Returns an inflate context resource (zlib.inflate) on
 * success.
 * @throws ZlibException
 *
 */
function inflate_init(int $encoding, array $options = null)
{
    error_clear_last();
    $result = \inflate_init($encoding, $options);
    if ($result === false) {
        throw ZlibException::createFromPhpError();
    }
    return $result;
}


/**
 * Reads a file, decompresses it and writes it to standard output.
 *
 * readgzfile can be used to read a file which is not in
 * gzip format; in this case readgzfile will directly
 * read from the file without decompression.
 *
 * @param string $filename The file name. This file will be opened from the filesystem and its
 * contents written to standard output.
 * @param int $use_include_path You can set this optional parameter to 1, if you
 * want to search for the file in the include_path too.
 * @return int Returns the number of (uncompressed) bytes read from the file on success
 * @throws ZlibException
 *
 */
function readgzfile(string $filename, int $use_include_path = 0): int
{
    error_clear_last();
    $result = \readgzfile($filename, $use_include_path);
    if ($result === false) {
        throw ZlibException::createFromPhpError();
    }
    return $result;
}


/**
 * Uncompress any raw/gzip/zlib encoded data.
 *
 * @param string $data
 * @param int $max_decoded_len
 * @return string Returns the uncompressed data.
 * @throws ZlibException
 *
 */
function zlib_decode(string $data, int $max_decoded_len = null): string
{
    error_clear_last();
    if ($max_decoded_len !== null) {
        $result = \zlib_decode($data, $max_decoded_len);
    } else {
        $result = \zlib_decode($data);
    }
    if ($result === false) {
        throw ZlibException::createFromPhpError();
    }
    return $result;
}
