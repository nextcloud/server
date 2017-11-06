<?php
/**
 * @copyright Copyright (c) 2017 Joas Schilling <coding@schilljs.com>
 * @copyright Copyright (c) 2017, ownCloud GmbH
 *
 * @author Joas Schilling <coding@schilljs.com>
 *
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
use OCP\IDBConnection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateCommand extends Command {

	protected static $_templateSimple =
		'<?php
namespace {{namespace}};

use Doctrine\DBAL\Schema\Schema;
use OCP\Migration\SimpleMigrationStep;
use OCP\Migration\IOutput;

/**
 * Auto-generated migration step: Please modify to your needs!
 */
class {{classname}} extends SimpleMigrationStep {

	/**
	 * @param IOutput $output
	 * @param \Closure $schemaClosure The `\Closure` returns a `Schema`
	 * @param array $options
	 * @since 13.0.0
	 */
	public function preSchemaChange(IOutput $output, \Closure $schemaClosure, array $options) {
	}

	/**
	 * @param IOutput $output
	 * @param \Closure $schemaClosure The `\Closure` returns a `Schema`
	 * @param array $options
	 * @return null|Schema
	 * @since 13.0.0
	 */
	public function changeSchema(IOutput $output, \Closure $schemaClosure, array $options) {
{{schemabody}}
	}

	/**
	 * @param IOutput $output
	 * @param \Closure $schemaClosure The `\Closure` returns a `Schema`
	 * @param array $options
	 * @since 13.0.0
	 */
	public function postSchemaChange(IOutput $output, \Closure $schemaClosure, array $options) {
	}
}
';

	/** @var IDBConnection */
	protected $connection;

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
			->addArgument('version', InputArgument::REQUIRED, 'Major version of this app, to allow versions on parallel development branches')
		;

		parent::configure();
	}

	public function execute(InputInterface $input, OutputInterface $output) {
		$appName = $input->getArgument('app');
		$version = $input->getArgument('version');

		if (!preg_match('/^\d{1,16}$/',$version)) {
			$output->writeln('<error>The given version is invalid. Only 0-9 are allowed (max. 16 digits)</error>');
			return 1;
		}

		$ms = new MigrationService($appName, $this->connection, new ConsoleOutput($output));

		$date = date('YmdHis');
		$path = $this->generateMigration($ms, 'Version' . $version . 'Date' . $date);

		$output->writeln("New migration class has been generated to <info>$path</info>");
		return 0;
	}

	/**
	 * @param MigrationService $ms
	 * @param string $className
	 * @param string $schemaBody
	 * @return string
	 */
	protected function generateMigration(MigrationService $ms, $className, $schemaBody = '') {
		if ($schemaBody === '') {
			$schemaBody = "\t\t" . 'return null;';
		}


		$placeHolders = [
			'{{namespace}}',
			'{{classname}}',
			'{{schemabody}}',
		];
		$replacements = [
			$ms->getMigrationsNamespace(),
			$className,
			$schemaBody,
		];
		$code = str_replace($placeHolders, $replacements, self::$_templateSimple);
		$dir = $ms->getMigrationsDirectory();

		$this->ensureMigrationDirExists($dir);
		$path = $dir . '/' . $className . '.php';

		if (file_put_contents($path, $code) === false) {
			throw new RuntimeException('Failed to generate new migration step.');
		}

		return $path;
	}

	protected function ensureMigrationDirExists($directory) {
		if (file_exists($directory) && is_dir($directory)) {
			return;
		}

		if (file_exists($directory)) {
			throw new \RuntimeException("Could not create folder \"$directory\"");
		}

		$this->ensureMigrationDirExists(dirname($directory));

		if (!@mkdir($directory) && !is_dir($directory)) {
			throw new \RuntimeException("Could not create folder \"$directory\"");
		}
	}
}
