<?php

namespace Doctrine\DBAL\SQL\Builder;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Sequence;
use Doctrine\DBAL\Schema\Table;

use function array_merge;

final class DropSchemaObjectsSQLBuilder
{
    private AbstractPlatform $platform;

    public function __construct(AbstractPlatform $platform)
    {
        $this->platform = $platform;
    }

    /**
     * @return list<string>
     *
     * @throws Exception
     */
    public function buildSQL(Schema $schema): array
    {
        return array_merge(
            $this->buildSequenceStatements($schema->getSequences()),
            $this->buildTableStatements($schema->getTables()),
        );
    }

    /**
     * @param list<Table> $tables
     *
     * @return list<string>
     */
    private function buildTableStatements(array $tables): array
    {
        return $this->platform->getDropTablesSQL($tables);
    }

    /**
     * @param list<Sequence> $sequences
     *
     * @return list<string>
     *
     * @throws Exception
     */
    private function buildSequenceStatements(array $sequences): array
    {
        $statements = [];

        foreach ($sequences as $sequence) {
            $statements[] = $this->platform->getDropSequenceSQL($sequence);
        }

        return $statements;
    }
}
