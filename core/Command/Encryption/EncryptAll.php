<?php
/**
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Joas Schilling <nickvergessen@owncloud.com>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
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

	/** @var IManager */
	protected $encryptionManager;

	/** @var  IAppManager */
	protected $appManager;

	/** @var IConfig */
	protected $config;

	/** @var  QuestionHelper */
	protected $questionHelper;

	/** @var bool */
	protected $wasTrashbinEnabled;

	/** @var  bool */
	protected $wasSingleUserModeEnabled;

	/**
	 * @param IManager $encryptionManager
	 * @param IAppManager $appManager
	 * @param IConfig $config
	 * @param QuestionHelper $questionHelper
	 */
	public function __construct(
		IManager $encryptionManager,
		IAppManager $appManager,
		IConfig $config,
		QuestionHelper $questionHelper
	) {
		parent::__construct();
		$this->appManager = $appManager;
		$this->encryptionManager = $encryptionManager;
		$this->config = $config;
		$this->questionHelper = $questionHelper;
	}

	/**
	 * Set single user mode and disable the trashbin app
	 */
	protected function forceSingleUserAndTrashbin() {
		$this->wasTrashbinEnabled = $this->appManager->isEnabledForUser('files_trashbin');
		$this->wasSingleUserModeEnabled = $this->config->getSystemValue('singleuser', false);
		$this->config->setSystemValue('singleuser', true);
		$this->appManager->disableApp('files_trashbin');
	}

	/**
	 * Reset the single user mode and re-enable the trashbin app
	 */
	protected function resetSingleUserAndTrashbin() {
		$this->config->setSystemValue('singleuser', $this->wasSingleUserModeEnabled);
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

	protected function execute(InputInterface $input, OutputInterface $output) {

		if ($this->encryptionManager->isEnabled() === false) {
			throw new \Exception('Server side encryption is not enabled');
		}

		$output->writeln("\n");
		$output->writeln('You are about to start to encrypt all files stored in your ownCloud.');
		$output->writeln('It will depend on the encryption module you use which files get encrypted.');
		$output->writeln('Depending on the number and size of your files this can take some time');
		$output->writeln('Please make sure that no user access his files during this process!');
		$output->writeln('');
		$question = new ConfirmationQuestion('Do you really want to continue? (y/n) ', false);
		if ($this->questionHelper->ask($input, $output, $question)) {
			$this->forceSingleUserAndTrashbin();

			try {
				$defaultModule = $this->encryptionManager->getEncryptionModule();
				$defaultModule->encryptAll($input, $output);
			} catch (\Exception $ex) {
				$this->resetSingleUserAndTrashbin();
				throw $ex;
			}

			$this->resetSingleUserAndTrashbin();
		} else {
			$output->writeln('aborted');
		}
	}

}
