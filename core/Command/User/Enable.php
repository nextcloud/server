<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Core\Command\User;

use OC\Core\Command\Base;
use OCP\IUser;
use OCP\IUserManager;
use Stecman\Component\Symfony\Console\BashCompletion\CompletionContext;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Enable extends Base {
	public function __construct(
		protected IUserManager $userManager,
	) {
		parent::__construct();
	}

	protected function configure() {
		$this
			->setName('user:enable')
			->setDescription('enables the specified user')
			->addArgument(
				'uid',
				InputArgument::REQUIRED,
				'the username'
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$user = $this->userManager->get($input->getArgument('uid'));
		if (is_null($user)) {
			$output->writeln('<error>User does not exist</error>');
			return 1;
		}

		$user->setEnabled(true);
		$output->writeln('<info>The specified user is enabled</info>');
		return 0;
	}

	/**
	 * @param string $argumentName
	 * @param CompletionContext $context
	 * @return string[]
	 */
	public function completeArgumentValues($argumentName, CompletionContext $context) {
		if ($argumentName === 'uid') {
			return array_map(
				static fn (IUser $user) => $user->getUID(),
				array_filter(
					$this->userManager->search($context->getCurrentWord()),
					static fn (IUser $user) => !$user->isEnabled()
				)
			);
		}
		return [];
	}
}
