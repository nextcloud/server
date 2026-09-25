<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Repair\NC29;

use OCP\BackgroundJob\IJobList;
use OCP\Migration\IOutput;
use Test\TestCase;

class SanitizeAccountPropertiesTest extends TestCase {

	private SanitizeAccountProperties $repairStep;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->repairStep = $this->createInstanceWithMocks(SanitizeAccountProperties::class);
	}

	public function testGetName(): void {
		self::assertStringContainsString('Validate account properties', $this->repairStep->getName());
	}

	public function testRun(): void {
		$this->mocks[IJobList::class]->expects(self::once())
			->method('add')
			->with(SanitizeAccountPropertiesJob::class, null);

		$output = $this->createMock(IOutput::class);
		$output->expects(self::once())
			->method('info')
			->with(self::matchesRegularExpression('/queued background/i'));

		$this->repairStep->run($output);
	}
}
