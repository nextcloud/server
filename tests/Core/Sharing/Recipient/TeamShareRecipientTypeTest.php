<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace Tests\Core\Sharing\Recipient;

use OC\Core\Sharing\Recipient\TeamShareRecipientType;
use OCA\Circles\CirclesManager;
use OCA\Circles\Exceptions\CircleNotFoundException;
use OCA\Circles\Service\CircleService;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IDBConnection;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Server;
use OCP\Sharing\ISharingManager;
use OCP\Sharing\ISharingRegistry;
use OCP\Sharing\Recipient\ShareRecipient;
use OCP\Sharing\ShareAccessContext;
use OCP\Teams\ITeamManager;
use OCP\Teams\Team;
use PHPUnit\Framework\Attributes\Group;
use Test\TestCase;

// TODO: Fix tests that fail when ran together with other test suites

/**
 * @psalm-suppress UndefinedClass, MixedAssignment, MixedMethodCall, MixedArgument Some annoying circles internals that have no public interface. This is only a test, it's fine.
 */
#[Group(name: 'DB')]
final class TeamShareRecipientTypeTest extends TestCase {
	private IDBConnection $dbConnection;

	private ISharingManager $manager;

	private IUser $user1;

	private Team $team1;

	private Team $team2;

	private Team $team3;

	private TeamShareRecipientType $recipientType;

	private const array DISPLAY_NAMES = [
		'team1' => 'Team 1',
		'team2' => 'Team 2',
		'team3' => 'Team 3',
	];

	private function createUser(IUserManager $userManager, string $uid, string $password): IUser {
		$user = $userManager->createUser($uid, $password);
		$this->assertNotFalse($user);
		return $user;
	}

	private function createTeam(ITeamManager $teamManager, string $name): Team {
		$circlesManager = Server::get(CirclesManager::class);
		$circlesManager->startSession($circlesManager->getLocalFederatedUser($this->user1->getUID()));

		$circle = $circlesManager->createCircle($name);
		Server::get(CircleService::class)->updateName($circle->getSingleId(), self::DISPLAY_NAMES[$name]);

		$team = $teamManager->getTeam($circle->getSingleId(), $this->user1->getUID());
		$this->assertNotNull($team);
		return $team;
	}

	#[\Override]
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();

		if (!class_exists(CirclesManager::class)) {
			self::markTestSkipped('Circles app is not installed');
		}
	}

	#[\Override]
	public function setUp(): void {
		parent::setUp();

		$this->dbConnection = Server::get(IDBConnection::class);

		$this->manager = Server::get(ISharingManager::class);

		$userManager = Server::get(IUserManager::class);
		$this->user1 = $this->createUser($userManager, 'user1', 'password');

		$teamManager = Server::get(ITeamManager::class);

		$this->team1 = $this->createTeam($teamManager, 'team1');
		$this->team2 = $this->createTeam($teamManager, 'team2');
		$this->team3 = $this->createTeam($teamManager, 'team3');

		$this->recipientType = new TeamShareRecipientType(Server::get(IEventDispatcher::class), $this->dbConnection, $teamManager, $this->manager);
	}

	#[\Override]
	protected function tearDown(): void {
		$circlesManager = Server::get(CirclesManager::class);
		$circlesManager->startSession($circlesManager->getLocalFederatedUser($this->user1->getUID()));
		try {
			$circlesManager->destroyCircle($this->team1->getId());
		} catch (CircleNotFoundException) {
		}

		$circlesManager->destroyCircle($this->team2->getId());
		$circlesManager->destroyCircle($this->team3->getId());

		$this->user1->delete();

		parent::tearDown();
	}

	/** @psalm-suppress UnevaluatedCode Test is skipped */
	public function testValidateRecipient(): void {
		$this->markTestSkipped('This test is broken if run together with other test suites because circles 🤷‍♀️');
		$this->assertTrue($this->recipientType->validateRecipient($this->team1->getId()));
		$this->assertFalse($this->recipientType->validateRecipient('invalid'));
	}

	public function testGetRecipientValues(): void {
		$this->assertContains($this->team1->getId(), $this->recipientType->getRecipients($this->user1, null));
	}

	/** @psalm-suppress UnevaluatedCode Test is skipped */
	public function testGetRecipientDisplayName(): void {
		$this->markTestSkipped('This test is broken if run together with other test suites because circles 🤷‍♀️');
		$this->assertEquals('Team 1', $this->recipientType->getRecipientDisplayName($this->team1->getId()));
	}

	public function testSearchRecipients(): void {
		$accessContext = new ShareAccessContext(currentUser: $this->user1);
		self::loginAsUser($this->user1->getUID());

		$generateRecipient = static fn (Team $team): ShareRecipient => new ShareRecipient(
			TeamShareRecipientType::class,
			$team->getId(),
			null,
		);

		$this->assertEquals(array_map($generateRecipient(...), [$this->team1, $this->team2, $this->team3]), $this->recipientType->searchRecipients($accessContext, 'Team', 3, 0));
		$this->assertEquals(array_map($generateRecipient(...), [$this->team1]), $this->recipientType->searchRecipients($accessContext, 'Team', 1, 0));
		$this->assertEquals(array_map($generateRecipient(...), [$this->team2, $this->team3]), $this->recipientType->searchRecipients($accessContext, 'Team', 3, 1));
		$this->assertEquals(array_map($generateRecipient(...), [$this->team2]), $this->recipientType->searchRecipients($accessContext, 'Team', 1, 1));

		$this->assertEquals(array_map($generateRecipient(...), [$this->team1]), $this->recipientType->searchRecipients($accessContext, 'Team 1', 1, 0));
	}

	/** @psalm-suppress UnevaluatedCode Test is skipped */
	public function testDelete(): void {
		$this->markTestSkipped('This test is broken if run together with other test suites because circles 🤷‍♀️');

		$registry = Server::get(ISharingRegistry::class);
		$registry->clear();
		$registry->registerRecipientType($this->recipientType);

		$accessContext = new ShareAccessContext(currentUser: $this->user1);

		$this->dbConnection->beginTransaction();
		$id = $this->manager->createShare($accessContext);
		$this->manager->addShareRecipient($accessContext, $id, new ShareRecipient($this->recipientType::class, $this->team1->getId(), null));
		$this->dbConnection->commit();

		$circlesManager = Server::get(CirclesManager::class);
		$circlesManager->startSession($circlesManager->getLocalFederatedUser($this->user1->getUID()));

		$before = $this->manager->generateTimestamp();
		$circlesManager->destroyCircle($this->team1->getId());
		$after = $this->manager->generateTimestamp();

		$this->dbConnection->beginTransaction();
		$share = $this->manager->getShare($accessContext, $id);
		$this->assertGreaterThanOrEqual($before, $share->lastUpdated);
		$this->assertLessThanOrEqual($after, $share->lastUpdated);
		$this->assertEquals([], $share->recipients);

		$this->manager->deleteShare($accessContext, $id);
		$this->dbConnection->commit();
		$registry->clear();
	}
}
