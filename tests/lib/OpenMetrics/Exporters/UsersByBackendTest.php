<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\OpenMetrics\Exporters;

use OC\OpenMetrics\Exporters\UsersByBackend;
use OCP\IUserManager;
use OCP\OpenMetrics\IMetricFamily;
use PHPUnit\Framework\MockObject\MockObject;

class UsersByBackendTest extends ExporterTestCase {
	private IUserManager&MockObject $userManager;
	private array $backendList = [
		'backend A' => 42,
		'backend B' => 51,
		'backend C' => 0,
	];


	protected function getExporter():IMetricFamily {
		$this->userManager = $this->createMock(IUserManager::class);
		$this->userManager->method('countUsers')
			->with(true)
			->willReturn($this->backendList);
		return new UsersByBackend($this->userManager);
	}

	public function testMetrics(): void {
		foreach ($this->metrics as $metric) {
			$this->assertEquals($this->backendList[$metric->label('backend')], $metric->value);
		}
	}
}
