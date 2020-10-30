<?php
/**
 * If there is an uncaught exception,
 * then print more of the backtrace than is done by default to stderr,
 * then exit with a non-zero exit code to indicate failure.
 */
set_exception_handler(static function (Throwable $throwable) : void {
    fwrite(STDERR, "Uncaught $throwable\n");
    $version = defined('PSALM_VERSION') ? PSALM_VERSION : '(unknown version)';
    fwrite(STDERR, "(Psalm $version crashed due to an uncaught Throwable)\n");
    exit(1);
});
