<?php
/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Encryption\Command;

use OCA\Encryption\Util;
use OCP\IConfig;
use OCP\IUserManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class RecoverUser extends Command {
	public function __construct(
		protected Util $util,
		IConfig $config,
		protected IUserManager $userManager,
		protected QuestionHelper $questionHelper,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this
			->setName('encryption:recover-user')
			->setDescription('Recover user data in case of password lost. This only works if the user enabled the recovery key.');

		$this->addArgument(
			'user',
			InputArgument::REQUIRED,
			'user which should be recovered'
		);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$isMasterKeyEnabled = $this->util->isMasterKeyEnabled();

		if ($isMasterKeyEnabled) {
			$output->writeln('You use the master key, no individual user recovery needed.');
			return self::SUCCESS;
		}

		$uid = $input->getArgument('user');
		$userExists = $this->userManager->userExists($uid);
		if ($userExists === false) {
			$output->writeln('User "' . $uid . '" unknown.');
			return self::FAILURE;
		}

		$recoveryKeyEnabled = $this->util->isRecoveryEnabledForUser($uid);
		if ($recoveryKeyEnabled === false) {
			$output->writeln('Recovery key is not enabled for: ' . $uid);
			return self::FAILURE;
		}

		$question = new Question('Please enter the recovery key password: ');
		$question->setHidden(true);
		$question->setHiddenFallback(false);
		$recoveryPassword = $this->questionHelper->ask($input, $output, $question);

		$question = new Question('Please enter the new login password for the user: ');
		$question->setHidden(true);
		$question->setHiddenFallback(false);
		$newLoginPassword = $this->questionHelper->ask($input, $output, $question);

		$output->write('Start to recover users files... This can take some time...');
		$this->userManager->get($uid)->setPassword($newLoginPassword, $recoveryPassword);
		$output->writeln('Done.');
		return self::SUCCESS;
	}
}
