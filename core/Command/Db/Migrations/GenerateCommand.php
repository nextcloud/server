<?php
/**
 * @copyright Copyright (c) 2017 Joas Schilling <coding@schilljs.com>
 * @copyright Copyright (c) 2017, ownCloud GmbH
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OC\Core\Command\Db\Migrations;

use OC\DB\Connection;
use OC\DB\MigrationService;
use OC\Migration\ConsoleOutput;
use OCP\App\IAppManager;
use OCP\Util;
use Stecman\Component\Symfony\Console\BashCompletion\Completion\CompletionAwareInterface;
use Stecman\Component\Symfony\Console\BashCompletion\CompletionContext;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class GenerateCommand extends Command implements CompletionAwareInterface {
	protected static $_templateSimple =
		'<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) {{year}} Your name <your@email.com>
 *
 * @author Your name <your@email.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace {{namespace}};

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * Auto-generated migration step: Please modify to your needs!
 */
class {{classname}} extends SimpleMigrationStep {

	/**
	 * @param IOutput $output
	 * @param Closure(): ISchemaWrapper $schemaClosure
	 * @param array $options
	 */
	public function preSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
	}

	/**
	 * @param IOutput $output
	 * @param Closure(): ISchemaWrapper $schemaClosure
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
{{schemabody}}
	}

	/**
	 * @param IOutput $output
	 * @param Closure(): ISchemaWrapper $schemaClosure
	 * @param array $options
	 */
	public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
	}
}
';

	protected Connection $connection;
	protected IAppManager $appManager;

	public function __construct(Connection $connection, IAppManager $appManager) {
		$this->connection = $connection;
		$this->appManager = $appManager;

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

	public function execute(InputInterface $input, OutputInterface $output): int {
		$appName = $input->getArgument('app');
		$version = $input->getArgument('version');

		if (!preg_match('/^\d{1,16}$/', $version)) {
			$output->writeln('<error>The given version is invalid. Only 0-9 are allowed (max. 16 digits)</error>');
			return 1;
		}

		if ($appName === 'core') {
			$fullVersion = implode('.', Util::getVersion());
		} else {
			try {
				$fullVersion = $this->appManager->getAppVersion($appName, false);
			} catch (\Throwable $e) {
				$fullVersion = '';
			}
		}

		if ($fullVersion) {
			[$major, $minor] = explode('.', $fullVersion);
			$shouldVersion = (string) ((int)$major * 1000 + (int)$minor);
			if ($version !== $shouldVersion) {
				$output->writeln('<comment>Unexpected migration version for current version: ' . $fullVersion . '</comment>');
				$output->writeln('<comment> - Pattern:  XYYY </comment>');
				$output->writeln('<comment> - Expected: ' . $shouldVersion . '</comment>');
				$output->writeln('<comment> - Actual:   ' . $version . '</comment>');

				if ($input->isInteractive()) {
					$helper = $this->getHelper('question');
					$question = new ConfirmationQuestion('Continue with your given version? (y/n) [n] ', false);

					if (!$helper->ask($input, $output, $question)) {
						return 1;
					}
				}
			}
		}

		$ms = new MigrationService($appName, $this->connection, new ConsoleOutput($output));

		$date = date('YmdHis');
		$path = $this->generateMigration($ms, 'Version' . $version . 'Date' . $date);

		$output->writeln("New migration class has been generated to <info>$path</info>");
		return 0;
	}

	/**
	 * @param string $optionName
	 * @param CompletionContext $context
	 * @return string[]
	 */
	public function completeOptionValues($optionName, CompletionContext $context) {
		return [];
	}

	/**
	 * @param string $argumentName
	 * @param CompletionContext $context
	 * @return string[]
	 */
	public function completeArgumentValues($argumentName, CompletionContext $context) {
		if ($argumentName === 'app') {
			$allApps = \OC_App::getAllApps();
			return array_diff($allApps, \OC_App::getEnabledApps(true, true));
		}

		if ($argumentName === 'version') {
			$appName = $context->getWordAtIndex($context->getWordIndex() - 1);

			$version = explode('.', $this->appManager->getAppVersion($appName));
			return [$version[0] . sprintf('%1$03d', $version[1])];
		}

		return [];
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
			'{{year}}',
		];
		$replacements = [
			$ms->getMigrationsNamespace(),
			$className,
			$schemaBody,
			date('Y')
		];
		$code = str_replace($placeHolders, $replacements, self::$_templateSimple);
		$dir = $ms->getMigrationsDirectory();

		$this->ensureMigrationDirExists($dir);
		$path = $dir . '/' . $className . '.php';

		if (file_put_contents($path, $code) === false) {
			throw new RuntimeException('Failed to generate new migration step. Could not write to ' . $path);
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
