<?php
namespace Psalm\Internal\FileManipulation;

use PhpParser;
use function count;
use function ltrim;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\FunctionLike;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use function preg_match;
use Psalm\DocComment;
use Psalm\FileManipulation;
use Psalm\Internal\Analyzer\CommentAnalyzer;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use function str_replace;
use function str_split;
use function strlen;
use function strpos;
use function strrpos;
use function substr;
use function reset;
use function array_merge;

/**
 * @internal
 */
class FunctionDocblockManipulator
{
    /**
     * Manipulators ordered by line number
     *
     * @var array<string, array<int, FunctionDocblockManipulator>>
     */
    private static $manipulators = [];

    /** @var Closure|Function_|ClassMethod|ArrowFunction */
    private $stmt;

    /** @var int */
    private $docblock_start;

    /** @var int */
    private $docblock_end;

    /** @var int */
    private $return_typehint_area_start;

    /** @var null|int */
    private $return_typehint_colon_start;

    /** @var null|int */
    private $return_typehint_start;

    /** @var null|int */
    private $return_typehint_end;

    /** @var null|string */
    private $new_php_return_type;

    /** @var bool */
    private $return_type_is_php_compatible = false;

    /** @var null|string */
    private $new_phpdoc_return_type;

    /** @var null|string */
    private $new_psalm_return_type;

    /** @var array<string, string> */
    private $new_php_param_types = [];

    /** @var array<string, string> */
    private $new_phpdoc_param_types = [];

    /** @var array<string, string> */
    private $new_psalm_param_types = [];

    /** @var string */
    private $indentation;

    /** @var string|null */
    private $return_type_description;

    /** @var array<string, int> */
    private $param_offsets = [];

    /** @var array<string, array{int, int}> */
    private $param_typehint_offsets = [];

    /** @var bool */
    private $is_pure = false;

    /**
     * @param  Closure|Function_|ClassMethod|ArrowFunction $stmt
     */
    public static function getForFunction(
        ProjectAnalyzer $project_analyzer,
        string $file_path,
        FunctionLike $stmt
    ): FunctionDocblockManipulator {
        if (isset(self::$manipulators[$file_path][$stmt->getLine()])) {
            return self::$manipulators[$file_path][$stmt->getLine()];
        }

        $manipulator
            = self::$manipulators[$file_path][$stmt->getLine()]
            = new self($file_path, $stmt, $project_analyzer);

        return $manipulator;
    }

