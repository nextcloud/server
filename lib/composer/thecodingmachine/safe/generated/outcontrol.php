<?php

namespace Safe;

use Safe\Exceptions\OutcontrolException;

/**
 * This function discards the contents of the topmost output buffer and turns
 * off this output buffering. If you want to further process the buffer's
 * contents you have to call ob_get_contents before
 * ob_end_clean as the buffer contents are discarded
 * when ob_end_clean is called.
 *
 * The output buffer must be started by
 * ob_start with PHP_OUTPUT_HANDLER_CLEANABLE
 * and PHP_OUTPUT_HANDLER_REMOVABLE
 * flags. Otherwise ob_end_clean will not work.
 *
 * @throws OutcontrolException
 *
 */
function ob_end_clean(): void
{
    error_clear_last();
    $result = \ob_end_clean();
    if ($result === false) {
        throw OutcontrolException::createFromPhpError();
    }
}


/**
 * This function will send the contents of the topmost output buffer (if
 * any) and turn this output buffer off.  If you want to further
 * process the buffer's contents you have to call
 * ob_get_contents before
 * ob_end_flush as the buffer contents are
 * discarded after ob_end_flush is called.
 *
 * The output buffer must be started by
 * ob_start with PHP_OUTPUT_HANDLER_FLUSHABLE
 * and PHP_OUTPUT_HANDLER_REMOVABLE
 * flags. Otherwise ob_end_flush will not work.
 *
 * @throws OutcontrolException
 *
 */
function ob_end_flush(): void
{
    error_clear_last();
    $result = \ob_end_flush();
    if ($result === false) {
        throw OutcontrolException::createFromPhpError();
    }
}


/**
 * This function adds another name/value pair to the URL rewrite mechanism.
 * The name and value will be added to URLs (as GET parameter) and forms
 * (as hidden input fields) the same way as the session ID when transparent
 * URL rewriting is enabled with session.use_trans_sid.
 *
 * This function's behaviour is controlled by the url_rewriter.tags and
 * url_rewriter.hosts php.ini
 * parameters.
 *
 * Note that this function can be successfully called at most once per request.
 *
 * @param string $name The variable name.
 * @param string $value The variable value.
 * @throws OutcontrolException
 *
 */
function output_add_rewrite_var(string $name, string $value): void
{
    error_clear_last();
    $result = \output_add_rewrite_var($name, $value);
    if ($result === false) {
        throw OutcontrolException::createFromPhpError();
    }
}


/**
 * This function resets the URL rewriter and removes all rewrite
 * variables previously set by the output_add_rewrite_var
 * function.
 *
 * @throws OutcontrolException
 *
 */
function output_reset_rewrite_vars(): void
{
    error_clear_last();
    $result = \output_reset_rewrite_vars();
    if ($result === false) {
        throw OutcontrolException::createFromPhpError();
    }
}
