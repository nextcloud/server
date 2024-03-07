<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Joas Schilling <coding@schilljs.com>
 * @author Victor Dubiniuk <dubiniuk@owncloud.com>
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
namespace OC\Core\Command\App;

use OC\Core\Command\Base;
use OCP\App\AppPathNotFoundException;
use OCP\App\IAppManager;
use Stecman\Component\Symfony\Console\BashCompletion\CompletionContext;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GetPath extends Base {
	public function __construct(
		protected IAppManager $appManager,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		parent::configure();

		$this
			->setName('app:getpath')
			->setDescription('Get an absolute path to the app directory')
			->addArgument(
				'app',
				InputArgument::REQUIRED,
				'Name of the app'
			)
		;
	}

	/**
	 * Executes the current command.
	 *
	 * @param InputInterface  $input  An InputInterface instance
	 * @param OutputInterface $output An OutputInterface instance
	 * @return int 0 if everything went fine, or an error code
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int {
		$appName = $input->getArgument('app');
		try {
			$path = $this->appManager->getAppPath($appName);
		} catch (AppPathNotFoundException) {
			// App not found, exit with non-zero
			return self::FAILURE;
		}
		$output->writeln($path);
		return self::SUCCESS;
	}

	/**
	 * @param string $argumentName
	 * @param CompletionContext $context
	 * @return string[]
	 */
	public function completeArgumentValues($argumentName, CompletionContext $context): array {
		if ($argumentName === 'app') {
			return $this->appManager->getInstalledApps();
		}
		return [];
	}
}
