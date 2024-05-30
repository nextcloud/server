<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Repair\NC18;

use OCP\IConfig;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class ResetGeneratedAvatarFlag implements IRepairStep {
	/** @var IConfig */
	private $config;
	/** @var IDBConnection */
	private $connection;

	public function __construct(IConfig $config,
		IDBConnection $connection) {
		$this->config = $config;
		$this->connection = $connection;
	}

	public function getName(): string {
		return 'Reset generated avatar flag';
	}

	private function shouldRun(): bool {
		$versionFromBeforeUpdate = $this->config->getSystemValueString('version', '0.0.0.0');
		return version_compare($versionFromBeforeUpdate, '18.0.0.5', '<=');
	}

	public function run(IOutput $output): void {
		if ($this->shouldRun()) {
			$query = $this->connection->getQueryBuilder();
			$query->delete('preferences')
				->where($query->expr()->eq('appid', $query->createNamedParameter('avatar')))
				->andWhere($query->expr()->eq('configkey', $query->createNamedParameter('generated')));
		}
	}
}
