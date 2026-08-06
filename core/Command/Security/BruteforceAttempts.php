<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core\Command\Security;

use OC\Core\Command\Base;
use OCP\Security\Bruteforce\IThrottler;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class BruteforceAttempts extends Base {
	public function __construct(
		protected IThrottler $throttler,
	) {
		parent::__construct();
	}

	#[\Override]
	protected function configure(): void {
		parent::configure();
		$this
			->setName('security:bruteforce:attempts')
			->setDescription('Show bruteforce attempts status for a given IP address')
			->addArgument(
				'ipaddress',
				InputArgument::REQUIRED,
				'IP address for which the attempts status is to be shown',
			)
			->addArgument(
				'action',
				InputArgument::OPTIONAL,
				'Only count attempts for the given action',
			)
			->addOption(
				'interval',
				null,
				InputOption::VALUE_REQUIRED,
				'Interval in hours to count attempts for (max 48)',
				12,
			)
		;
	}

	#[\Override]
	protected function execute(InputInterface $input, OutputInterface $output): int {
		$ip = $input->getArgument('ipaddress');
		$interval = (float)$input->getOption('interval');

		if (!filter_var($ip, FILTER_VALIDATE_IP)) {
			$output->writeln('<error>"' . $ip . '" is not a valid IP address</error>');
			return 1;
		}

		$data = [
			'bypass-listed' => $this->throttler->isBypassListed($ip),
			'attempts' => $this->throttler->getAttempts(
				$ip,
				(string)$input->getArgument('action'),
				$interval,
			),
			'delay' => $this->throttler->getDelay(
				$ip,
				(string)$input->getArgument('action'),
			),
		];
		if ($input->getOption('output') === 'plain') {
			$data['delay'] = $data['delay'] . ' ms';
		}

		$this->writeArrayInOutputFormat($input, $output, $data);

		return 0;
	}
}
