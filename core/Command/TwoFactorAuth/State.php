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

class State extends Base {
	public function __construct(
		private IRegistry $registry,
		IUserManager $userManager,
	) {
		parent::__construct(
			'twofactorauth:state',
			$userManager,
		);
	}

	protected function configure() {
		parent::configure();

		$this->setName('twofactorauth:state');
		$this->setDescription('Get the two-factor authentication (2FA) state of a user');
		$this->addArgument('uid', InputArgument::REQUIRED);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$uid = $input->getArgument('uid');
		$user = $this->userManager->get($uid);
		if (is_null($user)) {
			$output->writeln('<error>Invalid UID</error>');
			return 1;
		}

		$providerStates = $this->registry->getProviderStates($user);
		$filtered = $this->filterEnabledDisabledUnknownProviders($providerStates);
		[$enabled, $disabled] = $filtered;

		if (!empty($enabled)) {
			$output->writeln("Two-factor authentication is enabled for user $uid");
		} else {
			$output->writeln("Two-factor authentication is not enabled for user $uid");
		}

		$output->writeln('');
		$this->printProviders('Enabled providers', $enabled, $output);
		$this->printProviders('Disabled providers', $disabled, $output);

		return 0;
	}

	private function filterEnabledDisabledUnknownProviders(array $providerStates): array {
		$enabled = [];
		$disabled = [];

		foreach ($providerStates as $providerId => $isEnabled) {
			if ($isEnabled) {
				$enabled[] = $providerId;
			} else {
				$disabled[] = $providerId;
			}
		}

		return [$enabled, $disabled];
	}

	private function printProviders(string $title, array $providers,
		OutputInterface $output) {
		if (empty($providers)) {
			// Ignore and don't print anything
			return;
		}

		$output->writeln($title . ':');
		foreach ($providers as $provider) {
			$output->writeln('- ' . $provider);
		}
	}
}
