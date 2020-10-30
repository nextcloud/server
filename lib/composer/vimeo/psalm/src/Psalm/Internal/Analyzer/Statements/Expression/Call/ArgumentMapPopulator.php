<?php

namespace Psalm\Internal\Analyzer\Statements\Expression\Call;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\StaticCall;
use Psalm\Codebase;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use function substr;
use function token_get_all;
use function array_reverse;
use function is_string;
use function strlen;
use function array_shift;
use function reset;

class ArgumentMapPopulator
{
    /**
     * @param MethodCall|StaticCall|FuncCall|New_ $stmt
     */
    public static function recordArgumentPositions(
        StatementsAnalyzer $statements_analyzer,
        Expr $stmt,
        Codebase $codebase,
        string $function_reference
    ): void {
        $file_content = $codebase->file_provider->getContents($statements_analyzer->getFilePath());

        // Find opening paren
        $first_argument = $stmt->args[0] ?? null;
        $first_argument_character = $first_argument !== null
            ? $first_argument->getStartFilePos()
            : $stmt->getEndFilePos();
        $method_name_and_first_paren_source_code_length = $first_argument_character - $stmt->getStartFilePos();
        // FIXME: There are weird ::__construct calls in the AST for `extends`
        if ($method_name_and_first_paren_source_code_length <= 0) {
            return;
        }
        $method_name_and_first_paren_source_code = substr(
            $file_content,
            $stmt->getStartFilePos(),
            $method_name_and_first_paren_source_code_length
        );
        $method_name_and_first_paren_tokens = token_get_all('<?php ' . $method_name_and_first_paren_source_code);
        $opening_paren_position = $first_argument_character;
        foreach (array_reverse($method_name_and_first_paren_tokens) as $token) {
            $token = is_string($token) ? $token : $token[1];
            $opening_paren_position -= strlen($token);

            if ($token === '(') {
                break;
            }
        }

        // New instances can be created without parens
        if ($opening_paren_position < $stmt->getStartFilePos()) {
            return;
        }

        // Record ranges of the source code that need to be tokenized to find commas
        /** @var array{0: int, 1: int}[] $ranges */
        $ranges = [];

        // Add range between opening paren and first argument
        $first_argument = $stmt->args[0] ?? null;
        $first_argument_starting_position = $first_argument !== null
            ? $first_argument->getStartFilePos()
            : $stmt->getEndFilePos();
        $first_range_starting_position = $opening_paren_position + 1;
        if ($first_range_starting_position !== $first_argument_starting_position) {
            $ranges[] = [$first_range_starting_position, $first_argument_starting_position];
        }

        // Add range between arguments
        foreach ($stmt->args as $i => $argument) {
            $range_start = $argument->getEndFilePos() + 1;
            $next_argument = $stmt->args[$i + 1] ?? null;
            $range_end = $next_argument !== null
                ? $next_argument->getStartFilePos()
                : $stmt->getEndFilePos();

            if ($range_start !== $range_end) {
                $ranges[] = [$range_start, $range_end];
            }
        }

        $commas = [];
        foreach ($ranges as $range) {
            $position = $range[0];
            $length = $range[1] - $position;

            if ($length > 0) {
                $range_source_code = substr($file_content, $position, $length);
                $range_tokens = token_get_all('<?php ' . $range_source_code);

                array_shift($range_tokens);

                $current_position = $position;
                foreach ($range_tokens as $token) {
                    $token = is_string($token) ? $token : $token[1];

                    if ($token === ',') {
                        $commas[] = $current_position;
                    }

                    $current_position += strlen($token);
                }
            }
        }

        $argument_start_position = $opening_paren_position + 1;
        $argument_number = 0;
        while (!empty($commas)) {
            $comma = reset($commas);
            array_shift($commas);

            $codebase->analyzer->addNodeArgument(
                $statements_analyzer->getFilePath(),
                $argument_start_position,
                $comma,
                $function_reference,
                $argument_number
            );

            ++$argument_number;
            $argument_start_position = $comma + 1;
        }

        $codebase->analyzer->addNodeArgument(
            $statements_analyzer->getFilePath(),
            $argument_start_position,
            $stmt->getEndFilePos(),
            $function_reference,
            $argument_number
        );
    }
}
