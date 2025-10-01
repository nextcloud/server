<?php
/**
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0.
 */
namespace AWS\CRT;
use AWS\CRT\CRT;

final class Log {
    const NONE = 0;
    const FATAL = 1;
    const ERROR = 2;
    const WARN = 3;
    const INFO = 4;
    const DEBUG = 5;
    const TRACE = 6;

    public static function toStdout() {
        CRT::log_to_stdout();
    }

    public static function toStderr() {
        CRT::log_to_stderr();
    }

    public static function toFile($filename) {
        CRT::log_to_file($filename);
    }

    public static function toStream($stream) {
        assert(get_resource_type($stream) == "stream");
        CRT::log_to_stream($stream);
    }

    public static function stop() {
        CRT::log_stop();
    }

    public static function setLogLevel($level) {
        assert($level >= self::NONE && $level <= self::TRACE);
        CRT::log_set_level($level);
    }

    public static function log($level, $message) {
        CRT::log_message($level, $message);
    }
}
