<?php
namespace Psalm\Example\Plugin;

use Psalm\Codebase;
use Psalm\CodeLocation;
use Psalm\FileManipulation;
use Psalm\Plugin\Hook\AfterClassLikeExistenceCheckInterface;
use Psalm\StatementsSource;

class ClassUnqualifier implements AfterClassLikeExistenceCheckInterface
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
    ): void {
        $candidate_type = $code_location->getSelectedText();
        $aliases = $statements_source->getAliasedClassesFlipped();

        if ($statements_source->getFilePath() !== $code_location->file_path) {
            return;
        }

        if (strpos($candidate_type, '\\' . $fq_class_name) !== false) {
            $type_tokens = \Psalm\Internal\Type\TypeTokenizer::tokenize($candidate_type, false);

            foreach ($type_tokens as &$type_token) {
                if ($type_token[0] === ('\\' . $fq_class_name)
                    && isset($aliases[strtolower($fq_class_name)])
                ) {
                    $type_token[0] = $aliases[strtolower($fq_class_name)];
                }
            }

            $new_candidate_type = implode(
                '',
                array_map(
                    function ($f) {
                        return $f[0];
                    },
                    $type_tokens
                )
            );

            if ($new_candidate_type !== $candidate_type) {
                $bounds = $code_location->getSelectionBounds();
                $file_replacements[] = new FileManipulation($bounds[0], $bounds[1], $new_candidate_type);
            }
        }
    }
}
