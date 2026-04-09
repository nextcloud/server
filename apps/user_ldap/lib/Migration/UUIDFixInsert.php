<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\User_LDAP\Migration;

use OCA\User_LDAP\Mapping\GroupMapping;
use OCA\User_LDAP\Mapping\UserMapping;
use OCP\AppFramework\Services\IAppConfig;
use OCP\BackgroundJob\IJobList;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class UUIDFixInsert implements IRepairStep {

	public function __construct(
		protected IAppConfig $appConfig,
		protected UserMapping $userMapper,
		protected GroupMapping $groupMapper,
		protected IJobList $jobList,
	) {
	}

	public function getName(): string {
		return 'Insert UUIDFix background job for user and group in batches';
	}

	public function run(IOutput $output): void {
		$installedVersion = $this->appConfig->getAppValueString('installed_version', '1.2.1');
		if (version_compare($installedVersion, '1.2.1') !== -1) {
			return;
		}

		foreach ([$this->userMapper, $this->groupMapper] as $mapper) {
			$offset = 0;
			$batchSize = 50;
			$jobClass = $mapper instanceof UserMapping ? UUIDFixUser::class : UUIDFixGroup::class;
			do {
				$retry = false;
				$records = $mapper->getList($offset, $batchSize);
				if (count($records) === 0) {
					continue;
				}
				try {
					$this->jobList->add($jobClass, ['records' => $records]);
					$offset += $batchSize;
				} catch (\InvalidArgumentException $e) {
					if (str_contains($e->getMessage(), 'Background job arguments can\'t exceed 4000')) {
						$batchSize = (int)floor(count($records) * 0.8);
						$retry = true;
					}
				}
			} while (count($records) === $batchSize || $retry);
		}
	}
}
