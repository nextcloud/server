<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test;

use OC\Log;
use OC\SystemConfig;
use OCP\ILogger;
use OCP\IUser;
use OCP\IUserSession;
use OCP\Log\IWriter;
use OCP\Support\CrashReport\IRegistry;
use PHPUnit\Framework\MockObject\MockObject;

class LoggerTest extends TestCase implements IWriter {
	private SystemConfig&MockObject $config;

	private IRegistry&MockObject $registry;

	private Log $logger;

	/** @var array */
	private array $logs = [];

	protected function setUp(): void {
		parent::setUp();

		$this->logs = [];
		$this->config = $this->createMock(SystemConfig::class);
		$this->registry = $this->createMock(IRegistry::class);
		$this->logger = new Log($this, $this->config, crashReporters: $this->registry);
	}

	private function mockDefaultLogLevel(): void {
		$this->config->expects($this->any())
			->method('getValue')
			->willReturnMap([
				['loglevel', ILogger::WARN, ILogger::WARN],
			]);
	}

	public function testInterpolation(): void {
		$this->mockDefaultLogLevel();
		$logger = $this->logger;
		$logger->warning('{Message {nothing} {user} {foo.bar} a}', ['user' => 'Bob', 'foo.bar' => 'Bar']);

		$expected = ['2 {Message {nothing} Bob Bar a}'];
		$this->assertEquals($expected, $this->getLogs());
	}

	public function testAppCondition(): void {
		$this->config->expects($this->any())
			->method('getValue')
			->willReturnMap([
				['loglevel', ILogger::WARN, ILogger::WARN],
				['log.condition', [], ['apps' => ['files']]]
			]);
		$logger = $this->logger;

		$logger->info('Don\'t display info messages');
		$logger->info('Show info messages of files app', ['app' => 'files']);
		$logger->warning('Show warning messages of other apps');

		$expected = [
			'1 Show info messages of files app',
			'2 Show warning messages of other apps',
		];
		$this->assertEquals($expected, $this->getLogs());
	}

	public static function dataMatchesCondition(): array {
		return [
			[
				'user0',
				[
					'apps' => ['app2'],
				],
				[
					'1 Info of app2',
				],
			],
			[
				'user2',
				[
					'users' => ['user1', 'user2'],
					'apps' => ['app1'],
				],
				[
					'1 Info of app1',
				],
			],
			[
				'user3',
				[
					'users' => ['user3'],
				],
				[
					'1 Info without app',
					'1 Info of app1',
					'1 Info of app2',
					'0 Debug of app3',
				],
			],
			[
				'user4',
				[
					'users' => ['user4'],
					'apps' => ['app3'],
					'loglevel' => 0,
				],
				[
					'0 Debug of app3',
				],
			],
			[
				'user4',
				[
					'message' => ' of ',
				],
				[
					'1 Info of app1',
					'1 Info of app2',
					'0 Debug of app3',
				],
			],
		];
	}

	/**
	 * @dataProvider dataMatchesCondition
	 */
	public function testMatchesCondition(string $userId, array $conditions, array $expectedLogs): void {
		$this->config->expects($this->any())
			->method('getValue')
			->willReturnMap([
				['loglevel', ILogger::WARN, ILogger::WARN],
				['log.condition', [], ['matches' => [
					$conditions,
				]]],
			]);
		$logger = $this->logger;

		$user = $this->createMock(IUser::class);
		$user->method('getUID')
			->willReturn($userId);
		$userSession = $this->createMock(IUserSession::class);
		$userSession->method('getUser')
			->willReturn($user);
		$this->overwriteService(IUserSession::class, $userSession);

		$logger->info('Info without app');
		$logger->info('Info of app1', ['app' => 'app1']);
		$logger->info('Info of app2', ['app' => 'app2']);
		$logger->debug('Debug of app3', ['app' => 'app3']);

		$this->assertEquals($expectedLogs, $this->getLogs());
	}

	public function testLoggingWithDataArray(): void {
		$this->mockDefaultLogLevel();
		/** @var IWriter&MockObject */
		$writerMock = $this->createMock(IWriter::class);
		$logFile = new Log($writerMock, $this->config);
		$writerMock->expects($this->once())->method('write')->with('no app in context', ['something' => 'extra', 'message' => 'Testing logging with john']);
		$logFile->error('Testing logging with {user}', ['something' => 'extra', 'user' => 'john']);
	}

	private function getLogs(): array {
		return $this->logs;
	}

