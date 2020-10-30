<?php
namespace Psalm\Progress;

use const E_ERROR;
use function error_reporting;
use function fwrite;
use const PHP_OS;
use const STDERR;

abstract class Progress
{
    public function setErrorReporting(): void
    {
        error_reporting(E_ERROR);
    }

    public function debug(string $message): void
    {
    }

    public function startScanningFiles(): void
    {
    }

    public function startAnalyzingFiles(): void
    {
    }

    public function startAlteringFiles(): void
    {
    }

    public function alterFileDone(string $file_name): void
    {
    }

    public function start(int $number_of_tasks): void
    {
    }

    public function taskDone(int $level): void
    {
    }

    public function finish(): void
    {
    }

    public function write(string $message): void
    {
        fwrite(STDERR, $message);
    }

    protected static function doesTerminalSupportUtf8() : bool
    {
        if (\strtoupper(\substr(PHP_OS, 0, 3)) === 'WIN') {
            if (!\function_exists('sapi_windows_cp_is_utf8') || !\sapi_windows_cp_is_utf8()) {
                return false;
            }
        }

        return true;
    }
}
