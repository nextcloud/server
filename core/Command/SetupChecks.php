<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Côme Chilliet <come.chilliet@nextcloud.com>
 *
 * @author Côme Chilliet <come.chilliet@nextcloud.com>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OC\Core\Command;

use OCP\SetupCheck\ISetupCheckManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SetupChecks extends Base {
	public function __construct(
		private ISetupCheckManager $setupCheckManager,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		parent::configure();

		$this
			->setName('setupchecks')
			->setDescription('Run setup checks and output the results')
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$results = $this->setupCheckManager->runAll();
		switch ($input->getOption('output')) {
			case self::OUTPUT_FORMAT_JSON:
			case self::OUTPUT_FORMAT_JSON_PRETTY:
				$this->writeArrayInOutputFormat($input, $output, $results);
				break;
			default:
				foreach ($results as $category => $checks) {
					$output->writeln("\t{$category}:");
					foreach ($checks as $title => $check) {
						$styleTag = match ($check->getSeverity()) {
							'success' => 'info',
							'error' => 'error',
							default => 'comment',
						};
						$emoji = match ($check->getSeverity()) {
							'success' => '✓',
							'error' => '❌',
							default => 'ℹ',
						};
						$output->writeln(
							"\t\t<{$styleTag}>".
							"{$emoji} ".
							$title.
							($check->getDescription() !== null ? ': '.$check->getDescription() : '').
							"</{$styleTag}>"
						);
					}
				}
		}
		foreach ($results as $category => $checks) {
			foreach ($checks as $title => $check) {
				if ($check->getSeverity() !== 'success') {
					return 1;
				}
			}
		}
		return 0;
	}
}
