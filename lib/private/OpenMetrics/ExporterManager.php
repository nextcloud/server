<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\OpenMetrics;

use Generator;
use OC\OpenMetrics\Exporters\ActiveSessions;
use OC\OpenMetrics\Exporters\ActiveUsers;
use OC\OpenMetrics\Exporters\AppsCount;
use OC\OpenMetrics\Exporters\AppsInfo;
use OC\OpenMetrics\Exporters\FilesByType;
use OC\OpenMetrics\Exporters\InstanceInfo;
use OC\OpenMetrics\Exporters\LogLevel;
use OC\OpenMetrics\Exporters\Maintenance;
use OC\OpenMetrics\Exporters\RunningJobs;
use OC\OpenMetrics\Exporters\UsersByBackend;
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
			InstanceInfo::class,
			AppsInfo::class,
			AppsCount::class,
			Maintenance::class,
			LogLevel::class,

			// File exporters
			FilesByType::class,

			// Users exporters
			ActiveUsers::class,
			ActiveSessions::class,
			UsersByBackend::class,

			// Jobs
			RunningJobs::class,
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
			foreach ($appInfo[self::XML_ENTRY] as $classnames) {
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
