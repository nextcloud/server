<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Core\Command\TwoFactorAuth;

use OCP\Authentication\TwoFactorAuth\IRegistry;
use OCP\IUserManager;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Cleanup extends Base {
	public function __construct(
		private IRegistry $registry,
		IUserManager $userManager,
	) {
		parent::__construct(
			null,
			$userManager,
		);
	}

	protected function configure() {
		parent::configure();

		$this->setName('twofactorauth:cleanup');
		$this->setDescription('Clean up the two-factor user-provider association of an uninstalled/removed provider');
		$this->addArgument('provider-id', InputArgument::REQUIRED);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$providerId = $input->getArgument('provider-id');

		$this->registry->cleanUp($providerId);

		$output->writeln("<info>All user-provider associations for provider <options=bold>$providerId</> have been removed.</info>");
		return 0;
	}
}
