<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OC\SetupCheck;

use OC\AppFramework\Bootstrap\Coordinator;
use OCP\Migration\IOutput;
use OCP\Server;
use OCP\SetupCheck\ISetupCheck;
use OCP\SetupCheck\ISetupCheckManager;
use OCP\SetupCheck\SetupResult;
use OCP\Util;
use Psr\Log\LoggerInterface;

class SetupCheckManager implements ISetupCheckManager {
	public function __construct(
		private readonly Coordinator $coordinator,
		private readonly LoggerInterface $logger,
	) {
	}

	#[\Override]
	public function runByClass(string $filterByClass, ?IOutput $output = null): array {
		if (str_starts_with($filterByClass, '\\')) {
			$filterByClass = substr($filterByClass, 1);
		}
		return $this->run(filterByClass: $filterByClass, output: $output);
	}

	#[\Override]
	public function runByCategory(string $filterByCategory, ?IOutput $output = null): array {
		return $this->run(filterByCategory: $filterByCategory, output: $output);
	}

	#[\Override]
	public function runAll(?IOutput $output = null): array {
		return $this->run(output: $output);
	}

	private function run(?string $filterByCategory = null, ?string $filterByClass = null, ?IOutput $output = null): array {
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

			$checkDetails = $setupCheckObject->getName() . ' (' . get_class($setupCheckObject) . ')';
			$message = 'Starting check ' . $checkDetails;
			$output?->debug($message);
			$this->logger->debug($message);

			memory_reset_peak_usage();
			$startTime = microtime(true);
			try {
				$setupResult = $setupCheckObject->run();
			} catch (\Throwable $t) {
				$setupResult = SetupResult::error("An exception occurred while running the setup check:\n$t");
				$this->logger->error('Exception running check ' . get_class($setupCheckObject) . ': ' . $t->getMessage(), ['exception' => $t]);
			}
			$timeSpent = microtime(true) - $startTime;
			$memoryPeak = memory_get_peak_usage();

			$message = 'Check ' . $checkDetails . ' done in ' . number_format($timeSpent, 2) . ' seconds, peak memory usage: ' . Util::humanFileSize($memoryPeak);
			$output?->debug($message);
			$this->logger->debug($message);

			$setupResult->setName($setupCheckObject->getName());
			$category = $setupCheckObject->getCategory();
			$results[$category] ??= [];
			$results[$category][$setupCheckObject::class] = $setupResult;
		}
		return $results;
	}
}
