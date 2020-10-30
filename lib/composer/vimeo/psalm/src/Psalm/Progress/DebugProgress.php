<?php
namespace Psalm\Progress;

use const E_ALL;
use function error_reporting;

class DebugProgress extends Progress
{
    public function setErrorReporting(): void
    {
        error_reporting(E_ALL);
    }

    public function debug(string $message): void
    {
        $this->write($message);
    }

    public function startScanningFiles(): void
    {
        $this->write('Scanning files...' . "\n");
    }

    public function startAnalyzingFiles(): void
    {
        $this->write('Analyzing files...' . "\n");
    }

    public function startAlteringFiles(): void
    {
        $this->write('Updating files...' . "\n");
    }

    public function alterFileDone(string $file_name) : void
    {
        $this->write('Altered ' . $file_name . "\n");
    }
}
