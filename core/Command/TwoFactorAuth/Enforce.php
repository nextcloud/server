<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Core\Command\TwoFactorAuth;

use OC\Authentication\TwoFactorAuth\EnforcementState;
use OC\Authentication\TwoFactorAuth\MandatoryTwoFactor;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use function implode;

class Enforce extends Command {
	public function __construct(
		private MandatoryTwoFactor $mandatoryTwoFactor,
	) {
		parent::__construct();
	}

	protected function configure() {
		$this->setName('twofactorauth:enforce');
		$this->setDescription('Enabled/disable enforced two-factor authentication');
		$this->addOption(
			'on',
			null,
			InputOption::VALUE_NONE,
			'enforce two-factor authentication'
		);
		$this->addOption(
			'off',
			null,
			InputOption::VALUE_NONE,
			'don\'t enforce two-factor authenticaton'
		);
		$this->addOption(
			'group',
			null,
			InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
			'enforce only for the given group(s)'
		);
		$this->addOption(
			'exclude',
			null,
			InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
			'exclude mandatory two-factor auth for the given group(s)'
		);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		if ($input->getOption('on')) {
			$enforcedGroups = $input->getOption('group');
			$excludedGroups = $input->getOption('exclude');
			$this->mandatoryTwoFactor->setState(new EnforcementState(true, $enforcedGroups, $excludedGroups));
		} elseif ($input->getOption('off')) {
			$this->mandatoryTwoFactor->setState(new EnforcementState(false));
		}

		$state = $this->mandatoryTwoFactor->getState();
		if ($state->isEnforced()) {
			$this->writeEnforced($output, $state);
		} else {
			$this->writeNotEnforced($output);
		}
		return 0;
	}

	protected function writeEnforced(OutputInterface $output, EnforcementState $state) {
		if (empty($state->getEnforcedGroups())) {
			$message = 'Two-factor authentication is enforced for all users';
		} else {
			$message = 'Two-factor authentication is enforced for members of the group(s) ' . implode(', ', $state->getEnforcedGroups());
		}
		if (!empty($state->getExcludedGroups())) {
			$message .= ', except members of ' . implode(', ', $state->getExcludedGroups());
		}
		$output->writeln($message);
	}

	protected function writeNotEnforced(OutputInterface $output) {
		$output->writeln('Two-factor authentication is not enforced');
	}
}
