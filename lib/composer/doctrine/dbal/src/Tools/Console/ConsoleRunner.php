<?php

namespace Doctrine\DBAL\Tools\Console;

use Doctrine\DBAL\Tools\Console\Command\ReservedWordsCommand;
use Doctrine\DBAL\Tools\Console\Command\RunSqlCommand;
use Exception;
use PackageVersions\Versions;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;

/**
 * Handles running the Console Tools inside Symfony Console context.
 */
class ConsoleRunner
{
    /**
     * Runs console with the given connection provider.
     *
     * @param Command[] $commands
     *
     * @return void
     *
     * @throws Exception
     */
    public static function run(ConnectionProvider $connectionProvider, $commands = [])
    {
        $cli = new Application('Doctrine Command Line Interface', Versions::getVersion(
            Versions::rootPackageName()
        ));

        $cli->setCatchExceptions(true);
        self::addCommands($cli, $connectionProvider);
        $cli->addCommands($commands);
        $cli->run();
    }

    /**
     * @return void
     */
    public static function addCommands(Application $cli, ConnectionProvider $connectionProvider)
    {
        $cli->addCommands([
            new RunSqlCommand($connectionProvider),
            new ReservedWordsCommand($connectionProvider),
        ]);
    }

    /**
     * Prints the instructions to create a configuration file
     *
     * @return void
     */
    public static function printCliConfigTemplate()
    {
        echo <<<'HELP'
You are missing a "cli-config.php" or "config/cli-config.php" file in your
project, which is required to get the Doctrine-DBAL Console working. You can use the
following sample as a template:

<?php
use Doctrine\DBAL\Tools\Console\ConnectionProvider\SingleConnectionProvider;

// You can append new commands to $commands array, if needed

// replace with the mechanism to retrieve DBAL connection(s) in your app
// and return a Doctrine\DBAL\Tools\Console\ConnectionProvider instance.
$connection = getDBALConnection();

// in case you have a single connection you can use SingleConnectionProvider
// otherwise you need to implement the Doctrine\DBAL\Tools\Console\ConnectionProvider interface with your custom logic
return new SingleConnectionProvider($connection);

HELP;
    }
}
