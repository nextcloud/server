<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Evgeny Golyshev <eugulixes@gmail.com>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Matthew Setter <matthew@matthewsetter.com>
 * @author Morris Jobke <hey@morrisjobke.de>
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
namespace OC\Core\Command\Encryption;

use OCP\App\IAppManager;
use OCP\Encryption\IManager;
use OCP\IConfig;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class EncryptAll extends Command {
	protected bool $wasTrashbinEnabled = false;
	protected bool $wasMaintenanceModeEnabled = false;

	public function __construct(
		protected IManager $encryptionManager,
		protected IAppManager $appManager,
		protected IConfig $config,
		protected QuestionHelper $questionHelper,
	) {
		parent::__construct();
	}

	/**
	 * Set maintenance mode and disable the trashbin app
	 */
	protected function forceMaintenanceAndTrashbin(): void {
		$this->wasTrashbinEnabled = (bool)$this->appManager->isEnabledForUser('files_trashbin');
		$this->wasMaintenanceModeEnabled = $this->config->getSystemValueBool('maintenance');
		$this->config->setSystemValue('maintenance', true);
		$this->appManager->disableApp('files_trashbin');
	}

	/**
	 * Reset the maintenance mode and re-enable the trashbin app
	 */
	protected function resetMaintenanceAndTrashbin(): void {
		$this->config->setSystemValue('maintenance', $this->wasMaintenanceModeEnabled);
		if ($this->wasTrashbinEnabled) {
			$this->appManager->enableApp('files_trashbin');
		}
	}

	protected function configure() {
		parent::configure();

		$this->setName('encryption:encrypt-all');
		$this->setDescription('Encrypt all files for all users');
		$this->setHelp(
			'This will encrypt all files for all users. '
			. 'Please make sure that no user access his files during this process!'
		);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		if (!$input->isInteractive()) {
			$output->writeln('Invalid TTY.');
			$output->writeln('If you are trying to execute the command in a Docker ');
			$output->writeln("container, do not forget to execute 'docker exec' with");
			$output->writeln("the '-i' and '-t' options.");
			$output->writeln('');
			return 1;
		}

		if ($this->encryptionManager->isEnabled() === false) {
			throw new \Exception('Server side encryption is not enabled');
		}

		$output->writeln("\n");
		$output->writeln('You are about to encrypt all files stored in your Nextcloud installation.');
		$output->writeln('Depending on the number of available files, and their size, this may take quite some time.');
		$output->writeln('Please ensure that no user accesses their files during this time!');
		$output->writeln('Note: The encryption module you use determines which files get encrypted.');
		$output->writeln('');
		$question = new ConfirmationQuestion('Do you really want to continue? (y/n) ', false);
		if ($this->questionHelper->ask($input, $output, $question)) {
			$this->forceMaintenanceAndTrashbin();

			try {
				$defaultModule = $this->encryptionManager->getEncryptionModule();
				$defaultModule->encryptAll($input, $output);
			} catch (\Exception $ex) {
				$this->resetMaintenanceAndTrashbin();
				throw $ex;
			}

			$this->resetMaintenanceAndTrashbin();
			return 0;
		}
		$output->writeln('aborted');
		return 1;
	}
}