	public function write(string $app, $message, int $level) {
		$textMessage = $message;
		if (is_array($message)) {
			$textMessage = $message['message'];
		}
		$this->logs[] = $level . ' ' . $textMessage;
	}

	public static function userAndPasswordData(): array {
		return [
			['mySpecialUsername', 'MySuperSecretPassword'],
			['my-user', '324324()#ä234'],
			['my-user', ')qwer'],
			['my-user', 'qwer)asdf'],
			['my-user', 'qwer)'],
			['my-user', '(qwer'],
			['my-user', 'qwer(asdf'],
			['my-user', 'qwer('],
		];
	}

	/**
	 * @dataProvider userAndPasswordData
	 */
	public function testDetectlogin(string $user, string $password): void {
		$this->mockDefaultLogLevel();
		$e = new \Exception('test');
		$this->registry->expects($this->once())
			->method('delegateReport')
			->with($e, ['level' => 3]);

		$this->logger->logException($e);

		$logLines = $this->getLogs();
		foreach ($logLines as $logLine) {
			if (is_array($logLine)) {
				$logLine = json_encode($logLine);
			}
			$this->assertStringNotContainsString($user, $logLine);
			$this->assertStringNotContainsString($password, $logLine);
			$this->assertStringContainsString('*** sensitive parameters replaced ***', $logLine);
		}
	}

	/**
	 * @dataProvider userAndPasswordData
	 */
	public function testDetectcheckPassword(string $user, string $password): void {
		$this->mockDefaultLogLevel();
		$e = new \Exception('test');
		$this->registry->expects($this->once())
			->method('delegateReport')
			->with($e, ['level' => 3]);

		$this->logger->logException($e);

		$logLines = $this->getLogs();
		foreach ($logLines as $logLine) {
			if (is_array($logLine)) {
				$logLine = json_encode($logLine);
			}
			$this->assertStringNotContainsString($user, $logLine);
			$this->assertStringNotContainsString($password, $logLine);
			$this->assertStringContainsString('*** sensitive parameters replaced ***', $logLine);
		}
	}

	/**
	 * @dataProvider userAndPasswordData
	 */
	public function testDetectvalidateUserPass(string $user, string $password): void {
		$this->mockDefaultLogLevel();
		$e = new \Exception('test');
		$this->registry->expects($this->once())
			->method('delegateReport')
			->with($e, ['level' => 3]);

		$this->logger->logException($e);

		$logLines = $this->getLogs();
		foreach ($logLines as $logLine) {
			if (is_array($logLine)) {
				$logLine = json_encode($logLine);
			}
			$this->assertStringNotContainsString($user, $logLine);
			$this->assertStringNotContainsString($password, $logLine);
			$this->assertStringContainsString('*** sensitive parameters replaced ***', $logLine);
		}
	}

	/**
	 * @dataProvider userAndPasswordData
	 */
	public function testDetecttryLogin(string $user, string $password): void {
		$this->mockDefaultLogLevel();
		$e = new \Exception('test');
		$this->registry->expects($this->once())
			->method('delegateReport')
			->with($e, ['level' => 3]);

		$this->logger->logException($e);

		$logLines = $this->getLogs();
		foreach ($logLines as $logLine) {
			if (is_array($logLine)) {
				$logLine = json_encode($logLine);
			}
			$this->assertStringNotContainsString($user, $logLine);
			$this->assertStringNotContainsString($password, $logLine);
			$this->assertStringContainsString('*** sensitive parameters replaced ***', $logLine);
		}
	}

	/**
	 * @dataProvider userAndPasswordData
	 */
	public function testDetectclosure(string $user, string $password): void {
		$this->mockDefaultLogLevel();
		$a = function ($user, $password): void {
			throw new \Exception('test');
		};
		$this->registry->expects($this->once())
			->method('delegateReport');

		try {
			$a($user, $password);
		} catch (\Exception $e) {
			$this->logger->logException($e);
		}

		$logLines = $this->getLogs();
		foreach ($logLines as $logLine) {
			if (is_array($logLine)) {
				$logLine = json_encode($logLine);
			}
			$log = explode('\n', $logLine);
			unset($log[1]); // Remove `testDetectclosure(` because we are not testing this here, but the closure on stack trace 0
			$logLine = implode('\n', $log);

			$this->assertStringNotContainsString($user, $logLine);
			$this->assertStringNotContainsString($password, $logLine);
			$this->assertStringContainsString('*** sensitive parameters replaced ***', $logLine);
		}
	}
}
