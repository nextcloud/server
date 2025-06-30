<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Tests\Core\Command\Config;

use OC\Core\Command\App\Disable;
use OCP\App\IAppManager;
use OCP\Server;
use Symfony\Component\Console\Tester\CommandTester;
use Test\TestCase;

/**
 * Class AppsDisableTest
 *
 * @group DB
 */
class AppsDisableTest extends TestCase {
	/** @var CommandTester */
	private $commandTester;

	protected function setUp(): void {
		parent::setUp();

		$command = new Disable(
			Server::get(IAppManager::class)
		);

		$this->commandTester = new CommandTester($command);

		Server::get(IAppManager::class)->enableApp('admin_audit');
		Server::get(IAppManager::class)->enableApp('comments');
	}

	/**
	 * @dataProvider dataCommandInput
	 * @param $appId
	 * @param $groups
	 * @param $statusCode
	 * @param $pattern
	 */
	public function testCommandInput($appId, $statusCode, $pattern): void {
		$input = ['app-id' => $appId];

		$this->commandTester->execute($input);

		$this->assertMatchesRegularExpression('/' . $pattern . '/', $this->commandTester->getDisplay());
		$this->assertSame($statusCode, $this->commandTester->getStatusCode());
	}

	public static function dataCommandInput(): array {
		return [
			[['admin_audit'], 0, 'admin_audit ([\d\.]*) disabled'],
			[['comments'], 0, 'comments ([\d\.]*) disabled'],
			[['invalid_app'], 0, 'No such app enabled: invalid_app'],

			[['admin_audit', 'comments'], 0, "admin_audit ([\d\.]*) disabled\ncomments ([\d\.]*) disabled"],
			[['admin_audit', 'comments', 'invalid_app'], 0, "admin_audit ([\d\.]*) disabled\ncomments ([\d\.]*) disabled\nNo such app enabled: invalid_app"],

			[['files'], 2, "files can't be disabled"],
			[['provisioning_api'], 2, "provisioning_api can't be disabled"],

			[['files', 'admin_audit'], 2, "files can't be disabled.\nadmin_audit ([\d\.]*) disabled"],
			[['provisioning_api', 'comments'], 2, "provisioning_api can't be disabled.\ncomments ([\d\.]*) disabled"],
		];
	}
}