    /**
     * @param Closure|Function_|ClassMethod|ArrowFunction $stmt
     */
    private function __construct(string $file_path, FunctionLike $stmt, ProjectAnalyzer $project_analyzer)
    {
        $this->stmt = $stmt;
        $docblock = $stmt->getDocComment();
        $this->docblock_start = $docblock ? $docblock->getStartFilePos() : (int)$stmt->getAttribute('startFilePos');
        $this->docblock_end = $function_start = (int)$stmt->getAttribute('startFilePos');
        $function_end = (int)$stmt->getAttribute('endFilePos');

        foreach ($stmt->params as $param) {
            if ($param->var instanceof PhpParser\Node\Expr\Variable
                && \is_string($param->var->name)
            ) {
                $this->param_offsets[$param->var->name] = (int) $param->getAttribute('startFilePos');

                if ($param->type) {
                    $this->param_typehint_offsets[$param->var->name] = [
                        (int) $param->type->getAttribute('startFilePos'),
                        (int) $param->type->getAttribute('endFilePos')
                    ];
                }
            }
        }

        $codebase = $project_analyzer->getCodebase();

        $file_contents = $codebase->getFileContents($file_path);

        $last_arg_position = $stmt->params
            ? (int) $stmt->params[count($stmt->params) - 1]->getAttribute('endFilePos') + 1
            : null;

        if ($stmt instanceof Closure && $stmt->uses) {
            $last_arg_position = (int) $stmt->uses[count($stmt->uses) - 1]->getAttribute('endFilePos') + 1;
        }

        $end_bracket_position = (int) strpos($file_contents, ')', $last_arg_position ?: $function_start);

        $this->return_typehint_area_start = $end_bracket_position + 1;

        $function_code = substr($file_contents, $function_start, $function_end);

        $function_code_after_bracket = substr($function_code, $end_bracket_position + 1 - $function_start);

        // do a little parsing here
        $chars = str_split($function_code_after_bracket);

        $in_single_line_comment = $in_multi_line_comment = false;

        for ($i = 0, $iMax = count($chars); $i < $iMax; ++$i) {
            $char = $chars[$i];

            switch ($char) {
                case "\n":
                    $in_single_line_comment = false;
                    continue 2;

                case ':':
                    if ($in_multi_line_comment || $in_single_line_comment) {
                        continue 2;
                    }

                    $this->return_typehint_colon_start = $i + $end_bracket_position + 1;

                    continue 2;

                case '/':
                    if ($in_multi_line_comment || $in_single_line_comment) {
                        continue 2;
                    }

                    if ($chars[$i + 1] === '*') {
                        $in_multi_line_comment = true;
                        ++$i;
                    }

                    if ($chars[$i + 1] === '/') {
                        $in_single_line_comment = true;
                        ++$i;
                    }

                    continue 2;

                case '*':
                    if ($in_single_line_comment) {
                        continue 2;
                    }

                    if ($chars[$i + 1] === '/') {
                        $in_multi_line_comment = false;
                        ++$i;
                    }

                    continue 2;

                case '{':
                    if ($in_multi_line_comment || $in_single_line_comment) {
                        continue 2;
                    }

                    break 2;

                case '?':
                    if ($in_multi_line_comment || $in_single_line_comment) {
                        continue 2;
                    }

                    $this->return_typehint_start = $i + $end_bracket_position + 1;
                    break;
            }

            if ($in_multi_line_comment || $in_single_line_comment) {
                continue;
            }

            if ($chars[$i] === '\\' || preg_match('/\w/', $char)) {
                if ($this->return_typehint_start === null) {
                    $this->return_typehint_start = $i + $end_bracket_position + 1;
                }

                if ($chars[$i + 1] !== '\\' && !preg_match('/[\w]/', $chars[$i + 1])) {
                    $this->return_typehint_end = $i + $end_bracket_position + 2;
                    break;
                }
            }
        }

        $preceding_newline_pos = strrpos($file_contents, "\n", $this->docblock_end - strlen($file_contents));

        if ($preceding_newline_pos === false) {
            $this->indentation = '';

            return;
        }

        $first_line = substr($file_contents, $preceding_newline_pos + 1, $this->docblock_end - $preceding_newline_pos);

        $this->indentation = str_replace(ltrim($first_line), '', $first_line);
    }

    /**
     * Sets the new return type
     *
     */
    public function setReturnType(
        ?string $php_type,
        string $new_type,
        string $phpdoc_type,
        bool $is_php_compatible,
        ?string $description
    ): void {
        $new_type = str_replace(['<mixed, mixed>', '<array-key, mixed>'], '', $new_type);

        $this->new_php_return_type = $php_type;
        $this->new_phpdoc_return_type = $phpdoc_type;
        $this->new_psalm_return_type = $new_type;
        $this->return_type_is_php_compatible = $is_php_compatible;
        $this->return_type_description = $description;
    }

