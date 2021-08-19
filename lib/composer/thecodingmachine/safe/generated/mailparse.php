<?php

namespace Safe;

use Safe\Exceptions\MailparseException;

/**
 * Extracts/decodes a message section from the supplied filename.
 *
 * The contents of the section will be decoded according to their transfer
 * encoding - base64, quoted-printable and uuencoded text are supported.
 *
 * @param resource $mimemail A valid MIME resource, created with
 * mailparse_msg_create.
 * @param mixed $filename Can be a file name or a valid stream resource.
 * @param callable $callbackfunc If set, this must be either a valid callback that will be passed the
 * extracted section, or NULL to make this function return the
 * extracted section.
 *
 * If not specified, the contents will be sent to "stdout".
 * @return string If callbackfunc is not NULL returns TRUE on
 * success.
 *
 * If callbackfunc is set to NULL, returns the
 * extracted section as a string.
 * @throws MailparseException
 *
 */
function mailparse_msg_extract_part_file($mimemail, $filename, callable $callbackfunc = null): string
{
    error_clear_last();
    if ($callbackfunc !== null) {
        $result = \mailparse_msg_extract_part_file($mimemail, $filename, $callbackfunc);
    } else {
        $result = \mailparse_msg_extract_part_file($mimemail, $filename);
    }
    if ($result === false) {
        throw MailparseException::createFromPhpError();
    }
    return $result;
}


/**
 * Frees a MIME resource.
 *
 * @param resource $mimemail A valid MIME resource allocated by
 * mailparse_msg_create or
 * mailparse_msg_parse_file.
 * @throws MailparseException
 *
 */
function mailparse_msg_free($mimemail): void
{
    error_clear_last();
    $result = \mailparse_msg_free($mimemail);
    if ($result === false) {
        throw MailparseException::createFromPhpError();
    }
}


/**
 * Parses a file.
 * This is the optimal way of parsing a mail file that you have on disk.
 *
 * @param string $filename Path to the file holding the message.
 * The file is opened and streamed through the parser.
 *
 * The message contained in filename is supposed to end with a newline
 * (CRLF); otherwise the last line of the message will not be parsed.
 * @return resource Returns a MIME resource representing the structure.
 * @throws MailparseException
 *
 */
function mailparse_msg_parse_file(string $filename)
{
    error_clear_last();
    $result = \mailparse_msg_parse_file($filename);
    if ($result === false) {
        throw MailparseException::createFromPhpError();
    }
    return $result;
}


/**
 * Incrementally parse data into the supplied mime mail resource.
 *
 * This function allow you to stream portions of a file at a time, rather
 * than read and parse the whole thing.
 *
 * @param resource $mimemail A valid MIME resource.
 * @param string $data The final chunk of data is supposed to end with a newline
 * (CRLF); otherwise the last line of the message will not be parsed.
 * @throws MailparseException
 *
 */
function mailparse_msg_parse($mimemail, string $data): void
{
    error_clear_last();
    $result = \mailparse_msg_parse($mimemail, $data);
    if ($result === false) {
        throw MailparseException::createFromPhpError();
    }
}


/**
 * Streams data from the source file pointer, apply
 * encoding and write to the destination file pointer.
 *
 * @param resource $sourcefp A valid file handle. The file is streamed through the parser.
 * @param resource $destfp The destination file handle in which the encoded data will be written.
 * @param string $encoding One of the character encodings supported by the
 * mbstring module.
 * @throws MailparseException
 *
 */
function mailparse_stream_encode($sourcefp, $destfp, string $encoding): void
{
    error_clear_last();
    $result = \mailparse_stream_encode($sourcefp, $destfp, $encoding);
    if ($result === false) {
        throw MailparseException::createFromPhpError();
    }
}
