<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Core\Command\Security;

use OC\Core\Command\Base;
use OCP\Security\Bruteforce\IThrottler;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class BruteforceResetAttempts extends Base {
	public function __construct(
		protected IThrottler $throttler,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this
			->setName('security:bruteforce:reset')
			->setDescription('resets bruteforce attempts for given IP address')
			->addArgument(
				'ipaddress',
				InputArgument::REQUIRED,
				'IP address for which the attempts are to be reset'
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$ip = $input->getArgument('ipaddress');

		if (!filter_var($ip, FILTER_VALIDATE_IP)) {
			$output->writeln('<error>"' . $ip . '" is not a valid IP address</error>');
			return 1;
		}

		$this->throttler->resetDelayForIP($ip);
		return 0;
	}
}
