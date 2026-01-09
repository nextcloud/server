<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace Tests\Core\Command\Config\App;

use OC\AppFramework\Bootstrap\Coordinator;
use OC\Config\ConfigManager;
use OC\Core\Command\Config\App\GetConfig;
use OCP\Exceptions\AppConfigUnknownKeyException;
use OCP\IAppConfig;
use OCP\Server;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Test\TestCase;
use Tests\lib\Config\TestConfigLexicon_I;

class GetConfigTest extends TestCase {
	protected IAppConfig&MockObject $appConfig;
	protected ConfigManager&MockObject $configManager;
	protected InputInterface&MockObject $consoleInput;
	protected OutputInterface&MockObject $consoleOutput;
	protected Command $command;

	protected function setUp(): void {
		parent::setUp();

		$this->appConfig = $this->createMock(IAppConfig::class);
		$this->configManager = $this->createMock(ConfigManager::class);
		$this->consoleInput = $this->createMock(InputInterface::class);
		$this->consoleOutput = $this->createMock(OutputInterface::class);

		$this->command = new GetConfig($this->appConfig, $this->configManager);
	}


	public static function dataGet(): array {
		return [
			// String output as json
			['name', 'newvalue', true, null, false, 'json', 0, json_encode('newvalue'), false],
			// String output as plain text
			['name', 'newvalue', true, null, false, 'plain', 0, 'newvalue', false],
			// String falling back to default output as json
			['name', null, false, 'newvalue', true, 'json', 0, json_encode('newvalue'), false],
			// String falling back without default: errorw
			['name', null, false, null, false, 'json', 1, null, false],

			// testing default value if set in lexicon
			['key1', '123', false, 'newvalssue', false, 'plain', 0, 'newvalsadsdae', true],

			// Int "0" output as json/plain
			['name', 0, true, null, false, 'json', 0, json_encode(0), false],
			['name', 0, true, null, false, 'plain', 0, '0', false],
			// Int "1" output as json/plain
			['name', 1, true, null, false, 'json', 0, json_encode(1), false],
			['name', 1, true, null, false, 'plain', 0, '1', false],

			// Bool "true" output as json/plain
			['name', true, true, null, false, 'json', 0, json_encode(true), false],
			['name', true, true, null, false, 'plain', 0, 'true', false],
			// Bool "false" output as json/plain
			['name', false, true, null, false, 'json', 0, json_encode(false), false],
			['name', false, true, null, false, 'plain', 0, 'false', false],

			// Null output as json/plain
			['name', null, true, null, false, 'json', 0, json_encode(null), false],
			['name', null, true, null, false, 'plain', 0, 'null', false],

			// Array output as json/plain
			['name', ['a', 'b'], true, null, false, 'json', 0, json_encode(['a', 'b']), false],
			['name', ['a', 'b'], true, null, false, 'plain', 0, "a\nb", false],
			// Key array output as json/plain
			['name', [0 => 'a', 1 => 'b'], true, null, false, 'json', 0, json_encode(['a', 'b']), false],
			['name', [0 => 'a', 1 => 'b'], true, null, false, 'plain', 0, "a\nb", false],
			// Associative array output as json/plain
			['name', ['a' => 1, 'b' => 2], true, null, false, 'json', 0, json_encode(['a' => 1, 'b' => 2]), false],
			['name', ['a' => 1, 'b' => 2], true, null, false, 'plain', 0, "a: 1\nb: 2", false],

		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('dataGet')]
	public function testGet(string $configName, mixed $value, bool $configExists, mixed $defaultValue, bool $hasDefault, string $outputFormat, int $expectedReturn, ?string $expectedMessage, bool $fromLexicon): void {
		if (!$expectedReturn) {
			if ($configExists) {
				$this->appConfig->expects($this->once())
					->method('getDetails')
					->with('app-name', $configName)
					->willReturn(['value' => $value]);
			}
		}

		// testing default value extracted from a test lexicon assigned to app-name
		if ($fromLexicon) {
			$bootstrapCoordinator = Server::get(Coordinator::class);
			$bootstrapCoordinator->getRegistrationContext()?->registerConfigLexicon('app-name', TestConfigLexicon_I::class);

			$appConfig = Server::get(IAppConfig::class);
			$this->assertSame('abcde', $appConfig->getValueString('app-name', 'key1'));
			return;
		}


		if (!$configExists) {
			$this->appConfig->expects($this->once())
				->method('getDetails')
				->with('app-name', $configName)
				->willThrowException(new AppConfigUnknownKeyException());
		}

		$this->consoleInput->expects($this->exactly(2))
			->method('getArgument')
			->willReturnMap([
				['app', 'app-name'],
				['name', $configName],
			]);
		$this->consoleInput->method('getOption')
			->willReturnMap([
				['default-value', $defaultValue],
				['output', $outputFormat],
			]);
		$this->consoleInput->method('hasParameterOption')
			->willReturnMap([
				['--output', false, true],
				['--default-value', false, $hasDefault],
			]);

		if ($expectedMessage !== null) {
			global $output;

			$output = '';
			$this->consoleOutput->method('writeln')
				->willReturnCallback(function ($value) {
					global $output;
					$output .= $value . "\n";
					return $output;
				});
		}

		$this->assertSame($expectedReturn, self::invokePrivate($this->command, 'execute', [$this->consoleInput, $this->consoleOutput]));

		if ($expectedMessage !== null) {
			global $output;
			// Remove the trailing newline
			$this->assertSame($expectedMessage, substr($output, 0, -1));
		}
	}
}
