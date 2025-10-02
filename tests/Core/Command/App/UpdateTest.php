<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Tests\Core\Command\App;

use OC\Core\Command\App\Update;
use OC\Installer;
use OCP\App\IAppManager;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Test\TestCase;

class UpdateTest extends TestCase {
	private IAppManager&MockObject $appManager;
	private Installer&MockObject $installer;
	private LoggerInterface&MockObject $logger;
	private InputInterface&MockObject $inputInterface;
	private OutputInterface&MockObject $outputInterface;

	protected Update $command;

	protected function setUp(): void {
		parent::setUp();

		$this->appManager = $this->createMock(IAppManager::class);
		$this->installer = $this->createMock(Installer::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->inputInterface = $this->createMock(InputInterface::class);
		$this->outputInterface = $this->createMock(OutputInterface::class);

		$this->command = new Update(
			$this->appManager,
			$this->installer,
			$this->logger,
		);
	}

	public function testAppUpdateNoUpdatesFound(): void {
		$this->inputInterface->expects(self::once())
			->method('getArgument')
			->with('app-id')
			->willReturn('fakeid');

		$this->outputInterface->expects(self::once())
			->method('writeln')
			->with('fakeid is up-to-date or no updates could be found');

		$this->assertEquals(0, self::invokePrivate($this->command, 'execute', [$this->inputInterface, $this->outputInterface]));
	}

	public function testAppUpdate(): void {
		$this->inputInterface->expects(self::once())
			->method('getArgument')
			->with('app-id')
			->willReturn('fakeid');
		$this->inputInterface->expects(self::any())
			->method('getOption')
			->willReturnMap([
				['allow-unstable', false],
				['showonly', false],
			]);

		$this->installer->expects(self::once())
			->method('isUpdateAvailable')
			->willReturn('fakeversion');
		$this->installer->expects(self::once())
			->method('updateAppstoreApp')
			->with('fakeid', false)
			->willReturn(true);

		$output = [];
		$this->outputInterface->expects(self::any())
			->method('writeln')
			->willReturnCallback(
				function (string $line) use (&$output) {
					$output[] = $line;
				}
			);

		$this->assertEquals(0, self::invokePrivate($this->command, 'execute', [$this->inputInterface, $this->outputInterface]));
		$this->assertEquals(
			[
				'fakeid new version available: fakeversion',
				'fakeid updated',
			],
			$output
		);
	}

	public function testAppUpdateWithNewMethod(): void {
		// First create new apps path
		$apps_dirname = \sha1(\random_bytes(4));
		$new_apps_path = \sys_get_temp_dir() . '/' . $apps_dirname;
		// Create an app within that path
		mkdir($new_apps_path . '/test_app/lib', recursive:true);
		file_put_contents(
			$new_apps_path . '/test_app/lib/MyClass.php',
			'<?php namespace OCA\FakeApp; class MyClass { public function __construct(private string $oldParam) {} }'
		);

		// Add the app path
		\OC::$APPSROOTS[] = [
			'path' => $new_apps_path,
			// 'url' => '/apptest',
			'writable' => false,
		];

		// Load the class as if as the app had been booted
		require_once($new_apps_path . '/test_app/lib/MyClass.php');
		$object = new \OCA\FakeApp\MyClass('oldParam');

		// Now, let’s update the app
		$this->inputInterface->expects(self::once())
			->method('getArgument')
			->with('app-id')
			->willReturn('test_app');
		$this->inputInterface->expects(self::any())
			->method('getOption')
			->willReturnMap([
				['allow-unstable', false],
				['showonly', false],
			]);

		$this->installer->expects(self::once())
			->method('isUpdateAvailable')
			->willReturn('2.0.0');
		$this->installer->expects(self::once())
			->method('updateAppstoreApp')
			->willReturnCallback(
				function (string $appid, bool $allowUnstable) use ($new_apps_path): bool {
					file_put_contents(
						$new_apps_path . '/test_app/lib/MyClass.php',
						'<?php namespace OCA\FakeApp; class MyClass { public function __construct() {} }'
					);
					file_put_contents(
						$new_apps_path . '/test_app/lib/MyMigration.php',
						'<?php require_once __DIR__."/MyClass.php"; $object = new \OCA\FakeApp\MyClass(); // no param needed now'
					);
					require_once $new_apps_path . '/test_app/lib/MyMigration.php';
					return true;
				}
			);

		$this->assertEquals(0, self::invokePrivate($this->command, 'execute', [$this->inputInterface, $this->outputInterface]));
	}
}
