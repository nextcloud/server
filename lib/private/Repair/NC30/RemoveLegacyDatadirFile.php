<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Repair\NC30;

use OCP\IConfig;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class RemoveLegacyDatadirFile implements IRepairStep {

	public function __construct(
		private IConfig $config,
	) {
	}

	public function getName(): string {
		return 'Remove legacy ".ocdata" file';
	}

	public function run(IOutput $output): void {
		$ocdata = $this->config->getSystemValueString('datadirectory', \OC::$SERVERROOT . '/data') . '/.ocdata';
		if (file_exists($ocdata)) {
			unlink($ocdata);
		}
	}
}
