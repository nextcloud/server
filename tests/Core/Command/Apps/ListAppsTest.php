<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Tests\Core\Command\Config;

use OC\Core\Command\App\ListApps;
use OCP\App\IAppManager;
use OCP\Server;
use Symfony\Component\Console\Tester\CommandTester;
use Test\TestCase;

/**
 * Class ListAppsTest
 */
#[\PHPUnit\Framework\Attributes\Group('DB')]
class ListAppsTest extends TestCase {
	/** @var CommandTester */
	private $commandTester;

	/** @var IAppManager */
	private $appManager;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->appManager = Server::get(IAppManager::class);
		$command = new ListApps($this->appManager);
		$this->commandTester = new CommandTester($command);

		$this->appManager->disableApp('updatenotification');
	}

	#[\Override]
	protected function tearDown(): void {
		$this->appManager->disableApp('updatenotification');
		parent::tearDown();
	}

	public function testEnabledAppWithoutGroupRestrictionHasNoGroupsKey(): void {
		$this->appManager->enableApp('updatenotification');

		$this->commandTester->execute(['--output' => 'json']);

		$apps = json_decode($this->commandTester->getDisplay(), true);

		$this->assertArrayHasKey('updatenotification', $apps['enabled']);
		$this->assertIsNotArray($apps['enabled']['updatenotification']);
	}

	public function testEnabledAppWithGroupRestrictionShowsGroups(): void {
		$this->appManager->enableAppForGroups('updatenotification', ['admin']);

		$this->commandTester->execute(['--output' => 'json']);

		$apps = json_decode($this->commandTester->getDisplay(), true);

		$this->assertArrayHasKey('updatenotification', $apps['enabled']);
		$this->assertIsArray($apps['enabled']['updatenotification']);
		$this->assertSame(['admin'], $apps['enabled']['updatenotification']['groups']);
	}
}
