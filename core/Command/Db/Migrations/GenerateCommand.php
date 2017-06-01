<?php
/**
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @copyright Copyright (c) 2017, ownCloud GmbH
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OC\Core\Command\Db\Migrations;


use OC\DB\MigrationService;
use OC\Migration\ConsoleOutput;
use OCP\IConfig;
use OCP\IDBConnection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateCommand extends Command {

	private static $_templateSimple =
		'<?php
namespace <namespace>;

use OCP\Migration\ISimpleMigration;
use OCP\Migration\IOutput;

/**
 * Auto-generated migration step: Please modify to your needs!
 */
class Version<version> implements ISimpleMigration {

    /**
     * @param IOutput $out
     */
    public function run(IOutput $out) {
        // auto-generated - please modify it to your needs
    }
}
';

	private static $_templateSchema =
		'<?php
namespace <namespace>;

use Doctrine\DBAL\Schema\Schema;
use OCP\Migration\ISchemaMigration;

/**
 * Auto-generated migration step: Please modify to your needs!
 */
class Version<version> implements ISchemaMigration {

	public function changeSchema(Schema $schema, array $options) {
		// auto-generated - please modify it to your needs
    }
}
';

	private static $_templateSql =
		'<?php
namespace <namespace>;

use OCP\IDBConnection;
use OCP\Migration\ISqlMigration;

/**
 * Auto-generated migration step: Please modify to your needs!
 */
class Version<version> implements ISqlMigration {

	public function sql(IDBConnection $connection) {
		// auto-generated - please modify it to your needs
    }
}
';

	/** @var IDBConnection */
	private $connection;

	/**
	 * @param IDBConnection $connection
	 */
	public function __construct(IDBConnection $connection) {
		$this->connection = $connection;

		parent::__construct();
	}

	protected function configure() {
		$this
			->setName('migrations:generate')
			->addArgument('app', InputArgument::REQUIRED, 'Name of the app this migration command shall work on')
			->addArgument('kind', InputArgument::REQUIRED, 'simple, schema or sql - defines the kind of migration to be generated');

		parent::configure();
	}

	public function execute(InputInterface $input, OutputInterface $output) {
		$appName = $input->getArgument('app');
		$ms = new MigrationService($appName, $this->connection, new ConsoleOutput($output));

		$kind = $input->getArgument('kind');
		$version = date('YmdHis');
		$path = $this->generateMigration($ms, $version, $kind);

		$output->writeln("New migration class has been generated to <info>$path</info>");

	}

	/**
	 * @param MigrationService $ms
	 * @param string $version
	 * @param string $kind
	 * @return string
	 */
	private function generateMigration(MigrationService $ms, $version, $kind) {
		$placeHolders = [
			'<namespace>',
			'<version>',
		];
		$replacements = [
			$ms->getMigrationsNamespace(),
			$version,
		];
		$code = str_replace($placeHolders, $replacements, $this->getTemplate($kind));
		$dir = $ms->getMigrationsDirectory();
		$path = $dir . '/Version' . $version . '.php';

		if (file_put_contents($path, $code) === false) {
			throw new RuntimeException('Failed to generate new migration step.');
		}

		return $path;
	}

	private function getTemplate($kind) {
		if ($kind === 'simple') {
			return self::$_templateSimple;
		}
		if ($kind === 'schema') {
			return self::$_templateSchema;
		}
		if ($kind === 'sql') {
			return self::$_templateSql;
		}
		throw new \InvalidArgumentException('Kind can only be one of the following: simple, schema or sql');
	}

}
