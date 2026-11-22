<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Install\Events;

use OCP\Install\Events\InstallationCompletedEvent;

class InstallationCompletedEventTest extends \Test\TestCase {
	public function testConstructorWithAllParameters(): void {
		$dataDir = '/path/to/data';
		$adminUsername = 'admin';
		$adminEmail = 'admin@example.com';

		$event = new InstallationCompletedEvent($dataDir, $adminUsername, $adminEmail);

		$this->assertEquals($dataDir, $event->getDataDirectory());
		$this->assertEquals($adminUsername, $event->getAdminUsername());
		$this->assertEquals($adminEmail, $event->getAdminEmail());
		$this->assertTrue($event->hasAdminUser());
	}

	public function testConstructorWithMinimalParameters(): void {
		$dataDir = '/path/to/data';

		$event = new InstallationCompletedEvent($dataDir);

		$this->assertEquals($dataDir, $event->getDataDirectory());
		$this->assertNull($event->getAdminUsername());
		$this->assertNull($event->getAdminEmail());
		$this->assertFalse($event->hasAdminUser());
	}

	public function testConstructorWithUsernameOnly(): void {
		$dataDir = '/path/to/data';
		$adminUsername = 'admin';

		$event = new InstallationCompletedEvent($dataDir, $adminUsername);

		$this->assertEquals($dataDir, $event->getDataDirectory());
		$this->assertEquals($adminUsername, $event->getAdminUsername());
		$this->assertNull($event->getAdminEmail());
		$this->assertTrue($event->hasAdminUser());
	}

	public function testConstructorWithUsernameAndEmail(): void {
		$dataDir = '/path/to/data';
		$adminUsername = 'admin';
		$adminEmail = 'admin@example.com';

		$event = new InstallationCompletedEvent($dataDir, $adminUsername, $adminEmail);

		$this->assertEquals($dataDir, $event->getDataDirectory());
		$this->assertEquals($adminUsername, $event->getAdminUsername());
		$this->assertEquals($adminEmail, $event->getAdminEmail());
		$this->assertTrue($event->hasAdminUser());
	}

	public function testHasAdminUserReturnsFalseWhenUsernameIsNull(): void {
		$event = new InstallationCompletedEvent('/path/to/data', null, 'admin@example.com');

		$this->assertFalse($event->hasAdminUser());
		$this->assertNull($event->getAdminUsername());
		$this->assertEquals('admin@example.com', $event->getAdminEmail());
	}

	public function testDataDirectoryCanBeAnyString(): void {
		$customPath = '/custom/data/directory';
		$event = new InstallationCompletedEvent($customPath);

		$this->assertEquals($customPath, $event->getDataDirectory());
	}

	public function testEmailCanBeSetWithoutUsername(): void {
		$dataDir = '/path/to/data';
		$email = 'admin@example.com';

		$event = new InstallationCompletedEvent($dataDir, null, $email);

		$this->assertNull($event->getAdminUsername());
		$this->assertEquals($email, $event->getAdminEmail());
		$this->assertFalse($event->hasAdminUser());
	}
}
