<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Teams;

use OC\AppFramework\Bootstrap\Coordinator;
use OC\Teams\TeamManager;
use OCP\IURLGenerator;
use OCP\Teams\ITeamFolderProvider;
use OCP\Teams\ITeamResourceProvider;
use Test\TestCase;

class TeamManagerTest extends TestCase {
	public function testGetTeamFolderProviderReturnsNullWithoutTeamSupport(): void {
		$teamManager = $this->createTeamManager();

		$this->assertNull($teamManager->getTeamFolderProvider());
	}

	public function testGetTeamFolderProviderReturnsNullWithoutFolderProvider(): void {
		$teamManager = $this->createTeamManager(true);
		$this->setProviders($teamManager, [
			'other' => $this->createMock(ITeamResourceProvider::class),
		]);

		$this->assertNull($teamManager->getTeamFolderProvider());
	}

	public function testGetTeamFolderProviderReturnsRegisteredFolderProvider(): void {
		$teamManager = $this->createTeamManager(true);
		$folderProvider = $this->createMock(ITeamFolderProvider::class);
		$this->setProviders($teamManager, [
			'other' => $this->createMock(ITeamResourceProvider::class),
			'folder' => $folderProvider,
		]);

		$this->assertSame($folderProvider, $teamManager->getTeamFolderProvider());
	}

	private function createTeamManager(bool $hasTeamSupport = false): TeamManager {
		return new class($this->createMock(Coordinator::class), $this->createMock(IURLGenerator::class), null, $hasTeamSupport, ) extends TeamManager {
			public function __construct(
				Coordinator $bootContext,
				IURLGenerator $urlGenerator,
				null $circlesManager,
				private bool $hasTeamSupport,
			) {
				parent::__construct($bootContext, $urlGenerator, $circlesManager);
			}

			#[\Override]
			public function hasTeamSupport(): bool {
				return $this->hasTeamSupport;
			}
		};
	}

	/**
	 * @param array<string, ITeamResourceProvider> $providers
	 */
	private function setProviders(TeamManager $teamManager, array $providers): void {
		(new \ReflectionProperty(TeamManager::class, 'providers'))->setValue($teamManager, $providers);
	}
}
