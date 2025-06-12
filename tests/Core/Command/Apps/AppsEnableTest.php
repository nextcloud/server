<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Tests\Core\Command\Config;

use OC\Core\Command\App\Enable;
use OC\Installer;
use OCP\App\IAppManager;
use OCP\IGroupManager;
use OCP\Server;
use Symfony\Component\Console\Tester\CommandTester;
use Test\TestCase;

/**
 * Class AppsEnableTest
 *
 * @group DB
 */
class AppsEnableTest extends TestCase {
	/** @var CommandTester */
	private $commandTester;

	protected function setUp(): void {
		parent::setUp();

		$command = new Enable(
			Server::get(IAppManager::class),
			Server::get(IGroupManager::class),
			Server::get(Installer::class),
		);

		$this->commandTester = new CommandTester($command);

		Server::get(IAppManager::class)->disableApp('admin_audit');
		Server::get(IAppManager::class)->disableApp('comments');
	}

	/**
	 * @dataProvider dataCommandInput
	 * @param $appId
	 * @param $groups
	 * @param $statusCode
	 * @param $pattern
	 */
	public function testCommandInput($appId, $groups, $statusCode, $pattern): void {
		$input = ['app-id' => $appId];

		if (is_array($groups)) {
			$input['--groups'] = $groups;
		}

		$this->commandTester->execute($input);

		$this->assertMatchesRegularExpression('/' . $pattern . '/', $this->commandTester->getDisplay());
		$this->assertSame($statusCode, $this->commandTester->getStatusCode());
	}

	public static function dataCommandInput(): array {
		return [
			[['admin_audit'], null, 0, 'admin_audit ([\d\.]*) enabled'],
			[['comments'], null, 0, 'comments ([\d\.]*) enabled'],
			[['comments', 'comments'], null, 0, "comments ([\d\.]*) enabled\ncomments already enabled"],
			[['invalid_app'], null, 1, 'Could not download app invalid_app'],

			[['admin_audit', 'comments'], null, 0, "admin_audit ([\d\.]*) enabled\ncomments ([\d\.]*) enabled"],
			[['admin_audit', 'comments', 'invalid_app'], null, 1, "admin_audit ([\d\.]*) enabled\ncomments ([\d\.]*) enabled\nCould not download app invalid_app"],

			[['admin_audit'], ['admin'], 1, "admin_audit can't be enabled for groups"],
			[['comments'], ['admin'], 1, "comments can't be enabled for groups"],

			[['updatenotification'], ['admin'], 0, 'updatenotification ([\d\.]*) enabled for groups: admin'],
			[['updatenotification', 'dashboard'], ['admin'], 0, "updatenotification ([\d\.]*) enabled for groups: admin\ndashboard ([\d\.]*) enabled for groups: admin"],

			[['updatenotification'], ['admin', 'invalid_group'], 0, 'updatenotification ([\d\.]*) enabled for groups: admin'],
			[['updatenotification', 'dashboard'], ['admin', 'invalid_group'], 0, "updatenotification ([\d\.]*) enabled for groups: admin\ndashboard ([\d\.]*) enabled for groups: admin"],
			[['updatenotification', 'dashboard', 'invalid_app'], ['admin', 'invalid_group'], 1, "updatenotification ([\d\.]*) enabled for groups: admin\ndashboard ([\d\.]*) enabled for groups: admin\nCould not download app invalid_app"],
		];
	}
}
