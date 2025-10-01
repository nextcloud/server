<?php

namespace Doctrine\DBAL\Tools\Console\Command;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Tools\Console\ConnectionProvider;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use function array_keys;
use function assert;
use function is_bool;
use function is_string;
use function sprintf;
use function stripos;

/**
 * Task for executing arbitrary SQL that can come from a file or directly from
 * the command line.
 */
class RunSqlCommand extends Command
{
    use CommandCompatibility;

    private ConnectionProvider $connectionProvider;

    public function __construct(ConnectionProvider $connectionProvider)
    {
        parent::__construct();

        $this->connectionProvider = $connectionProvider;
    }

    private function doConfigure(): void
    {
        $this
        ->setName('dbal:run-sql')
        ->setDescription('Executes arbitrary SQL directly from the command line.')
        ->setDefinition([
            new InputOption('connection', null, InputOption::VALUE_REQUIRED, 'The named database connection'),
            new InputArgument('sql', InputArgument::REQUIRED, 'The SQL statement to execute.'),
            new InputOption('depth', null, InputOption::VALUE_REQUIRED, 'Dumping depth of result set (deprecated).'),
            new InputOption('force-fetch', null, InputOption::VALUE_NONE, 'Forces fetching the result.'),
        ])
        ->setHelp(<<<'EOT'
The <info>%command.name%</info> command executes the given SQL query and
outputs the results:

<info>php %command.full_name% "SELECT * FROM users"</info>
EOT);
    }

    /** @throws Exception */
    private function doExecute(InputInterface $input, OutputInterface $output): int
    {
        $conn = $this->getConnection($input);
        $io   = new SymfonyStyle($input, $output);

        $sql = $input->getArgument('sql');

        if ($sql === null) {
            throw new RuntimeException("Argument 'SQL' is required in order to execute this command correctly.");
        }

        assert(is_string($sql));

        if ($input->getOption('depth') !== null) {
            $io->warning('Parameter "depth" is deprecated and has no effect anymore.');
        }

        $forceFetch = $input->getOption('force-fetch');
        assert(is_bool($forceFetch));

        if (stripos($sql, 'select') === 0 || $forceFetch) {
            $this->runQuery($io, $conn, $sql);
        } else {
            $this->runStatement($io, $conn, $sql);
        }

        return 0;
    }

    private function getConnection(InputInterface $input): Connection
    {
        $connectionName = $input->getOption('connection');
        assert(is_string($connectionName) || $connectionName === null);

        if ($connectionName !== null) {
            return $this->connectionProvider->getConnection($connectionName);
        }

        return $this->connectionProvider->getDefaultConnection();
    }

    /** @throws Exception */
    private function runQuery(SymfonyStyle $io, Connection $conn, string $sql): void
    {
        $resultSet = $conn->fetchAllAssociative($sql);
        if ($resultSet === []) {
            $io->success('The query yielded an empty result set.');

            return;
        }

        $io->table(array_keys($resultSet[0]), $resultSet);
    }

    /** @throws Exception */
    private function runStatement(SymfonyStyle $io, Connection $conn, string $sql): void
    {
        $io->success(sprintf('%d rows affected.', $conn->executeStatement($sql)));
    }
}
