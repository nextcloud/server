<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\SetupCheck;

use OC\AppFramework\Bootstrap\Coordinator;
use OC\AppFramework\Bootstrap\RegistrationContext;
use OC\AppFramework\Bootstrap\ServiceRegistration;
use OC\SetupCheck\SetupCheckManager;
use OCP\Migration\IOutput;
use OCP\SetupCheck\ISetupCheck;
use OCP\SetupCheck\SetupResult;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class SetupCheckManagerTestSecurityCheck implements ISetupCheck {
	/** @var list<string> */
	public static array $ran = [];

	#[\Override]
	public function getCategory(): string {
		return 'security';
	}

	#[\Override]
	public function getName(): string {
		return 'Security test check';
	}

	#[\Override]
	public function run(): SetupResult {
		self::$ran[] = static::class;
		return SetupResult::success();
	}
}

class SetupCheckManagerTestSystemCheck extends SetupCheckManagerTestSecurityCheck {
	#[\Override]
	public function getCategory(): string {
		return 'system';
	}

	#[\Override]
	public function getName(): string {
		return 'System test check';
	}
}

class SetupCheckManagerTest extends TestCase {
	private Coordinator&MockObject $coordinator;
	private LoggerInterface&MockObject $logger;
	private SetupCheckManager $manager;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		SetupCheckManagerTestSecurityCheck::$ran = [];

		$registrationContext = $this->createMock(RegistrationContext::class);
		$registrationContext->method('getSetupChecks')
			->willReturn([
				new ServiceRegistration('test', SetupCheckManagerTestSecurityCheck::class),
				new ServiceRegistration('test', SetupCheckManagerTestSystemCheck::class),
			]);

		$this->coordinator = $this->createMock(Coordinator::class);
		$this->coordinator->method('getRegistrationContext')
			->willReturn($registrationContext);
		$this->logger = $this->createMock(LoggerInterface::class);

		$this->manager = new SetupCheckManager($this->coordinator, $this->logger);
	}

	/**
	 * Record the reported progress, together with the checks that already ran when it was reported.
	 *
	 * @param list<array{string, list<string>}> $reported
	 */
	private function createOutput(array &$reported): IOutput&MockObject {
		$output = $this->createMock(IOutput::class);
		$output->method('debug')
			->willReturnCallback(function (string $message) use (&$reported): void {
				$reported[] = [$message, SetupCheckManagerTestSecurityCheck::$ran];
			});
		return $output;
	}

	public function testRunAllReportsEveryCheckBeforeAndAfterItIsRun(): void {
		$reported = [];

		$results = $this->manager->runAll($this->createOutput($reported));

		$security = preg_quote(SetupCheckManagerTestSecurityCheck::class, '/');
		$system = preg_quote(SetupCheckManagerTestSystemCheck::class, '/');

		$this->assertCount(4, $reported);
		// The check is reported before it gets a chance to run
		$this->assertSame('Starting check Security test check (' . SetupCheckManagerTestSecurityCheck::class . ')', $reported[0][0]);
		$this->assertSame([], $reported[0][1]);
		$this->assertMatchesRegularExpression(
			'/^Check Security test check \(' . $security . '\) done in \d+\.\d\d seconds, peak memory usage: /',
			$reported[1][0]
		);
		$this->assertSame([SetupCheckManagerTestSecurityCheck::class], $reported[1][1]);
		$this->assertSame('Starting check System test check (' . SetupCheckManagerTestSystemCheck::class . ')', $reported[2][0]);
		$this->assertSame([SetupCheckManagerTestSecurityCheck::class], $reported[2][1]);
		$this->assertMatchesRegularExpression(
			'/^Check System test check \(' . $system . '\) done in \d+\.\d\d seconds, peak memory usage: /',
			$reported[3][0]
		);

		$this->assertEquals(
			['security' => [SetupCheckManagerTestSecurityCheck::class], 'system' => [SetupCheckManagerTestSystemCheck::class]],
			array_map(array_keys(...), $results)
		);
	}

	public function testRunByCategoryOnlyReportsMatchingChecks(): void {
		$reported = [];

		$this->manager->runByCategory('system', $this->createOutput($reported));

		$this->assertCount(2, $reported);
		$this->assertSame('Starting check System test check (' . SetupCheckManagerTestSystemCheck::class . ')', $reported[0][0]);
		$this->assertStringStartsWith('Check System test check (' . SetupCheckManagerTestSystemCheck::class . ') done in ', $reported[1][0]);
		$this->assertEquals([SetupCheckManagerTestSystemCheck::class], SetupCheckManagerTestSecurityCheck::$ran);
	}

	public function testRunByClassOnlyReportsMatchingChecks(): void {
		$reported = [];

		$this->manager->runByClass(SetupCheckManagerTestSecurityCheck::class, $this->createOutput($reported));

		$this->assertCount(2, $reported);
		$this->assertStringContainsString(SetupCheckManagerTestSecurityCheck::class, $reported[0][0]);
		$this->assertEquals([SetupCheckManagerTestSecurityCheck::class], SetupCheckManagerTestSecurityCheck::$ran);
	}

	public function testRunByClassAcceptsALeadingBackslash(): void {
		$results = $this->manager->runByClass('\\' . SetupCheckManagerTestSystemCheck::class);

		$this->assertEquals(['system' => [SetupCheckManagerTestSystemCheck::class]], array_map(array_keys(...), $results));
	}

	public function testRunWithoutOutput(): void {
		$results = $this->manager->runAll();

		$this->assertCount(2, $results);
	}
}
