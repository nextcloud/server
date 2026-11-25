<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Teams;

use OC\AppFramework\Bootstrap\Coordinator;
use OC\Teams\TeamManager;
use OCA\Circles\CirclesManager;
use OCA\Circles\Exceptions\CircleNotFoundException;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\FederatedUser;
use OCA\Circles\Model\Member;
use OCA\Circles\Model\Probes\CircleProbe;
use OCP\IURLGenerator;
use OCP\Teams\ITeamFolderProvider;
use OCP\Teams\ITeamResourceProvider;
use OCP\Teams\TeamResource;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class TeamManagerTest extends TestCase {
	public function testIsMemberOfTeamWithoutTeamSupport(): void {
		$this->assertFalse($this->createTeamManager()->isMemberOfTeam('team', 'user'));
	}

	public function testIsMemberOfTeamUsesMemberOnlyProbe(): void {
		$circlesManager = $this->createCirclesManager();
		$federatedUser = $this->createMock(FederatedUser::class);
		$probe = new CircleProbe();
		$probe->mustBeMember();
		$circlesManager->expects($this->once())->method('getFederatedUser')
			->with('user', Member::TYPE_USER)->willReturn($federatedUser);
		$circlesManager->expects($this->once())->method('startSession')->with($federatedUser);
		$circlesManager->expects($this->once())->method('getCircle')
			->with('team', $this->equalTo($probe))->willReturn($this->createMock(Circle::class));

		$this->assertTrue($this->createTeamManagerWithCircles($circlesManager)->isMemberOfTeam('team', 'user'));
	}

	public function testIsMemberOfTeamReturnsFalseForMissingTeamOrNonMember(): void {
		$circlesManager = $this->createCirclesManager();
		$circlesManager->method('getFederatedUser')->willReturn($this->createMock(FederatedUser::class));
		$circlesManager->expects($this->once())->method('getCircle')->willThrowException(new CircleNotFoundException());

		$this->assertFalse($this->createTeamManagerWithCircles($circlesManager)->isMemberOfTeam('team', 'user'));
	}

	public function testGetSharedWithWithoutTeamSupport(): void {
		$this->assertSame([], $this->createTeamManager()->getSharedWith('team', 'user'));
	}

	public function testGetSharedWithRejectsNonMembers(): void {
		$circlesManager = $this->createCirclesManager();
		$circlesManager->method('getFederatedUser')->willReturn($this->createMock(FederatedUser::class));
		$circlesManager->method('getCircle')->willThrowException(new CircleNotFoundException());
		$provider = $this->createMock(ITeamResourceProvider::class);
		$provider->expects($this->never())->method('getSharedWith');
		$teamManager = $this->createTeamManagerWithCircles($circlesManager);
		$this->setProviders($teamManager, ['provider' => $provider]);

		$this->assertSame([], $teamManager->getSharedWith('team', 'user'));
	}

	public function testGetSharedWithReturnsResourcesForMembers(): void {
		$circlesManager = $this->createCirclesManager();
		$circlesManager->method('getFederatedUser')->willReturn($this->createMock(FederatedUser::class));
		$circlesManager->method('getCircle')->willReturn($this->createMock(Circle::class));
		$resource = $this->createMock(TeamResource::class);
		$provider = $this->createMock(ITeamResourceProvider::class);
		$provider->expects($this->once())->method('getSharedWith')->with('team')->willReturn([$resource]);
		$teamManager = $this->createTeamManagerWithCircles($circlesManager);
		$this->setProviders($teamManager, ['provider' => $provider]);

		$this->assertSame([$resource], $teamManager->getSharedWith('team', 'user'));
	}

	private function createCirclesManager(): CirclesManager&MockObject {
		if (!class_exists(CirclesManager::class)) {
			$this->markTestSkipped('Circles is not installed');
		}

		return $this->createMock(CirclesManager::class);
	}

	private function createTeamManagerWithCircles(CirclesManager $circlesManager): TeamManager {
		return new TeamManager($this->createMock(Coordinator::class), $this->createMock(IURLGenerator::class), $circlesManager);
	}

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
