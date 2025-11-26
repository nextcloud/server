<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Core\Command\Encryption;

use OCP\App\IAppManager;
use OCP\Encryption\IManager;
use OCP\IConfig;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class EncryptAll extends Command {
	protected bool $wasTrashbinEnabled = false;

	public function __construct(
		protected IManager $encryptionManager,
		protected IAppManager $appManager,
		protected IConfig $config,
		protected QuestionHelper $questionHelper,
		private LoggerInterface $logger,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		parent::configure();

		$this
			->setName('encryption:encrypt-all')
			->setDescription('Encrypt all users\' files using Nextcloud Server Side Encryption')
			->setHelp(
				"This will encrypt all files server-side for all users.\n" .
				"Maintenance mode will be enabled automatically.\n" .
				"Users should not access their files during this process!\n" .
				"WARNING: Please read the documentation prior to utilizing this feature to avoid data loss!"
		);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {

		// Handle container environment TTY problems to avoid confusion
		if (!$input->isInteractive()) {
			$output->writeln('<error>Invalid TTY.</error>');
			$output->writeln("<comment>If running inside Docker, use 'docker exec -it' or equivalent.</comment>");
			$output->writeln('');
			return self::FAILURE;
		}

		// Prevent running if SSE isn't enabled
		if ($this->encryptionManager->isEnabled() === false) {
			$output->writeln('<error>Server Side Encryption is not enabled; unable to encrypt files.</error>');
			return self::FAILURE;
		}

		// Prevent running if no valid SSE modules are registered
		$modules = $this->encryptionManager->getEncryptionModules();
		if (empty($modules)) {
			$output->writeln('<error>No Server Side Encryption modules are registered; unable to encrypt files.</error>');
			return self::FAILURE;
		}

		// Prevent running if a default SSE module isn't already configured
		$defaultModuleId = $this->encryptionManager->getDefaultEncryptionModuleId();
		if ($defaultModuleId === '') {
			$output->writeln('<error>A default Server Side Encryption module is not configured; unable to encrypt files.</error>');
			return self::FAILURE;
		}

		// Prevent running if the default SSE module isn't valid
		if (!isset($modules[$defaultModuleId])) {
			$output->writeln('<error>The current default Server Side Encryption module does not exist: ' . $defaultModuleId . '; unable to encrypt files.</error>');
			return self::FAILURE;
		}

		// Prevent running if maintenance mode is already enabled
		if ($this->config->getSystemValueBool('maintenance')) {
			$output->writeln('<error>This command cannot be run with maintenance mode enabled.</error>');
			return self::FAILURE;
		}

		// TODO: Might make sense to add some additional readiness checks here such as the readiness of key storage/etc

		$output->writeln("\n");
		$output->writeln('You are about to encrypt all files stored in your Nextcloud installation.');
		$output->writeln('Depending on the number and size of files, this may take a long time.');
		$output->writeln('Please ensure that no user accesses their files during this process!');
		$output->writeln('Note: The encryption module you use and its settings determine which files get encrypted.');
		$output->writeln('Reminder: If External Storage is included in encryption, those files will no longer be accessible outside Nextcloud.');
		$output->writeln('WARNING: Please read the documentation prior to utilizing this feature to avoid data loss!');
		$output->writeln('');

		// require "yes" to be typed in fully since this is a sensitive action
		$question = new ConfirmationQuestion('Do you really want to continue? (yes/NO) ', false, '/^yes$/i'); 
		if (!$this->questionHelper->ask($input, $output, $question)) {
			$output->writeln("\n" . 'Aborted.');
			return self::FAILURE;
		}

		// Requirements before proceeding: disable trash bin, enable maintenance mode
		$this->forceMaintenanceAndTrashbin();

		// Encrypt all the files
		try {
			$defaultModule = $this->encryptionManager->getEncryptionModule();
			$defaultModule->encryptAll($input, $output);
		} catch (\Throwable $ex) {
			$output->writeln('<error>Encryption failed: ' . $ex->getMessage() . '</error>');
			$this->logger->error('encryption:encrypt-all failed', ['exception' => $ex]);
			return self::FAILURE;
		} finally {
		    // restore state no matter what (XXX: Better coverage than prior behavior; though I'm not convinced either is ideal)
			$this->resetMaintenanceAndTrashbin();
		}
		// If we made it here, we're good
		return self::SUCCESS;
	}

	/**
	 * Set maintenance mode and disable the trashbin app
	 * TODO: The "why?" should be noted here.
	 */
	protected function forceMaintenanceAndTrashbin(): void {
		$this->wasTrashbinEnabled = (bool)$this->appManager->isEnabledForUser('files_trashbin');
		$this->config->setSystemValue('maintenance', true);
		$this->appManager->disableApp('files_trashbin');
		// TODO: Determine whether files_versions should be disabled temporarily too
	}

	/**
	 * Reset the maintenance mode and re-enable the trashbin app
	 */
	protected function resetMaintenanceAndTrashbin(): void {
		$this->config->setSystemValue('maintenance', false);
		if ($this->wasTrashbinEnabled) {
			$this->appManager->enableApp('files_trashbin');
		}
	}
}
