<?php
namespace Psalm\Plugin\Hook;

use Psalm\Codebase;
use Psalm\Context;
use Psalm\StatementsSource;
use Psalm\Storage\FileStorage;

interface BeforeFileAnalysisInterface
{
    /**
     * Called before a file has been checked
     */
    public static function beforeAnalyzeFile(
        StatementsSource $statements_source,
        Context $file_context,
        FileStorage $file_storage,
        Codebase $codebase
    ): void;
}
