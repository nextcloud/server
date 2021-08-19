<?php

namespace Doctrine\DBAL\Tools\Console\Command;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Platforms\Keywords\DB2Keywords;
use Doctrine\DBAL\Platforms\Keywords\KeywordList;
use Doctrine\DBAL\Platforms\Keywords\MariaDb102Keywords;
use Doctrine\DBAL\Platforms\Keywords\MySQL57Keywords;
use Doctrine\DBAL\Platforms\Keywords\MySQL80Keywords;
use Doctrine\DBAL\Platforms\Keywords\MySQLKeywords;
use Doctrine\DBAL\Platforms\Keywords\OracleKeywords;
use Doctrine\DBAL\Platforms\Keywords\PostgreSQL100Keywords;
use Doctrine\DBAL\Platforms\Keywords\PostgreSQL94Keywords;
use Doctrine\DBAL\Platforms\Keywords\ReservedKeywordsValidator;
use Doctrine\DBAL\Platforms\Keywords\SQLiteKeywords;
use Doctrine\DBAL\Platforms\Keywords\SQLServer2012Keywords;
use Doctrine\DBAL\Tools\Console\ConnectionProvider;
use InvalidArgumentException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function array_keys;
use function assert;
use function count;
use function implode;
use function is_array;
use function is_string;

class ReservedWordsCommand extends Command
{
    /** @var array<string,class-string<KeywordList>> */
    private $keywordListClasses = [
        'db2'           => DB2Keywords::class,
        'mysql'         => MySQLKeywords::class,
        'mysql57'       => MySQL57Keywords::class,
        'mysql80'       => MySQL80Keywords::class,
        'mariadb102'    => MariaDb102Keywords::class,
        'oracle'        => OracleKeywords::class,
        'pgsql'         => PostgreSQL94Keywords::class,
        'pgsql100'      => PostgreSQL100Keywords::class,
        'sqlite'        => SQLiteKeywords::class,
        'sqlserver'     => SQLServer2012Keywords::class,
    ];

    /** @var ConnectionProvider */
    private $connectionProvider;

    public function __construct(ConnectionProvider $connectionProvider)
    {
        parent::__construct();
        $this->connectionProvider = $connectionProvider;
    }

    /**
     * If you want to add or replace a keywords list use this command.
     *
     * @param string                    $name
     * @param class-string<KeywordList> $class
     *
     * @return void
     */
    public function setKeywordListClass($name, $class)
    {
        $this->keywordListClasses[$name] = $class;
    }

    /** @return void */
    protected function configure()
    {
        $this
        ->setName('dbal:reserved-words')
        ->setDescription('Checks if the current database contains identifiers that are reserved.')
        ->setDefinition([
            new InputOption('connection', null, InputOption::VALUE_REQUIRED, 'The named database connection'),
            new InputOption(
                'list',
                'l',
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'Keyword-List name.'
            ),
        ])
        ->setHelp(<<<EOT
Checks if the current database contains tables and columns
with names that are identifiers in this dialect or in other SQL dialects.

By default SQLite, MySQL, PostgreSQL, Microsoft SQL Server and Oracle
keywords are checked:

    <info>%command.full_name%</info>

If you want to check against specific dialects you can
pass them to the command:

    <info>%command.full_name% -l mysql -l pgsql</info>

The following keyword lists are currently shipped with Doctrine:

    * mysql
    * mysql57
    * mysql80
    * mariadb102
    * pgsql
    * pgsql100
    * sqlite
    * oracle
    * sqlserver
    * sqlserver2012
    * db2 (Not checked by default)
EOT
        );
    }

    /**
     * {@inheritdoc}
     *
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $conn = $this->getConnection($input);

        $keywordLists = $input->getOption('list');

        if (is_string($keywordLists)) {
            $keywordLists = [$keywordLists];
        } elseif (! is_array($keywordLists)) {
            $keywordLists = [];
        }

        if (count($keywordLists) === 0) {
            $keywordLists = array_keys($this->keywordListClasses);
        }

        $keywords = [];
        foreach ($keywordLists as $keywordList) {
            if (! isset($this->keywordListClasses[$keywordList])) {
                throw new InvalidArgumentException(
                    "There exists no keyword list with name '" . $keywordList . "'. " .
                    'Known lists: ' . implode(', ', array_keys($this->keywordListClasses))
                );
            }

            $class      = $this->keywordListClasses[$keywordList];
            $keywords[] = new $class();
        }

        $output->write(
            'Checking keyword violations for <comment>' . implode(', ', $keywordLists) . '</comment>...',
            true
        );

        $schema  = $conn->getSchemaManager()->createSchema();
        $visitor = new ReservedKeywordsValidator($keywords);
        $schema->visit($visitor);

        $violations = $visitor->getViolations();
        if (count($violations) !== 0) {
            $output->write(
                'There are <error>' . count($violations) . '</error> reserved keyword violations'
                . ' in your database schema:',
                true
            );

            foreach ($violations as $violation) {
                $output->write('  - ' . $violation, true);
            }

            return 1;
        }

        $output->write('No reserved keywords violations have been found!', true);

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
}
