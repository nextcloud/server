<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OC\SetupCheck;

use OC\AppFramework\Bootstrap\Coordinator;
use OCP\Server;
use OCP\SetupCheck\ISetupCheck;
use OCP\SetupCheck\ISetupCheckManager;
use OCP\SetupCheck\SetupResult;
use Psr\Log\LoggerInterface;

class SetupCheckManager implements ISetupCheckManager {
	public function __construct(
		readonly private Coordinator $coordinator,
		readonly private LoggerInterface $logger,
	) {
	}

	#[\Override]
	public function runByClass(string $filterByClass): array {
		if (str_starts_with($filterByClass, '\\')) {
			$filterByClass = substr($filterByClass, 1);
		}
		return $this->run(filterByClass: $filterByClass);
	}

	#[\Override]
	public function runByCategory(string $filterByCategory): array {
		return $this->run(filterByCategory: $filterByCategory);
	}

	#[\Override]
	public function runAll(): array {
		return $this->run();
	}

	private function run(?string $filterByCategory = null, ?string $filterByClass = null): array {
		$results = [];
		$setupChecks = $this->coordinator->getRegistrationContext()->getSetupChecks();
		foreach ($setupChecks as $setupCheck) {
			/** @var ISetupCheck $setupCheckObject */
			$setupCheckObject = Server::get($setupCheck->getService());
			if ($filterByCategory !== null && $filterByCategory !== $setupCheckObject->getCategory()) {
				continue;
			}

			if ($filterByClass !== null && $filterByClass !== get_class($setupCheckObject)) {
				continue;
			}

			$this->logger->debug('Running check ' . get_class($setupCheckObject));
			try {
				$setupResult = $setupCheckObject->run();
			} catch (\Throwable $t) {
				$setupResult = SetupResult::error("An exception occurred while running the setup check:\n$t");
				$this->logger->error('Exception running check ' . get_class($setupCheckObject) . ': ' . $t->getMessage(), ['exception' => $t]);
			}
			$setupResult->setName($setupCheckObject->getName());
			$category = $setupCheckObject->getCategory();
			$results[$category] ??= [];
			$results[$category][$setupCheckObject::class] = $setupResult;
		}
		return $results;
	}
}
