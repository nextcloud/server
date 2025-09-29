<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Tests\unit\Migration;

use OCA\DAV\CardDAV\SyncService;
use OCA\DAV\Migration\CreateSystemAddressBookStep;
use OCP\Migration\IOutput;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CreateSystemAddressBookStepTest extends TestCase {

	private SyncService&MockObject $syncService;
	private CreateSystemAddressBookStep $step;

	protected function setUp(): void {
		parent::setUp();

		$this->syncService = $this->createMock(SyncService::class);

		$this->step = new CreateSystemAddressBookStep(
			$this->syncService,
		);
	}

	public function testGetName(): void {
		$name = $this->step->getName();

		self::assertEquals('Create system address book', $name);
	}

	public function testRun(): void {
		$output = $this->createMock(IOutput::class);

		$this->step->run($output);

		$this->addToAssertionCount(1);
	}

}
