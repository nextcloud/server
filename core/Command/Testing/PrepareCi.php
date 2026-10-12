<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core\Command\Testing;

use OC\Core\Command\Base;
use OCP\IConfig;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PrepareCi extends Base {
	public function __construct(
		private readonly IConfig $config,
	) {
		parent::__construct();
	}

	#[\Override]
	protected function configure(): void {
		parent::configure();

		$this
			->setName('testing:prepare-ci')
			->setDescription('Trade security for speed on a CI instance, never use this in production');
	}

	#[\Override]
	protected function execute(InputInterface $input, OutputInterface $output): int {
		$openssl = $this->config->getSystemValue('openssl', []);
		$openssl['private_key_bits'] = 2048;

		$values = [
			// bcrypt instead of argon2, so hashingCost applies
			'hashing_default_password' => true,
			'hashingCost' => 4,
			'openssl' => $openssl,
		];
		$this->config->setSystemValues($values);

		foreach ($values as $key => $value) {
			$output->writeln('<info>Set ' . $key . ' to ' . json_encode($value) . '</info>');
		}

		return self::SUCCESS;
	}
}
