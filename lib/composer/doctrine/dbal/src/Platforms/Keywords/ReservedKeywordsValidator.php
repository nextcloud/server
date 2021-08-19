<?php

namespace Doctrine\DBAL\Platforms\Keywords;

use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Sequence;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Schema\Visitor\Visitor;

use function count;
use function implode;
use function str_replace;

class ReservedKeywordsValidator implements Visitor
{
    /** @var KeywordList[] */
    private $keywordLists = [];

    /** @var string[] */
    private $violations = [];

    /**
     * @param KeywordList[] $keywordLists
     */
    public function __construct(array $keywordLists)
    {
        $this->keywordLists = $keywordLists;
    }

    /**
     * @return string[]
     */
    public function getViolations()
    {
        return $this->violations;
    }

    /**
     * @param string $word
     *
     * @return string[]
     */
    private function isReservedWord($word)
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
     *
     * @return void
     */
    private function addViolation($asset, $violatedPlatforms)
    {
        if (count($violatedPlatforms) === 0) {
            return;
        }

        $this->violations[] = $asset . ' keyword violations: ' . implode(', ', $violatedPlatforms);
    }

    /**
     * {@inheritdoc}
     */
    public function acceptColumn(Table $table, Column $column)
    {
        $this->addViolation(
            'Table ' . $table->getName() . ' column ' . $column->getName(),
            $this->isReservedWord($column->getName())
        );
    }

    /**
     * {@inheritdoc}
     */
    public function acceptForeignKey(Table $localTable, ForeignKeyConstraint $fkConstraint)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function acceptIndex(Table $table, Index $index)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function acceptSchema(Schema $schema)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function acceptSequence(Sequence $sequence)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function acceptTable(Table $table)
    {
        $this->addViolation(
            'Table ' . $table->getName(),
            $this->isReservedWord($table->getName())
        );
    }
}
