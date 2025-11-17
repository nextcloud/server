<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Repair\NC25;

use OCP\HintException;
use OCP\IConfig;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;
use OCP\Security\ISecureRandom;

class AddMissingSecretJob implements IRepairStep {
	public function __construct(
		private IConfig $config,
		private ISecureRandom $random,
	) {
	}

	public function getName(): string {
		return 'Add possibly missing system config';
	}

	public function run(IOutput $output): void {
		$passwordSalt = $this->config->getSystemValueString('passwordsalt', '');
		if ($passwordSalt === '') {
			try {
				$this->config->setSystemValue('passwordsalt', $this->random->generate(30));
			} catch (HintException $e) {
				$output->warning('passwordsalt is missing from your config.php and your config.php is read only. Please fix it manually.');
			}
		}

		$secret = $this->config->getSystemValueString('secret', '');
		if ($secret === '') {
			try {
				$this->config->setSystemValue('secret', $this->random->generate(48));
			} catch (HintException $e) {
				$output->warning('secret is missing from your config.php and your config.php is read only. Please fix it manually.');
			}
		}
	}
}
