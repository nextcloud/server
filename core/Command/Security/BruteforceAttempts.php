<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2023 Joas Schilling <coding@schilljs.com>
 *
 * @author Joas Schilling <coding@schilljs.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OC\Core\Command\Security;

use OC\Core\Command\Base;
use OCP\Security\Bruteforce\IThrottler;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class BruteforceAttempts extends Base {
	public function __construct(
		protected IThrottler $throttler,
	) {
		parent::__construct();
	}

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
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$ip = $input->getArgument('ipaddress');

		if (!filter_var($ip, FILTER_VALIDATE_IP)) {
			$output->writeln('<error>"' . $ip . '" is not a valid IP address</error>');
			return 1;
		}

		$data = [
			'bypass-listed' => $this->throttler->isBypassListed($ip),
			'attempts' => $this->throttler->getAttempts(
				$ip,
				(string) $input->getArgument('action'),
			),
			'delay' => $this->throttler->getDelay(
				$ip,
				(string) $input->getArgument('action'),
			),
		];

		$this->writeArrayInOutputFormat($input, $output, $data);

		return 0;
	}
}
