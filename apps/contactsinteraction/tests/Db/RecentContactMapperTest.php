<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\ContactsInteraction\Tests\Db;

use OCA\ContactsInteraction\Db\RecentContact;
use OCA\ContactsInteraction\Db\RecentContactMapper;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IUser;
use Sabre\VObject\Component\VCard;
use Sabre\VObject\UUIDUtil;
use Test\TestCase;

/**
 * @group DB
 */
class RecentContactMapperTest extends TestCase {

	/** @var RecentContactMapper */
	private $recentContactMapper;

	/** @var ITimeFactory */
	private $time;

	protected function setUp(): void {
		parent::setUp();

		$this->recentContactMapper = \OC::$server->get(RecentContactMapper::class);
		$this->time = \OC::$server->get(ITimeFactory::class);
	}

	protected function tearDown(): void {
		parent::tearDown();

		$this->recentContactMapper->cleanUp(time());
	}

	public function testCreateRecentContact(): void {
		$contact = $this->createRecentContact();
		$this->assertNotNull($contact->getId());
	}

	public function testFindAll(): void {
		$this->createRecentContact();
		$this->createRecentContact();

		$contacts = $this->recentContactMapper->findAll('admin');
		$this->assertCount(2, $contacts);
	}

	public function testFindMatchByEmail(): void {
		$this->createRecentContact();
		$contact = $this->createRecentContact('foo@bar');

		$user = $this->createMock(IUser::class);
		$user->method('getUID')
			->willReturn('admin');

		$result = $this->recentContactMapper->findMatch($user, null, 'foo@bar', null);

		$this->assertCount(1, $result);
		$this->assertEquals($contact->getId(), $result[0]->getId());
	}

	public function testFindMatchByFederatedCloudId(): void {
		$this->createRecentContact();
		$contact = $this->createRecentContact(null, 'foo.bar');

		$user = $this->createMock(IUser::class);
		$user->method('getUID')
			->willReturn('admin');

		$result = $this->recentContactMapper->findMatch($user, null, null, 'foo.bar');

		$this->assertCount(1, $result);
		$this->assertEquals($contact->getId(), $result[0]->getId());
	}

	public function testCleanUp(): void {
		$this->createRecentContact();
		$this->createRecentContact();
		$this->assertCount(2, $this->recentContactMapper->findAll('admin'));

		$this->recentContactMapper->cleanUp(time());
		$this->assertCount(0, $this->recentContactMapper->findAll('admin'));
	}

	protected function createRecentContact(?string $email = null, ?string $federatedCloudId = null): RecentContact {
		$props = [
			'URI' => UUIDUtil::getUUID(),
			'FN' => 'Foo Bar',
			'CATEGORIES' => 'Recently contacted',
		];

		$time = $this->time->getDateTime();
		$time->modify('-14days');

		$contact = new RecentContact();
		$contact->setActorUid('admin');
		$contact->setCard((new VCard($props))->serialize());
		$contact->setLastContact($time->getTimestamp());

		if ($email !== null) {
			$contact->setEmail($email);
		}

		if ($federatedCloudId !== null) {
			$contact->setFederatedCloudId($federatedCloudId);
		}

		return $this->recentContactMapper->insert($contact);
	}
}