    /**
     * Sets a new param type
     *
     * @param   bool        $is_php_compatible
     *
     */
    public function setParamType(
        string $param_name,
        ?string $php_type,
        string $new_type,
        string $phpdoc_type
    ): void {
        $new_type = str_replace(['<mixed, mixed>', '<array-key, mixed>', '<empty, empty>'], '', $new_type);

        if ($php_type) {
            $this->new_php_param_types[$param_name] = $php_type;
        }

        if ($php_type !== $new_type) {
            $this->new_phpdoc_param_types[$param_name] = $phpdoc_type;
            $this->new_psalm_param_types[$param_name] = $new_type;
        }
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

        foreach ($this->new_phpdoc_param_types as $param_name => $phpdoc_type) {
            $found_in_params = false;
            $new_param_block = $phpdoc_type . ' ' . '$' . $param_name;

            if (isset($parsed_docblock->tags['param'])) {
                foreach ($parsed_docblock->tags['param'] as &$param_block) {
                    $doc_parts = CommentAnalyzer::splitDocLine($param_block);

                    if (($doc_parts[1] ?? null) === '$' . $param_name) {
                        if ($param_block !== $new_param_block) {
                            $modified_docblock = true;
                        }

                        $param_block = $new_param_block;
                        $found_in_params = true;
                        break;
                    }
                }
            }

            if (!$found_in_params) {
                $modified_docblock = true;
                $parsed_docblock->tags['param'][] = $new_param_block;
            }
        }

        $old_phpdoc_return_type = null;
        if (isset($parsed_docblock->tags['return'])) {
            $old_phpdoc_return_type = reset($parsed_docblock->tags['return']);
        }

        if ($this->is_pure) {
            $modified_docblock = true;
            $parsed_docblock->tags['psalm-pure'] = [''];
        }

        if ($this->new_phpdoc_return_type
            && $this->new_phpdoc_return_type !== $old_phpdoc_return_type
        ) {
            $modified_docblock = true;
            $parsed_docblock->tags['return'] = [
                $this->new_phpdoc_return_type
                    . ($this->return_type_description ? (' ' . $this->return_type_description) : ''),
            ];
        }

        $old_psalm_return_type = null;
        if (isset($parsed_docblock->tags['psalm-return'])) {
            $old_psalm_return_type = reset($parsed_docblock->tags['psalm-return']);
        }

        if ($this->new_psalm_return_type
            && $this->new_phpdoc_return_type !== $this->new_psalm_return_type
            && $this->new_psalm_return_type !== $old_psalm_return_type
        ) {
            $modified_docblock = true;
            $parsed_docblock->tags['psalm-return'] = [$this->new_psalm_return_type];
        }

        if (!$parsed_docblock->tags && !$parsed_docblock->description) {
            return '';
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
            if ($manipulator->new_php_return_type) {
                if ($manipulator->return_typehint_start && $manipulator->return_typehint_end) {
                    $file_manipulations[$manipulator->return_typehint_start] = new FileManipulation(
                        $manipulator->return_typehint_start,
                        $manipulator->return_typehint_end,
                        $manipulator->new_php_return_type
                    );
                } else {
                    $file_manipulations[$manipulator->return_typehint_area_start] = new FileManipulation(
                        $manipulator->return_typehint_area_start,
                        $manipulator->return_typehint_area_start,
                        ': ' . $manipulator->new_php_return_type
                    );
                }
            } elseif ($manipulator->new_php_return_type === ''
                && $manipulator->return_typehint_colon_start
                && $manipulator->new_phpdoc_return_type
                && $manipulator->return_typehint_start
                && $manipulator->return_typehint_end
            ) {
                $file_manipulations[$manipulator->return_typehint_start] = new FileManipulation(
                    $manipulator->return_typehint_colon_start,
                    $manipulator->return_typehint_end,
                    ''
                );
            }

            if (!$manipulator->new_php_return_type
                || !$manipulator->return_type_is_php_compatible
                || $manipulator->docblock_start !== $manipulator->docblock_end
                || $manipulator->is_pure
            ) {
                $file_manipulations[$manipulator->docblock_start] = new FileManipulation(
                    $manipulator->docblock_start,
                    $manipulator->docblock_end,
                    $manipulator->getDocblock()
                );
            }

            foreach ($manipulator->new_php_param_types as $param_name => $new_php_param_type) {
                if (!isset($manipulator->param_offsets[$param_name])) {
                    continue;
                }

                $param_offset = $manipulator->param_offsets[$param_name];

                $typehint_offsets = $manipulator->param_typehint_offsets[$param_name] ?? null;

                if ($new_php_param_type) {
                    if ($typehint_offsets) {
                        $file_manipulations[$typehint_offsets[0]] = new FileManipulation(
                            $typehint_offsets[0],
                            $typehint_offsets[1],
                            $new_php_param_type
                        );
                    } else {
                        $file_manipulations[$param_offset] = new FileManipulation(
                            $param_offset,
                            $param_offset,
                            $new_php_param_type . ' '
                        );
                    }
                } elseif ($new_php_param_type === ''
                    && $typehint_offsets
                ) {
                    $file_manipulations[$typehint_offsets[0]] = new FileManipulation(
                        $typehint_offsets[0],
                        $param_offset,
                        ''
                    );
                }
            }
        }

        return $file_manipulations;
    }

    public function makePure() : void
    {
        $this->is_pure = true;
    }

    public static function clearCache(): void
    {
        self::$manipulators = [];
    }

    /**
     * @param array<string, array<int, FunctionDocblockManipulator>> $manipulators
     */
    public static function addManipulators(array $manipulators) : void
    {
        self::$manipulators = array_merge($manipulators, self::$manipulators);
    }

    /**
     * @return array<string, array<int, FunctionDocblockManipulator>>
     */
    public static function getManipulators(): array
    {
        return self::$manipulators;
    }
}
