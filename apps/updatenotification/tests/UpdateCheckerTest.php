<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\UpdateNotification\Tests;

use OC\Updater\ChangesCheck;
use OC\Updater\VersionCheck;
use OCA\UpdateNotification\UpdateChecker;
use OCP\AppFramework\Services\IInitialState;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class UpdateCheckerTest extends TestCase {
	
	private ChangesCheck&MockObject $changesChecker;
	private VersionCheck&MockObject $updater;
	private IInitialState&MockObject $initialState;
	private UpdateChecker $updateChecker;

	protected function setUp(): void {
		parent::setUp();

		$this->updater = $this->createMock(VersionCheck::class);
		$this->changesChecker = $this->createMock(ChangesCheck::class);
		$this->initialState = $this->createMock(IInitialState::class);
		$this->updateChecker = new UpdateChecker(
			$this->updater,
			$this->changesChecker,
			$this->initialState,
		);
	}

	public function testGetUpdateStateWithUpdateAndInvalidLink(): void {
		$this->updater
			->expects($this->once())
			->method('check')
			->willReturn([
				'version' => '1.2.3',
				'versionstring' => 'Nextcloud 1.2.3',
				'web' => 'javascript:alert(1)',
				'url' => 'javascript:alert(2)',
				'changes' => 'javascript:alert(3)',
				'autoupdater' => '0',
				'eol' => '1',
			]);

		$expected = [
			'updateAvailable' => true,
			'updateVersion' => '1.2.3',
			'updateVersionString' => 'Nextcloud 1.2.3',
			'updaterEnabled' => false,
			'versionIsEol' => true,
		];
		$this->assertSame($expected, $this->updateChecker->getUpdateState());
	}

	public function testGetUpdateStateWithUpdateAndValidLink(): void {
		$changes = [
			'changelog' => 'https://nextcloud.com/changelog/#123-0-0',
			'whatsNew' => [
				'en' => [
					'regular' => [
						'Yardarm heave to brig spyglass smartly pillage',
						'Bounty gangway bilge skysail rope\'s end',
						'Maroon cutlass spirits nipperkin Plate Fleet',
					],
					'admin' => [
						'Scourge of the seven seas coffer doubloon',
						'Brig me splice the main brace',
					]
				]
			]
		];

		$this->updater
			->expects($this->once())
			->method('check')
			->willReturn([
				'version' => '1.2.3',
				'versionstring' => 'Nextcloud 1.2.3',
				'web' => 'https://docs.nextcloud.com/myUrl',
				'url' => 'https://downloads.nextcloud.org/server',
				'changes' => 'https://updates.nextcloud.com/changelog_server/?version=123.0.0',
				'autoupdater' => '1',
				'eol' => '0',
			]);

		$this->changesChecker->expects($this->once())
			->method('check')
			->willReturn($changes);

		$expected = [
			'updateAvailable' => true,
			'updateVersion' => '1.2.3',
			'updateVersionString' => 'Nextcloud 1.2.3',
			'updaterEnabled' => true,
			'versionIsEol' => false,
			'updateLink' => 'https://docs.nextcloud.com/myUrl',
			'downloadLink' => 'https://downloads.nextcloud.org/server',
			'changes' => $changes,
		];
		$this->assertSame($expected, $this->updateChecker->getUpdateState());
	}

	public function testGetUpdateStateWithoutUpdate(): void {
		$this->updater
			->expects($this->once())
			->method('check')
			->willReturn([]);

		$expected = [];
		$this->assertSame($expected, $this->updateChecker->getUpdateState());
	}

	public function testSetInitialState(): void {
		$this->updater
			->expects($this->once())
			->method('check')
			->willReturn([
				'version' => '1.2.3',
				'versionstring' => 'Nextcloud 1.2.3',
				'web' => 'https://docs.nextcloud.com/myUrl',
				'url' => 'https://downloads.nextcloud.org/server',
				'changes' => 'https://updates.nextcloud.com/changelog_server/?version=123.0.0',
				'autoupdater' => '1',
				'eol' => '0',
			]);

		$this->initialState->expects(self::once())
			->method('provideInitialState')
			->with('updateState', [
				'updateVersion' => 'Nextcloud 1.2.3',
				'updateLink' => 'https://docs.nextcloud.com/myUrl',
			]);

		$this->updateChecker->setInitialState();
	}
}
