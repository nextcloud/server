<?php

namespace Safe;

use Safe\Exceptions\ErrorfuncException;

/**
 * Sends an error message to the web server's error log or to a file.
 *
 * @param string $message The error message that should be logged.
 * @param int $message_type Says where the error should go. The possible message types are as
 * follows:
 *
 *
 * error_log log types
 *
 *
 *
 * 0
 *
 * message is sent to PHP's system logger, using
 * the Operating System's system logging mechanism or a file, depending
 * on what the error_log
 * configuration directive is set to.  This is the default option.
 *
 *
 *
 * 1
 *
 * message is sent by email to the address in
 * the destination parameter.  This is the only
 * message type where the fourth parameter,
 * extra_headers is used.
 *
 *
 *
 * 2
 *
 * No longer an option.
 *
 *
 *
 * 3
 *
 * message is appended to the file
 * destination. A newline is not automatically
 * added to the end of the message string.
 *
 *
 *
 * 4
 *
 * message is sent directly to the SAPI logging
 * handler.
 *
 *
 *
 *
 *
 * @param string $destination The destination. Its meaning depends on the
 * message_type parameter as described above.
 * @param string $extra_headers The extra headers. It's used when the message_type
 * parameter is set to 1.
 * This message type uses the same internal function as
 * mail does.
 * @throws ErrorfuncException
 *
 */
function error_log(string $message, int $message_type = 0, string $destination = null, string $extra_headers = null): void
{
    error_clear_last();
    if ($extra_headers !== null) {
        $result = \error_log($message, $message_type, $destination, $extra_headers);
    } elseif ($destination !== null) {
        $result = \error_log($message, $message_type, $destination);
    } else {
        $result = \error_log($message, $message_type);
    }
    if ($result === false) {
        throw ErrorfuncException::createFromPhpError();
    }
}
