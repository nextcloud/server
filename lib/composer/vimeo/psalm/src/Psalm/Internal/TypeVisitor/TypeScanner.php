<?php
namespace Psalm\Internal\TypeVisitor;

use Psalm\Internal\Codebase\Scanner;
use Psalm\Storage\FileStorage;
use Psalm\Type\Atomic\TLiteralClassString;
use Psalm\Type\Atomic\TScalarClassConstant;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\TypeNode;
use Psalm\Type\NodeVisitor;
use function strtolower;

class TypeScanner extends NodeVisitor
{
    private $scanner;

    private $file_storage;

    private $phantom_classes;

    /**
     * @param  array<string, mixed> $phantom_classes
     */
    public function __construct(
        Scanner $scanner,
        ?FileStorage $file_storage,
        array $phantom_classes
    ) {
        $this->scanner = $scanner;
        $this->file_storage = $file_storage;
        $this->phantom_classes = $phantom_classes;
    }

    protected function enterNode(TypeNode $type) : ?int
    {
        if ($type instanceof TNamedObject) {
            $fq_classlike_name_lc = strtolower($type->value);

            if (!isset($this->phantom_classes[$type->value])
                && !isset($this->phantom_classes[$fq_classlike_name_lc])
            ) {
                $this->scanner->queueClassLikeForScanning(
                    $type->value,
                    false,
                    !$type->from_docblock,
                    $this->phantom_classes
                );

                if ($this->file_storage) {
                    $this->file_storage->referenced_classlikes[$fq_classlike_name_lc] = $type->value;
                }
            }
        }

        if ($type instanceof TScalarClassConstant) {
            $this->scanner->queueClassLikeForScanning(
                $type->fq_classlike_name,
                false,
                !$type->from_docblock,
                $this->phantom_classes
            );

            if ($this->file_storage) {
                $fq_classlike_name_lc = strtolower($type->fq_classlike_name);

                $this->file_storage->referenced_classlikes[$fq_classlike_name_lc] = $type->fq_classlike_name;
            }
        }

        if ($type instanceof TLiteralClassString) {
            $this->scanner->queueClassLikeForScanning(
                $type->value,
                false,
                !$type->from_docblock,
                $this->phantom_classes
            );

            if ($this->file_storage) {
                $fq_classlike_name_lc = strtolower($type->value);

                $this->file_storage->referenced_classlikes[$fq_classlike_name_lc] = $type->value;
            }
        }

        return null;
    }
}
