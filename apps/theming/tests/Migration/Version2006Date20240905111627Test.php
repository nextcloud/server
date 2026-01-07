<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Theming\Tests\Migration;

use OCA\Theming\Migration\Version2006Date20240905111627;
use OCP\BackgroundJob\IJobList;
use OCP\Config\IUserConfig;
use OCP\DB\ISchemaWrapper;
use OCP\IAppConfig;
use OCP\IDBConnection;
use OCP\IUserManager;
use OCP\Migration\IOutput;
use OCP\Server;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

#[\PHPUnit\Framework\Attributes\Group(name: 'DB')]
class Version2006Date20240905111627Test extends TestCase {

	private IAppConfig&MockObject $appConfig;
	private IDBConnection&MockObject $connection;
	private IJobList&MockObject $jobList;
	private Version2006Date20240905111627 $migration;

	protected function setUp(): void {
		parent::setUp();

		$this->appConfig = $this->createMock(IAppConfig::class);
		$this->connection = $this->createMock(IDBConnection::class);
		$this->jobList = $this->createMock(IJobList::class);
		$this->migration = new Version2006Date20240905111627(
			$this->jobList,
			$this->appConfig,
			$this->connection,
		);
	}

	public function testRestoreSystemColors(): void {
		$this->appConfig->expects(self::once())
			->method('getValueString')
			->with('theming', 'color', '')
			->willReturn('ffab00');
		$this->appConfig->expects(self::once())
			->method('getValueBool')
			->with('theming', 'disable-user-theming')
			->willReturn(true);

		// expect the color value to be deleted
		$this->appConfig->expects(self::once())
			->method('deleteKey')
			->with('theming', 'color');
		// expect the correct calls to setValueString (setting the new values)
		$setValueCalls = [];
		$this->appConfig->expects(self::exactly(2))
			->method('setValueString')
			->willReturnCallback(function () use (&$setValueCalls) {
				$setValueCalls[] = func_get_args();
				return true;
			});

		$output = $this->createMock(IOutput::class);
		$this->migration->changeSchema($output, fn () => $this->createMock(ISchemaWrapper::class), []);

		$this->assertEquals([
			['theming', 'background_color', 'ffab00', false, false],
			['theming', 'primary_color', 'ffab00', false, false],
		], $setValueCalls);
	}

	#[\PHPUnit\Framework\Attributes\Group(name: 'DB')]
	public function testRestoreUserColors(): void {
		$this->appConfig->expects(self::once())
			->method('getValueString')
			->with('theming', 'color', '')
			->willReturn('');
		$this->appConfig->expects(self::once())
			->method('getValueBool')
			->with('theming', 'disable-user-theming')
			->willReturn(false);

		// Create a user
		$manager = Server::get(IUserManager::class);
		$user = $manager->createUser('theming_legacy', 'theming_legacy');
		self::assertNotFalse($user);
		// Set the users theming value to legacy key
		$config = Server::get(IUserConfig::class);
		$config->setValueString('theming_legacy', 'theming', 'background_color', 'ffab00');

		// expect some output
		$output = $this->createMock(IOutput::class);
		$output->expects(self::exactly(3))
			->method('info')
			->willReturnCallback(fn ($txt) => match($txt) {
				'No custom system color configured - skipping' => true,
				'Restoring user primary color' => true,
				'Primary color of users restored' => true,
				default => self::fail('output.info called with unexpected argument: ' . $txt)
			});
		// Create the migration class
		$migration = new Version2006Date20240905111627(
			$this->jobList,
			$this->appConfig,
			Server::get(IDBConnection::class),
		);
		// Run the migration
		$migration->changeSchema($output, fn () => $this->createMock(ISchemaWrapper::class), []);

		// See new value
		$config->clearCache('theming_legacy');
		$newValue = $config->getValueString('theming_legacy', 'theming', 'primary_color');
		self::assertEquals('ffab00', $newValue);

		// cleanup
		$user->delete();
	}

	/**
	 * Ensure only users with background color but no primary color are migrated
	 */
	#[\PHPUnit\Framework\Attributes\Group(name: 'DB')]
	public function testRestoreUserColorsWithConflicts(): void {
		$this->appConfig->expects(self::once())
			->method('getValueString')
			->with('theming', 'color', '')
			->willReturn('');
		$this->appConfig->expects(self::once())
			->method('getValueBool')
			->with('theming', 'disable-user-theming')
			->willReturn(false);

		// Create a user
		$manager = Server::get(IUserManager::class);
		$legacyUser = $manager->createUser('theming_legacy', 'theming_legacy');
		self::assertNotFalse($legacyUser);
		$user = $manager->createUser('theming_no_legacy', 'theming_no_legacy');
		self::assertNotFalse($user);
		// Set the users theming value to legacy key
		$config = Server::get(IUserConfig::class);
		$config->setValueString($user->getUID(), 'theming', 'primary_color', '999999');
		$config->setValueString($user->getUID(), 'theming', 'background_color', '111111');
		$config->setValueString($legacyUser->getUID(), 'theming', 'background_color', 'ffab00');

		// expect some output
		$output = $this->createMock(IOutput::class);
		$output->expects(self::exactly(3))
			->method('info')
			->willReturnCallback(fn ($txt) => match($txt) {
				'No custom system color configured - skipping' => true,
				'Restoring user primary color' => true,
				'Primary color of users restored' => true,
				default => self::fail('output.info called with unexpected argument: ' . $txt)
			});
		// Create the migration class
		$migration = new Version2006Date20240905111627(
			$this->jobList,
			$this->appConfig,
			Server::get(IDBConnection::class),
		);
		// Run the migration
		$migration->changeSchema($output, fn () => $this->createMock(ISchemaWrapper::class), []);

		// See new value of only the legacy user
		$config->clearCacheAll();
		self::assertEquals('111111', $config->getValueString($user->getUID(), 'theming', 'background_color'));
		self::assertEquals('999999', $config->getValueString($user->getUID(), 'theming', 'primary_color'));
		self::assertEquals('ffab00', $config->getValueString($legacyUser->getUID(), 'theming', 'primary_color'));

		// cleanup
		$legacyUser->delete();
		$user->delete();
	}
}
