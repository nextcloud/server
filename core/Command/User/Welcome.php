<?php
/**
 * SPDX-FileCopyrightText: 2023 FedericoHeichou <federicoheichou@gmail.com>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core\Command\User;

use OC\Core\Command\Base;
use OCA\Settings\Mailer\NewUserMailHelper;
use OCP\IUserManager;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Welcome extends Base {
	/** @var IUserManager */
	protected $userManager;

	/** @var NewUserMailHelper */
	private $newUserMailHelper;

	/**
	 * @param IUserManager $userManager
	 * @param NewUserMailHelper $newUserMailHelper
	 */
	public function __construct(
		IUserManager $userManager,
		NewUserMailHelper $newUserMailHelper,
	) {
		parent::__construct();

		$this->userManager = $userManager;
		$this->newUserMailHelper = $newUserMailHelper;
	}

	/**
	 * @return void
	 */
	protected function configure() {
		$this
			->setName('user:welcome')
			->setDescription('Sends the welcome email')
			->addArgument(
				'user',
				InputArgument::REQUIRED,
				'The user to send the email to'
			)
			->addOption(
				'reset-password',
				'r',
				InputOption::VALUE_NONE,
				'Add the reset password link to the email'
			)
		;
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return int
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int {
		$userId = $input->getArgument('user');
		// check if user exists
		$user = $this->userManager->get($userId);
		if ($user === null) {
			$output->writeln('<error>User does not exist</error>');
			return 1;
		}
		$email = $user->getEMailAddress();
		if ($email === '' || $email === null) {
			$output->writeln('<error>User does not have an email address</error>');
			return 1;
		}
		try {
			$emailTemplate = $this->newUserMailHelper->generateTemplate($user, $input->getOption('reset-password'));
			$this->newUserMailHelper->sendMail($user, $emailTemplate);
		} catch (\Exception $e) {
			$output->writeln('<error>Failed to send email: ' . $e->getMessage() . '</error>');
			return 1;
		}
		return 0;
	}
}
