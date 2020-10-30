<?php
namespace Psalm\Plugin\Hook;

use Psalm\Codebase;
use Psalm\CodeLocation;
use Psalm\FileManipulation;
use Psalm\StatementsSource;

interface AfterClassLikeExistenceCheckInterface
{
    /**
     * @param  FileManipulation[] $file_replacements
     */
    public static function afterClassLikeExistenceCheck(
        string $fq_class_name,
        CodeLocation $code_location,
        StatementsSource $statements_source,
        Codebase $codebase,
        array &$file_replacements = []
    ): void;
}
