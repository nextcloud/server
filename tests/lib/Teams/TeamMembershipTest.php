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
use OCA\Circles\Model\Probes\CircleProbe;
use OCP\IURLGenerator;
use PHPUnit\Framework\TestCase;

class TeamMembershipTest extends TestCase {
	public function testMissingBackendDeniesAccess(): void {
		$manager = new TeamManager($this->createMock(Coordinator::class), $this->createMock(IURLGenerator::class), null);
		self::assertFalse($manager->isMemberOfTeam('team-a', 'alice'));
	}

	public function testUsesMemberOnlyProbeAndExplicitUserSession(): void {
		$circles = $this->createMock(CirclesManager::class);
		$user = new FederatedUser();
		$circles->expects(self::once())->method('getFederatedUser')->with('alice')->willReturn($user);
		$circles->expects(self::once())->method('startSession')->with($user);
		$expectedProbe = new CircleProbe();
		$expectedProbe->mustBeMember();
		$circles->expects(self::once())->method('getCircle')->with('team-a', self::equalTo($expectedProbe))->willReturn(new Circle());
		$manager = new TeamManager($this->createMock(Coordinator::class), $this->createMock(IURLGenerator::class), $circles);
		self::assertTrue($manager->isMemberOfTeam('team-a', 'alice'));
	}

	public function testMissingOrInaccessibleTeamDeniesAccess(): void {
		$circles = $this->createMock(CirclesManager::class);
		$circles->method('getFederatedUser')->willReturn(new FederatedUser());
		$circles->method('getCircle')->willThrowException(new CircleNotFoundException());
		$manager = new TeamManager($this->createMock(Coordinator::class), $this->createMock(IURLGenerator::class), $circles);
		self::assertFalse($manager->isMemberOfTeam('team-a', 'alice'));
	}
}
