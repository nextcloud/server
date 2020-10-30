<?php
namespace Psalm\Internal\FileManipulation;

use function ltrim;
use PhpParser\Node\Stmt\Class_;
use Psalm\DocComment;
use Psalm\FileManipulation;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use function str_replace;
use function strlen;
use function strrpos;
use function substr;

/**
 * @internal
 */
class ClassDocblockManipulator
{
    /**
     * @var array<string, array<int, self>>
     */
    private static $manipulators = [];

    /** @var Class_ */
    private $stmt;

    /** @var int */
    private $docblock_start;

    /** @var int */
    private $docblock_end;

    /** @var bool */
    private $immutable = false;

    /** @var string */
    private $indentation;

    public static function getForClass(
        ProjectAnalyzer $project_analyzer,
        string $file_path,
        Class_ $stmt
    ) : self {
        if (isset(self::$manipulators[$file_path][$stmt->getLine()])) {
            return self::$manipulators[$file_path][$stmt->getLine()];
        }

        $manipulator
            = self::$manipulators[$file_path][$stmt->getLine()]
            = new self($project_analyzer, $stmt, $file_path);

        return $manipulator;
    }

    private function __construct(
        ProjectAnalyzer $project_analyzer,
        Class_ $stmt,
        string $file_path
    ) {
        $this->stmt = $stmt;
        $docblock = $stmt->getDocComment();
        $this->docblock_start = $docblock ? $docblock->getStartFilePos() : (int)$stmt->getAttribute('startFilePos');
        $this->docblock_end = (int)$stmt->getAttribute('startFilePos');

        $codebase = $project_analyzer->getCodebase();

        $file_contents = $codebase->getFileContents($file_path);

        $preceding_newline_pos = (int) strrpos($file_contents, "\n", $this->docblock_end - strlen($file_contents));

        $first_line = substr($file_contents, $preceding_newline_pos + 1, $this->docblock_end - $preceding_newline_pos);

        $this->indentation = str_replace(ltrim($first_line), '', $first_line);
    }

    public function makeImmutable() : void
    {
        $this->immutable = true;
    }

    /**
     * Gets a new docblock given the existing docblock, if one exists, and the updated return types
     * and/or parameters
     *
     */
    private function getDocblock(): string
    {
        $docblock = $this->stmt->getDocComment();

        if ($docblock) {
            $parsed_docblock = DocComment::parsePreservingLength($docblock);
        } else {
            $parsed_docblock = new \Psalm\Internal\Scanner\ParsedDocblock('', []);
        }

        $modified_docblock = false;

        if ($this->immutable) {
            $modified_docblock = true;
            $parsed_docblock->tags['psalm-immutable'] = [''];
        }

        if (!$modified_docblock) {
            return (string)$docblock . "\n" . $this->indentation;
        }

        return $parsed_docblock->render($this->indentation);
    }

    /**
     * @return array<int, FileManipulation>
     */
    public static function getManipulationsForFile(string $file_path): array
    {
        if (!isset(self::$manipulators[$file_path])) {
            return [];
        }

        $file_manipulations = [];

        foreach (self::$manipulators[$file_path] as $manipulator) {
            if ($manipulator->immutable) {
                $file_manipulations[$manipulator->docblock_start] = new FileManipulation(
                    $manipulator->docblock_start,
                    $manipulator->docblock_end,
                    $manipulator->getDocblock()
                );
            }
        }

        return $file_manipulations;
    }

    public static function clearCache(): void
    {
        self::$manipulators = [];
    }
}
