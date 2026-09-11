<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\SystemReport;

use OC\AppFramework\Bootstrap\Coordinator;
use OCP\SystemReport\ISystemReportManager;
use OCP\SystemReport\ISystemReportSection;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

final readonly class SystemReportManager implements ISystemReportManager {
	public function __construct(
		private Coordinator $coordinator,
		private ContainerInterface $container,
		private LoggerInterface $logger,
	) {
	}

	/**
	 * @return ISystemReportSection[]
	 */
	#[\Override]
	public function getSections(): array {
		$sections = [];
		$registrations = $this->coordinator->getRegistrationContext()?->getSystemReportSections() ?? [];
		foreach ($registrations as $registration) {
			$class = $registration->getService();
			try {
				/** @var ISystemReportSection $section */
				$section = $this->container->get($class);
				// Trigger detail collection here so a failing section is skipped
				// instead of surfacing later when the report is rendered.
				$section->getDetails();
			} catch (\Throwable $t) {
				$this->logger->error('Exception while collecting system report section ' . $class . ': ' . $t->getMessage(), ['exception' => $t]);
				continue;
			}

			$sections[] = $section;
		}

		return $sections;
	}
}
