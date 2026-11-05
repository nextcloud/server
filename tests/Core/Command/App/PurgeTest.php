<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Tests\Core\Command\App;

use OC\App\AppDataPurger;
use OC\App\AppLeftovers;
use OC\Core\Command\App\Purge;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Tester\CommandTester;
use Test\TestCase;

class PurgeTest extends TestCase {
	private AppDataPurger&MockObject $purger;
	private CommandTester $command;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();
		$this->purger = $this->createMock(AppDataPurger::class);
		$this->command = new CommandTester(new Purge($this->purger));
	}

	private function leftovers(string $app = 'polls', bool $codePresent = true, array $tables = ['oc_polls_polls', 'oc_polls_votes']): AppLeftovers {
		return new AppLeftovers($app, $codePresent, $tables, 3, 1, 2, 8, 2048);
	}

	public function testListsAppsWithLeftovers(): void {
		$this->purger->method('findAppsWithLeftovers')->willReturn([
			$this->leftovers(),
			$this->leftovers('oldapp', false, []),
		]);

		$this->assertSame(0, $this->command->execute([]));
		$display = $this->command->getDisplay();
		$this->assertStringContainsString('polls (disabled): 2 tables, 3 config values, 1 user preference, 2 background jobs, 8 migration records, app data 2 KB', $display);
		$this->assertStringContainsString('oldapp (removed): 3 config values', $display);
	}

	public function testSaysSoWhenThereIsNothing(): void {
		$this->purger->method('findAppsWithLeftovers')->willReturn([]);
		$this->assertSame(0, $this->command->execute([]));
		$this->assertStringContainsString('No disabled or removed app has data left behind.', $this->command->getDisplay());
	}

	public function testShowsWithoutDeleting(): void {
		$this->purger->method('getRefusal')->willReturn(null);
		$this->purger->method('getLeftovers')->with('polls')->willReturn($this->leftovers());
		$this->purger->expects($this->never())->method('purge');

		$this->assertSame(0, $this->command->execute(['app-id' => 'polls']));
		$display = $this->command->getDisplay();
		$this->assertStringContainsString('- table oc_polls_votes', $display);
		$this->assertStringContainsString('- 8 migration records', $display);
		$this->assertStringContainsString('Nothing was deleted. Run again with --force', $display);
	}

	public function testDeletesWithForce(): void {
		$leftovers = $this->leftovers();
		$this->purger->method('getRefusal')->willReturn(null);
		$this->purger->method('getLeftovers')->willReturn($leftovers);
		$this->purger->expects($this->once())->method('purge')->with($leftovers);

		$this->assertSame(0, $this->command->execute(['app-id' => 'polls', '--force' => true]));
		$this->assertStringContainsString("Deleted what app 'polls' left behind.", $this->command->getDisplay());
	}

	public function testExplainsThatTheTablesOfARemovedAppAreUnknown(): void {
		$this->purger->method('getRefusal')->willReturn(null);
		$this->purger->method('getLeftovers')->willReturn($this->leftovers('oldapp', false, []));

		$this->command->execute(['app-id' => 'oldapp']);
		$this->assertStringContainsString('code is gone, so its tables cannot be found', $this->command->getDisplay());
	}

	public function testRefusesWithAReason(): void {
		$this->purger->method('getRefusal')->willReturn("App 'polls' is enabled. Disable it first with: occ app:disable polls");
		$this->purger->expects($this->never())->method('getLeftovers');
		$this->purger->expects($this->never())->method('purge');

		$this->assertSame(1, $this->command->execute(['app-id' => 'polls', '--force' => true]));
		$this->assertStringContainsString('Disable it first', $this->command->getDisplay());
	}

	public function testStopsWhenTheTablesCannotBeFound(): void {
		$this->purger->method('getRefusal')->willReturn(null);
		$this->purger->method('getLeftovers')->willThrowException(new \RuntimeException("Could not find the tables of app 'polls' from its migrations"));
		$this->purger->expects($this->never())->method('purge');

		$this->assertSame(1, $this->command->execute(['app-id' => 'polls', '--force' => true]));
		$this->assertStringContainsString('Nothing was deleted.', $this->command->getDisplay());
	}

	public function testJsonOutput(): void {
		$this->purger->method('getRefusal')->willReturn(null);
		$this->purger->method('getLeftovers')->willReturn($this->leftovers());

		$this->command->execute(['app-id' => 'polls', '--output' => 'json']);
		$data = json_decode($this->command->getDisplay(), true);
		$this->assertSame('polls', $data['app']);
		$this->assertSame(['oc_polls_polls', 'oc_polls_votes'], $data['tables']);
		$this->assertFalse($data['deleted']);
	}
}
