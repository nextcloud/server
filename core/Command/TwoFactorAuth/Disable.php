<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Core\Command\TwoFactorAuth;

use OC\Authentication\TwoFactorAuth\ProviderManager;
use OCP\IUserManager;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Disable extends Base {
	public function __construct(
		private ProviderManager $manager,
		IUserManager $userManager,
	) {
		parent::__construct(
			'twofactorauth:disable',
			$userManager,
		);
	}

	protected function configure() {
		parent::configure();

		$this->setName('twofactorauth:disable');
		$this->setDescription('Disable two-factor authentication for a user');
		$this->addArgument('uid', InputArgument::REQUIRED);
		$this->addArgument('provider_id', InputArgument::REQUIRED);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$uid = $input->getArgument('uid');
		$providerId = $input->getArgument('provider_id');
		$user = $this->userManager->get($uid);
		if (is_null($user)) {
			$output->writeln('<error>Invalid UID</error>');
			return 1;
		}
		if ($this->manager->tryDisableProviderFor($providerId, $user)) {
			$output->writeln("Two-factor provider <options=bold>$providerId</> disabled for user <options=bold>$uid</>.");
			return 0;
		} else {
			$output->writeln('<error>The provider does not support this operation.</error>');
			return 2;
		}
	}
}
