<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2023 Robin Appelman <robin@icewind.nl>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OC\Core\Command\Security;

use OC\Core\Command\Base;
use OC\Security\Bruteforce\Throttler;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class BruteforceStatus extends Base {
	public function __construct(
		protected Throttler $throttler,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		parent::configure();
		$this
			->setName('security:bruteforce:status')
			->setDescription('List bruteforce attempts summary')
			->addOption('ipaddress', 'i', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Only list attempts from the specified ip range in CIDR notation')
			->addOption('action', 'a', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Only list attempts for the specified action')
			->addOption('count', 'c', InputOption::VALUE_REQUIRED, 'Only list a limited number of items with the most occurrences');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$ips = $input->getOption('ipaddress');
		$actions = $input->getOption('action');
		$count = $input->getOption('count');

		$summary = $this->throttler->summarizeAttempts();

		if ($ips) {
			$summary = array_filter($summary, function (array $item) use ($ips) {
				return $this->throttler->inSubnets($item['ip'], $ips);
			});
		}

		if ($actions) {
			$actions = array_map(function (string $action) {
				return strtolower($action);
			}, $actions);
			$summary = array_filter($summary, function (array $item) use ($actions) {
				$lowerAction = strtolower($item['action']);
				foreach ($actions as $action) {
					return str_contains($lowerAction, $action);
				}
				return false;
			});
		}

		if ($count) {
			$summary = array_slice($summary, 0, $count);
		}

		if ($input->getOption('output') === self::OUTPUT_FORMAT_JSON || $input->getOption('output') === self::OUTPUT_FORMAT_JSON_PRETTY) {
			$this->writeArrayInOutputFormat($input, $output, $summary);
		} else {
			$table = new Table($output);
			$table
				->setHeaders(['IP', 'Action', 'Count'])
				->setRows(array_map(function (array $item) {
					return [$item['ip'], $item['action'], $item['count']];
				}, $summary));
			$table->render();
		}
		return 0;
	}
}
