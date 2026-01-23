<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\OpenMetrics;

use Generator;
use OCP\App\IAppManager;
use OCP\IConfig;
use OCP\OpenMetrics\IMetricFamily;
use OCP\Server;
use Psr\Log\LoggerInterface;

class ExporterManager {
	private array $skippedClasses;
	private const XML_ENTRY = 'openmetrics';

	public function __construct(
		private IAppManager $appManager,
		private LoggerInterface $logger,
		IConfig $config,
	) {
		// Use values as keys for faster lookups
		$this->skippedClasses = array_fill_keys($config->getSystemValue('openmetrics_skipped_classes', []), true);
	}

	public function export(): Generator {
		// Core exporters
		$exporters = [
			// Basic exporters
			Exporters\InstanceInfo::class,
			Exporters\AppsInfo::class,
			Exporters\AppsCount::class,
			Exporters\Maintenance::class,

			// File exporters
			Exporters\FilesByType::class,

			// Users exporters
			Exporters\ActiveUsers::class,
			Exporters\ActiveSessions::class,
			Exporters\UsersByBackend::class,

			// Jobs
			Exporters\RunningJobs::class,
		];
		$exporters = array_filter($exporters, fn ($classname) => !isset($this->skippedClasses[$classname]));
		foreach ($exporters as $classname) {
			$exporter = $this->loadExporter($classname);
			if ($exporter !== null) {
				yield $exporter;
			}
		}

		// Apps exporters
		foreach ($this->appManager->getEnabledApps() as $appId) {
			$appInfo = $this->appManager->getAppInfo($appId);
			if (!isset($appInfo[self::XML_ENTRY]) || !is_array($appInfo[self::XML_ENTRY])) {
				continue;
			}
			foreach ($appInfo[self::XML_ENTRY] as $classEntries) {
				// When multiple exporters are specified, $classEntries will be an array, instead of a string
				$classnames = is_array($classEntries) ? $classEntries : [$classEntries];

				foreach ($classnames as $classname) {
					if (isset($this->skippedClasses[$classname])) {
						continue;
					}
					$exporter = $this->loadExporter($classname, $appId);
					if ($exporter !== null) {
						yield $exporter;
					}
				}
			}
		}
	}

	private function loadExporter(string $classname, string $appId = 'core'): ?IMetricFamily {
		try {
			return Server::get($classname);
		} catch (\Exception $e) {
			$this->logger->error(
				'Unable to build exporter {exporter}',
				[
					'app' => $appId,
					'exception' => $e,
					'exporter' => $classname,
				],
			);
		}

		return null;
	}
}
