<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Migration;

use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;


class RepairStepAppIdDecorator implements IRepairStep {
	public function __construct(
		private readonly string      $appId,
		private readonly IRepairStep $repairStep,
	) {
	}

	public function getName() {
		return $this->repairStep->getName();
	}

	public function run(IOutput $output) {
		return $this->repairStep->run($output);
	}

	public function getAppId(): string {
		return $this->appId;
	}
}
