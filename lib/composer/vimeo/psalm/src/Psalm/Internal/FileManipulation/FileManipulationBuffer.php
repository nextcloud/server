<?php
namespace Psalm\Internal\FileManipulation;

use function array_merge;
use Psalm\CodeLocation;
use Psalm\FileManipulation;
use Psalm\Internal\Provider\FileProvider;
use function preg_match;
use function strlen;
use function strrpos;
use function substr;
use function substr_replace;

/**
 * @internal
 */
class FileManipulationBuffer
{
    /** @var array<string, FileManipulation[]> */
    private static $file_manipulations = [];

    /** @var CodeMigration[] */
    private static $code_migrations = [];

    /**
     * @param FileManipulation[] $file_manipulations
     *
     */
    public static function add(string $file_path, array $file_manipulations): void
    {
        if (!isset(self::$file_manipulations[$file_path])) {
            self::$file_manipulations[$file_path] = [];
        }

        foreach ($file_manipulations as $file_manipulation) {
            self::$file_manipulations[$file_path][$file_manipulation->getKey()] = $file_manipulation;
        }
    }

    /** @param CodeMigration[] $code_migrations */
    public static function addCodeMigrations(array $code_migrations) : void
    {
        self::$code_migrations = array_merge(self::$code_migrations, $code_migrations);
    }

    /**
     * @return array{int, int}
     */
    private static function getCodeOffsets(
        string $source_file_path,
        int $source_start,
        int $source_end
    ) : array {
        if (!isset(self::$file_manipulations[$source_file_path])) {
            return [0, 0];
        }

        $start_offset = 0;
        $middle_offset = 0;

        foreach (self::$file_manipulations[$source_file_path] as $fm) {
            $offset = strlen($fm->insertion_text) - $fm->end + $fm->start;

            if ($fm->end < $source_start) {
                $start_offset += $offset;
                $middle_offset += $offset;
            } elseif ($fm->start > $source_start
                && $fm->end < $source_end
            ) {
                $middle_offset += $offset;
            }
        }

        return [$start_offset, $middle_offset];
    }

    public static function addForCodeLocation(
        CodeLocation $code_location,
        string $replacement_text,
        bool $swallow_newlines = false
    ): void {
        $bounds = $code_location->getSnippetBounds();

        if ($swallow_newlines) {
            $project_analyzer = \Psalm\Internal\Analyzer\ProjectAnalyzer::getInstance();

            $codebase = $project_analyzer->getCodebase();

            $file_contents = $codebase->getFileContents($code_location->file_path);

            if (($file_contents[$bounds[0] - 1] ?? null) === "\n"
                && ($file_contents[$bounds[0] - 2] ?? null) === "\n"
            ) {
                $bounds[0] -= 2;
            }
        }

        self::add(
            $code_location->file_path,
            [
                new FileManipulation(
                    $bounds[0],
                    $bounds[1],
                    $replacement_text
                ),
            ]
        );
    }

    public static function addVarAnnotationToRemove(CodeLocation\DocblockTypeLocation $code_location): void
    {
        $bounds = $code_location->getSelectionBounds();

        $project_analyzer = \Psalm\Internal\Analyzer\ProjectAnalyzer::getInstance();

        $codebase = $project_analyzer->getCodebase();

        $file_contents = $codebase->getFileContents($code_location->file_path);

        $comment_start = strrpos($file_contents, '/**', $bounds[0] - strlen($file_contents));

        if ($comment_start === false) {
            return;
        }

        $comment_end = \strpos($file_contents, '*/', $bounds[1]);

        if ($comment_end === false) {
            return;
        }

        $comment_end += 2;

        $comment_text = substr($file_contents, $comment_start, $comment_end - $comment_start);

        $var_type_comment_start = $bounds[0] - $comment_start;
        $var_type_comment_end = $bounds[1] - $comment_start;

        $var_start = strrpos($comment_text, '@var', $var_type_comment_start - strlen($comment_text));
        $var_end = \strpos($comment_text, "\n", $var_type_comment_end);

        if ($var_start && $var_end) {
            $var_start = strrpos($comment_text, "\n", $var_start - strlen($comment_text)) ?: $var_start;
            $comment_text = substr_replace($comment_text, '', $var_start, $var_end - $var_start);
            if (preg_match('@^/\*\*\n(\s*\*\s*\n)*\s*\*?\*/$@', $comment_text)) {
                $comment_text = '';
            }
        } else {
            $comment_text = '';
        }

        self::add(
            $code_location->file_path,
            [
                new FileManipulation(
                    $comment_start,
                    $comment_end,
                    $comment_text,
                    false,
                    $comment_text === ''
                ),
            ]
        );
    }

    /**
     * @return FileManipulation[]
     */
    public static function getManipulationsForFile(string $file_path): array
    {
        if (!isset(self::$file_manipulations[$file_path])) {
            return [];
        }

        return self::$file_manipulations[$file_path];
    }

    /**
     * @param string $file_path
     *
     * @return array<string, FileManipulation[]>
     */
    public static function getMigrationManipulations(FileProvider $file_provider): array
    {
        $code_migration_manipulations = [];

        foreach (self::$code_migrations as $code_migration) {
            [$start_offset, $middle_offset] = self::getCodeOffsets(
                $code_migration->source_file_path,
                $code_migration->source_start,
                $code_migration->source_end
            );

            if (!isset($code_migration_manipulations[$code_migration->source_file_path])) {
                $code_migration_manipulations[$code_migration->source_file_path] = [];
            }

            if (!isset($code_migration_manipulations[$code_migration->destination_file_path])) {
                $code_migration_manipulations[$code_migration->destination_file_path] = [];
            }

            $delete_file_manipulation = new FileManipulation(
                $code_migration->source_start + $start_offset,
                $code_migration->source_end + $middle_offset,
                ''
            );

            $code_migration_manipulations[$code_migration->source_file_path][] = $delete_file_manipulation;

            [$destination_start_offset] = self::getCodeOffsets(
                $code_migration->destination_file_path,
                $code_migration->destination_start,
                $code_migration->destination_start
            );

            $manipulation = new FileManipulation(
                $code_migration->destination_start + $destination_start_offset,
                $code_migration->destination_start + $destination_start_offset,
                "\n" . substr(
                    $file_provider->getContents($code_migration->source_file_path),
                    $delete_file_manipulation->start,
                    $delete_file_manipulation->end - $delete_file_manipulation->start
                ) . "\n"
            );

            $code_migration_manipulations[$code_migration->destination_file_path][$manipulation->getKey()]
                = $manipulation;
        }

        return $code_migration_manipulations;
    }

    /**
     * @return array<string, FileManipulation[]>
     */
    public static function getAll(): array
    {
        return self::$file_manipulations;
    }

    public static function clearCache(): void
    {
        self::$file_manipulations = [];
        self::$code_migrations = [];
    }
}
