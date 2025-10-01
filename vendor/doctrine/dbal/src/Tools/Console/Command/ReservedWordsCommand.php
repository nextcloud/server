<?php

namespace Doctrine\DBAL\Tools\Console\Command;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Platforms\Keywords\DB2Keywords;
use Doctrine\DBAL\Platforms\Keywords\KeywordList;
use Doctrine\DBAL\Platforms\Keywords\MariaDb102Keywords;
use Doctrine\DBAL\Platforms\Keywords\MariaDb117Keywords;
use Doctrine\DBAL\Platforms\Keywords\MySQL57Keywords;
use Doctrine\DBAL\Platforms\Keywords\MySQL80Keywords;
use Doctrine\DBAL\Platforms\Keywords\MySQL84Keywords;
use Doctrine\DBAL\Platforms\Keywords\MySQLKeywords;
use Doctrine\DBAL\Platforms\Keywords\OracleKeywords;
use Doctrine\DBAL\Platforms\Keywords\PostgreSQL100Keywords;
use Doctrine\DBAL\Platforms\Keywords\PostgreSQL94Keywords;
use Doctrine\DBAL\Platforms\Keywords\ReservedKeywordsValidator;
use Doctrine\DBAL\Platforms\Keywords\SQLiteKeywords;
use Doctrine\DBAL\Platforms\Keywords\SQLServer2012Keywords;
use Doctrine\DBAL\Tools\Console\ConnectionProvider;
use Doctrine\Deprecations\Deprecation;
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

/** @deprecated Use database documentation instead. */
class ReservedWordsCommand extends Command
{
    use CommandCompatibility;

    /** @var array<string,KeywordList> */
    private array $keywordLists;

    private ConnectionProvider $connectionProvider;

    public function __construct(ConnectionProvider $connectionProvider)
    {
        Deprecation::triggerIfCalledFromOutside(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/pull/5431',
            'ReservedWordsCommand is deprecated. Use database documentation instead.',
        );

        parent::__construct();

        $this->connectionProvider = $connectionProvider;

        $this->keywordLists = [
            'db2'        => new DB2Keywords(),
            'mariadb102' => new MariaDb102Keywords(),
            'mariadb117' => new MariaDb117Keywords(),
            'mysql'      => new MySQLKeywords(),
            'mysql57'    => new MySQL57Keywords(),
            'mysql80'    => new MySQL80Keywords(),
            'mysql84'    => new MySQL84Keywords(),
            'oracle'     => new OracleKeywords(),
            'pgsql'      => new PostgreSQL94Keywords(),
            'pgsql100'   => new PostgreSQL100Keywords(),
            'sqlite'     => new SQLiteKeywords(),
            'sqlserver'  => new SQLServer2012Keywords(),
        ];
    }

    /**
     * Add or replace a keyword list.
     */
    public function setKeywordList(string $name, KeywordList $keywordList): void
    {
        $this->keywordLists[$name] = $keywordList;
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
        Deprecation::trigger(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/issues/4510',
            'ReservedWordsCommand::setKeywordListClass() is deprecated,'
                . ' use ReservedWordsCommand::setKeywordList() instead.',
        );

        $this->keywordLists[$name] = new $class();
    }

    private function doConfigure(): void
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
                'Keyword-List name.',
            ),
        ])
        ->setHelp(<<<'EOT'
Checks if the current database contains tables and columns
with names that are identifiers in this dialect or in other SQL dialects.

By default all supported platform keywords are checked:

    <info>%command.full_name%</info>

If you want to check against specific dialects you can
pass them to the command:

    <info>%command.full_name% -l mysql -l pgsql</info>

The following keyword lists are currently shipped with Doctrine:

    * db2
    * mariadb102
    * mariadb117
    * mysql
    * mysql57
    * mysql80
    * mysql84
    * oracle
    * pgsql
    * pgsql100
    * sqlite
    * sqlserver
EOT);
    }

    /** @throws Exception */
    private function doExecute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln(
            '<comment>The <info>dbal:reserved-words</info> command is deprecated.</comment>'
                . ' Use the documentation on the used database platform(s) instead.',
        );
        $output->writeln('');

        $conn = $this->getConnection($input);

        $keywordLists = $input->getOption('list');

        if (is_string($keywordLists)) {
            $keywordLists = [$keywordLists];
        } elseif (! is_array($keywordLists)) {
            $keywordLists = [];
        }

        if (count($keywordLists) === 0) {
            $keywordLists = array_keys($this->keywordLists);
        }

        $keywords = [];
        foreach ($keywordLists as $keywordList) {
            if (! isset($this->keywordLists[$keywordList])) {
                throw new InvalidArgumentException(
                    "There exists no keyword list with name '" . $keywordList . "'. " .
                    'Known lists: ' . implode(', ', array_keys($this->keywordLists)),
                );
            }

            $keywords[] = $this->keywordLists[$keywordList];
        }

        $output->write(
            'Checking keyword violations for <comment>' . implode(', ', $keywordLists) . '</comment>...',
            true,
        );

        $schema  = $conn->getSchemaManager()->introspectSchema();
        $visitor = new ReservedKeywordsValidator($keywords);
        $schema->visit($visitor);

        $violations = $visitor->getViolations();
        if (count($violations) !== 0) {
            $output->write(
                'There are <error>' . count($violations) . '</error> reserved keyword violations'
                . ' in your database schema:',
                true,
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
