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
		private Coordinator $coordinator,
		private LoggerInterface $logger,
	) {
	}
	
	public function runClass(string $limitClass): array {
		if (str_starts_with($limitClass, '\\')) {
			$limitClass = substr($limitClass, 1);
		}
		return $this->run($limitClass);
	}
	
	public function runCategory(string $limitCategory): array {
		return $this->run($limitCategory);
	}
	
	public function runAll(): array {
		return $this->run();
	}
	
	private function run(?string $limit = null): array {
		$results = [];
		$setupChecks = $this->coordinator->getRegistrationContext()->getSetupChecks();
		foreach ($setupChecks as $setupCheck) {
			/** @var ISetupCheck $setupCheckObject */
			$setupCheckObject = Server::get($setupCheck->getService());
			if (isset($limit) && $limit !== $setupCheckObject->getCategory() && $limit !== get_class($setupCheckObject)) {
				continue;
			}
			$this->logger->debug('Running check ' . get_class($setupCheckObject));
			try {
				$setupResult = $setupCheckObject->run();
			} catch (\Throwable $t) {
				$setupResult = SetupResult::error("An exception occured while running the setup check:\n$t");
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
