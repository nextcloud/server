<?php

namespace Doctrine\DBAL\Platforms\Keywords;

use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Sequence;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Schema\Visitor\Visitor;
use Doctrine\Deprecations\Deprecation;

use function count;
use function implode;
use function str_replace;

/** @deprecated Use database documentation instead. */
class ReservedKeywordsValidator implements Visitor
{
    /** @var KeywordList[] */
    private array $keywordLists;

    /** @var string[] */
    private array $violations = [];

    /** @param KeywordList[] $keywordLists */
    public function __construct(array $keywordLists)
    {
        Deprecation::triggerIfCalledFromOutside(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/pull/5431',
            'ReservedKeywordsValidator is deprecated. Use database documentation instead.',
        );

        $this->keywordLists = $keywordLists;
    }

    /** @return string[] */
    public function getViolations()
    {
        return $this->violations;
    }

    /**
     * @param string $word
     *
     * @return string[]
     */
    private function isReservedWord($word): array
    {
        if ($word[0] === '`') {
            $word = str_replace('`', '', $word);
        }

        $keywordLists = [];
        foreach ($this->keywordLists as $keywordList) {
            if (! $keywordList->isKeyword($word)) {
                continue;
            }

            $keywordLists[] = $keywordList->getName();
        }

        return $keywordLists;
    }

    /**
     * @param string   $asset
     * @param string[] $violatedPlatforms
     */
    private function addViolation($asset, $violatedPlatforms): void
    {
        if (count($violatedPlatforms) === 0) {
            return;
        }

        $this->violations[] = $asset . ' keyword violations: ' . implode(', ', $violatedPlatforms);
    }

    /**
     * {@inheritDoc}
     */
    public function acceptColumn(Table $table, Column $column)
    {
        $this->addViolation(
            'Table ' . $table->getName() . ' column ' . $column->getName(),
            $this->isReservedWord($column->getName()),
        );
    }

    /**
     * {@inheritDoc}
     */
    public function acceptForeignKey(Table $localTable, ForeignKeyConstraint $fkConstraint)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function acceptIndex(Table $table, Index $index)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function acceptSchema(Schema $schema)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function acceptSequence(Sequence $sequence)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function acceptTable(Table $table)
    {
        $this->addViolation(
            'Table ' . $table->getName(),
            $this->isReservedWord($table->getName()),
        );
    }
}
