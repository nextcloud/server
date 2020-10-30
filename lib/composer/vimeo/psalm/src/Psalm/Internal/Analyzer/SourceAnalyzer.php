<?php
namespace Psalm\Internal\Analyzer;

use Psalm\Aliases;
use Psalm\Codebase;
use Psalm\StatementsSource;
use Psalm\Type;

/**
 * @internal
 */
abstract class SourceAnalyzer implements StatementsSource
{
    /**
     * @var SourceAnalyzer
     */
    protected $source;

    public function __destruct()
    {
        $this->source = null;
    }

    public function getAliases(): Aliases
    {
        return $this->source->getAliases();
    }

    /**
     * @return array<string, string>
     */
    public function getAliasedClassesFlipped(): array
    {
        return $this->source->getAliasedClassesFlipped();
    }

    /**
     * @return array<string, string>
     */
    public function getAliasedClassesFlippedReplaceable(): array
    {
        return $this->source->getAliasedClassesFlippedReplaceable();
    }

    public function getFQCLN(): ?string
    {
        return $this->source->getFQCLN();
    }

    public function getClassName(): ?string
    {
        return $this->source->getClassName();
    }

    public function getParentFQCLN(): ?string
    {
        return $this->source->getParentFQCLN();
    }

    public function getFileName(): string
    {
        return $this->source->getFileName();
    }

    public function getFilePath(): string
    {
        return $this->source->getFilePath();
    }

    public function getRootFileName(): string
    {
        return $this->source->getRootFileName();
    }

    public function getRootFilePath(): string
    {
        return $this->source->getRootFilePath();
    }

    public function setRootFilePath(string $file_path, string $file_name): void
    {
        $this->source->setRootFilePath($file_path, $file_name);
    }

    public function hasParentFilePath(string $file_path): bool
    {
        return $this->source->hasParentFilePath($file_path);
    }

    public function hasAlreadyRequiredFilePath(string $file_path): bool
    {
        return $this->source->hasAlreadyRequiredFilePath($file_path);
    }

    public function getRequireNesting(): int
    {
        return $this->source->getRequireNesting();
    }

    /**
     * @psalm-mutation-free
     */
    public function getSource(): StatementsSource
    {
        return $this->source;
    }

    /**
     * Get a list of suppressed issues
     *
     * @return array<string>
     */
    public function getSuppressedIssues(): array
    {
        return $this->source->getSuppressedIssues();
    }

    /**
     * @param array<int, string> $new_issues
     */
    public function addSuppressedIssues(array $new_issues): void
    {
        $this->source->addSuppressedIssues($new_issues);
    }

    /**
     * @param array<int, string> $new_issues
     */
    public function removeSuppressedIssues(array $new_issues): void
    {
        $this->source->removeSuppressedIssues($new_issues);
    }

    public function getNamespace(): ?string
    {
        return $this->source->getNamespace();
    }

    public function isStatic(): bool
    {
        return $this->source->isStatic();
    }

    /**
     * @psalm-mutation-free
     */
    public function getCodebase() : Codebase
    {
        return $this->source->getCodebase();
    }

    /**
     * @psalm-mutation-free
     */
    public function getProjectAnalyzer() : ProjectAnalyzer
    {
        return $this->source->getProjectAnalyzer();
    }

    /**
     * @psalm-mutation-free
     */
    public function getFileAnalyzer() : FileAnalyzer
    {
        return $this->source->getFileAnalyzer();
    }

    /**
     * @return array<string, array<string, array{Type\Union}>>|null
     */
    public function getTemplateTypeMap(): ?array
    {
        return $this->source->getTemplateTypeMap();
    }

    public function getNodeTypeProvider() : \Psalm\NodeTypeProvider
    {
        return $this->source->getNodeTypeProvider();
    }
}
