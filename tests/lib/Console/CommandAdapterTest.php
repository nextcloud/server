<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Console;

use OC\Console\CommandAdapter;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Container\ContainerInterface;
use Stecman\Component\Symfony\Console\BashCompletion\CompletionContext;
use Symfony\Component\Console\Tester\CommandCompletionTester;
use Test\Console\Fixtures\CompletionFixtureCommand;
use Test\Console\Fixtures\FixtureDependency;
use Test\TestCase;

class CommandAdapterTest extends TestCase {
	private ContainerInterface&MockObject $container;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();
		$this->container = $this->createMock(ContainerInterface::class);
		$this->container->method('get')
			->with(CompletionFixtureCommand::class)
			->willReturn(new CompletionFixtureCommand(new FixtureDependency()));
	}

	private function createAdapter(): CommandAdapter {
		return new CommandAdapter(CompletionFixtureCommand::class, null, $this->container);
	}

	private function contextWithCurrentWord(string $word): CompletionContext&MockObject {
		$context = $this->createMock(CompletionContext::class);
		$context->method('getCurrentWord')->willReturn($word);
		return $context;
	}

	public function testCompleteArgumentValuesResolvesAStaticCallableDynamically(): void {
		$adapter = $this->createAdapter();
		$this->assertEquals(['alpha'], $adapter->completeArgumentValues('dynamic', $this->contextWithCurrentWord('a')));
		$this->assertEquals(['alpha', 'beta', 'gamma'], $adapter->completeArgumentValues('dynamic', $this->contextWithCurrentWord('')));
	}

	public function testCompleteArgumentValuesResolvesANonStaticCallableOnAContainerResolvedInstance(): void {
		$adapter = $this->createAdapter();
		$this->assertEquals(['injected-value'], $adapter->completeArgumentValues('instanceBased', $this->contextWithCurrentWord('')));
	}

	public function testCompleteArgumentValuesReturnsAStaticList(): void {
		$adapter = $this->createAdapter();
		$this->assertEquals(['foo', 'bar'], $adapter->completeArgumentValues('static', $this->contextWithCurrentWord('')));
	}

	public function testCompleteArgumentValuesReturnsEmptyForUnknownArgument(): void {
		$adapter = $this->createAdapter();
		$this->assertEquals([], $adapter->completeArgumentValues('does-not-exist', $this->contextWithCurrentWord('')));
	}

	public function testCompleteOptionValuesReturnsAStaticList(): void {
		$adapter = $this->createAdapter();
		$this->assertEquals(['x', 'y'], $adapter->completeOptionValues('option', $this->contextWithCurrentWord('')));
	}

	public function testCompleteOptionValuesStillHardcodesOutputFormats(): void {
		$adapter = $this->createAdapter();
		$this->assertEquals(['plain', 'json', 'json_pretty'], $adapter->completeOptionValues('output', $this->contextWithCurrentWord('')));
	}

	/** "occ completion" goes through Command::complete(), a separate path from completeArgumentValues() above. */
	public function testNativeCompletionResolvesAStaticCallableDynamically(): void {
		$tester = new CommandCompletionTester($this->createAdapter());
		$this->assertEquals(['alpha'], $tester->complete(['a']));
	}

	public function testNativeCompletionResolvesANonStaticCallableOnAContainerResolvedInstance(): void {
		$tester = new CommandCompletionTester($this->createAdapter());
		$this->assertEquals(['injected-value'], $tester->complete(['x', '']));
	}

	public function testNativeCompletionReturnsAStaticListForAnArgument(): void {
		$tester = new CommandCompletionTester($this->createAdapter());
		$this->assertEquals(['foo', 'bar'], $tester->complete(['x', 'y', '']));
	}

	public function testNativeCompletionReturnsAStaticListForAnOption(): void {
		$tester = new CommandCompletionTester($this->createAdapter());
		$this->assertEquals(['x', 'y'], $tester->complete(['--option', '']));
	}
}
