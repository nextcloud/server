<?php
namespace Psalm\Internal\Analyzer;

use PhpParser;
use Psalm\Aliases;

/**
 * @internal
 */
class TraitAnalyzer extends ClassLikeAnalyzer
{
    /**
     * @var Aliases
     */
    private $aliases;

    public function __construct(
        PhpParser\Node\Stmt\Trait_ $class,
        SourceAnalyzer $source,
        string $fq_class_name,
        Aliases $aliases
    ) {
        $this->source = $source;
        $this->file_analyzer = $source->getFileAnalyzer();
        $this->aliases = $source->getAliases();
        $this->class = $class;
        $this->fq_class_name = $fq_class_name;
        $codebase = $source->getCodebase();
        $this->storage = $codebase->classlike_storage_provider->get($fq_class_name);
        $this->aliases = $aliases;
    }

    public function getNamespace(): ?string
    {
        return $this->aliases->namespace;
    }

    public function getAliases(): Aliases
    {
        return $this->aliases;
    }

    /**
     * @return array<string, string>
     */
    public function getAliasedClassesFlipped(): array
    {
        return [];
    }

    /**
     * @return array<string, string>
     */
    public function getAliasedClassesFlippedReplaceable(): array
    {
        return [];
    }
}
