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
use OCP\Teams\ITeamFileResolver;
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

	public function testCollectTeamIdsDoesNotWidenForOtherProviders(): void {
		$teamManager = $this->createTeamManager(true);
		$addressed = $this->createMock(ITeamResourceProvider::class);
		$addressed->expects($this->once())
			->method('getTeamsForResource')
			->with('42')
			->willReturn(['team-1']);

		$resolver = $this->createMock(ITeamFileResolver::class);
		$resolver->expects($this->never())->method('getTeamsForFile');

		$this->setProviders($teamManager, ['deck' => $addressed, 'groupfolders' => $resolver]);

		$this->assertSame(['team-1'], $this->collectTeamIds($teamManager, 'deck', '42'));
	}

	public function testCollectTeamIdsAsksEveryResolverForTheFilesProvider(): void {
		$teamManager = $this->createTeamManager(true);
		$addressed = $this->createMock(ITeamResourceProvider::class);
		$addressed->method('getTeamsForResource')->with('42')->willReturn(['team-1']);

		$resolver = $this->createMock(ITeamFileResolver::class);
		$resolver->expects($this->once())
			->method('getTeamsForFile')
			->with(42)
			->willReturn(['team-2']);

		$unrelated = $this->createMock(ITeamResourceProvider::class);
		$unrelated->expects($this->never())->method('getTeamsForResource');

		$this->setProviders($teamManager, [
			'files' => $addressed,
			'groupfolders' => $resolver,
			'talk' => $unrelated,
		]);

		$this->assertSame(['team-1', 'team-2'], $this->collectTeamIds($teamManager, 'files', '42'));
	}

	public function testCollectTeamIdsDoesNotAskTheAddressedProviderTwice(): void {
		$teamManager = $this->createTeamManager(true);
		$addressed = $this->createMock(ITeamFileResolver::class);
		$addressed->method('getTeamsForResource')->willReturn(['team-1']);
		$addressed->expects($this->never())->method('getTeamsForFile');

		$this->setProviders($teamManager, ['files' => $addressed]);

		$this->assertSame(['team-1'], $this->collectTeamIds($teamManager, 'files', '42'));
	}

	public function testCollectTeamIdsReturnsEachTeamOnce(): void {
		$teamManager = $this->createTeamManager(true);
		$addressed = $this->createMock(ITeamResourceProvider::class);
		$addressed->method('getTeamsForResource')->willReturn(['team-1']);

		$resolver = $this->createMock(ITeamFileResolver::class);
		$resolver->method('getTeamsForFile')->willReturn(['team-1', 'team-2']);

		$this->setProviders($teamManager, ['files' => $addressed, 'groupfolders' => $resolver]);

		$this->assertSame(['team-1', 'team-2'], $this->collectTeamIds($teamManager, 'files', '42'));
	}

	private function collectTeamIds(TeamManager $teamManager, string $providerId, string $resourceId): array {
		$method = new \ReflectionMethod(TeamManager::class, 'collectTeamIds');

		return $method->invoke($teamManager, $providerId, $resourceId);
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
