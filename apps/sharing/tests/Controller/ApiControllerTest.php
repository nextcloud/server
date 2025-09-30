<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

use OCA\Sharing\Controller\ApiController;
use OCA\Sharing\Features\ExpirationShareFeature;
use OCA\Sharing\Features\NoteShareFeature;
use OCA\Sharing\Manager;
use OCA\Sharing\RecipientTypes\GroupShareRecipientType;
use OCA\Sharing\RecipientTypes\UserShareRecipientType;
use OCA\Sharing\Registry;
use OCA\Sharing\SourceTypes\NodeShareSourceType;
use OCP\AppFramework\Http;
use OCP\Files\IRootFolder;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Server;
use Test\TestCase;

/**
 * @group DB
 */
class ApiControllerTest extends TestCase {
	private Registry $registry;

	private IUser $user1;

	private IUser $user2;

	private IUser $user3;

	private IGroup $group1;

	private ApiController $controller;

	public function setUp(): void {
		parent::setUp();

		$this->registry = Server::get(Registry::class);
		$this->registry->clear();

		$this->user1 = Server::get(IUserManager::class)->createUser('user1', 'password');
		$this->user2 = Server::get(IUserManager::class)->createUser('user2', 'password');
		$this->user3 = Server::get(IUserManager::class)->createUser('user3', 'password');

		$this->group1 = Server::get(IGroupManager::class)->createGroup('group1');
		$this->group1->addUser($this->user1);

		self::loginAsUser($this->user1->getUID());

		$this->controller = Server::get(ApiController::class);
	}

	protected function tearDown(): void {
		$manager = Server::get(Manager::class);
		foreach ($manager->list(null, null, false, false) as $share) {
			$manager->delete(null, $share->id, false, false);
		}

		$this->user1->delete();
		$this->user2->delete();
		$this->user3->delete();
		$this->group1->delete();

		parent::tearDown();
	}

	public function testSearchRecipients(): void {
		$this->registry->registerRecipientType(new UserShareRecipientType());

		$response = $this->controller->searchRecipients(UserShareRecipientType::class, 'user');
		$this->assertEquals(Http::STATUS_OK, $response->getStatus());
		$this->assertEquals(['user1', 'user2', 'user3'], $response->getData());

		$response = $this->controller->searchRecipients(UserShareRecipientType::class, 'user', 1);
		$this->assertEquals(Http::STATUS_OK, $response->getStatus());
		$this->assertEquals(['user1'], $response->getData());

		$response = $this->controller->searchRecipients(UserShareRecipientType::class, 'user', offset: 1);
		$this->assertEquals(Http::STATUS_OK, $response->getStatus());
		$this->assertEquals(['user2', 'user3'], $response->getData());

		$response = $this->controller->searchRecipients(GroupShareRecipientType::class, 'group');
		$this->assertEquals(Http::STATUS_BAD_REQUEST, $response->getStatus());
		$this->assertEquals('Invalid share recipient search parameters: The recipient type is not registered.', $response->getData());
	}

	public function testCreateShare(): void {
		$this->registry->registerSourceType(new NodeShareSourceType());
		$this->registry->registerRecipientType(new UserShareRecipientType());
		$this->registry->registerFeature(new NoteShareFeature());

		$sourceNode = Server::get(IRootFolder::class)->getUserFolder($this->user1->getUID())->newFile('foo.txt', 'bar');

		$data = [
			'creator' => $this->user1->getUID(),
			'source_type' => NodeShareSourceType::class,
			'sources' => [(string)$sourceNode->getId()],
			'recipient_type' => UserShareRecipientType::class,
			'recipients' => [$this->user2->getUID()],
			'properties' => [NoteShareFeature::class => ['text' => ['abc']]],
		];
		$response = $this->controller->createShare($data);
		$this->assertEquals(Http::STATUS_CREATED, $response->getStatus());
		$responseData = $response->getData();
		$this->assertIsString($responseData['id']);
		unset($responseData['id']);
		$this->assertEquals($data, $responseData);
	}

	public function testGetShare(): void {
		$this->registry->registerSourceType(new NodeShareSourceType());
		$this->registry->registerRecipientType(new UserShareRecipientType());
		$this->registry->registerFeature(new NoteShareFeature());

		$sourceNode = Server::get(IRootFolder::class)->getUserFolder($this->user1->getUID())->newFile('foo.txt', 'bar');

		$data = [
			'creator' => $this->user1->getUID(),
			'source_type' => NodeShareSourceType::class,
			'sources' => [(string)$sourceNode->getId()],
			'recipient_type' => UserShareRecipientType::class,
			'recipients' => [$this->user2->getUID()],
			'properties' => [NoteShareFeature::class => ['text' => ['abc']]],
		];
		$response = $this->controller->createShare($data);
		$this->assertEquals(Http::STATUS_CREATED, $response->getStatus());
		$responseData = $response->getData();
		$this->assertIsString($responseData['id']);
		$id = $responseData['id'];
		unset($responseData['id']);
		$this->assertEquals($data, $responseData);

		$response = $this->controller->getShare($id);
		$this->assertEquals(Http::STATUS_OK, $response->getStatus());
		$responseData = $response->getData();
		$this->assertIsString($responseData['id']);
		unset($responseData['id']);
		$this->assertEquals($data, $responseData);
	}

	public function testGetShareAsRecipient(): void {
		$this->registry->registerSourceType(new NodeShareSourceType());
		$this->registry->registerRecipientType(new UserShareRecipientType());
		$this->registry->registerFeature(new NoteShareFeature());

		$sourceNode = Server::get(IRootFolder::class)->getUserFolder($this->user1->getUID())->newFile('foo.txt', 'bar');

		$data = [
			'creator' => $this->user1->getUID(),
			'source_type' => NodeShareSourceType::class,
			'sources' => [(string)$sourceNode->getId()],
			'recipient_type' => UserShareRecipientType::class,
			'recipients' => [$this->user2->getUID()],
			'properties' => [NoteShareFeature::class => ['text' => ['abc']]],
		];
		$response = $this->controller->createShare($data);
		$this->assertEquals(Http::STATUS_CREATED, $response->getStatus());
		$responseData = $response->getData();
		$this->assertIsString($responseData['id']);
		$id = $responseData['id'];
		unset($responseData['id']);
		$this->assertEquals($data, $responseData);

		self::loginAsUser($this->user2->getUID());

		$response = $this->controller->getShare($id);
		$this->assertEquals(Http::STATUS_OK, $response->getStatus());
		$responseData = $response->getData();
		$this->assertIsString($responseData['id']);
		unset($responseData['id']);
		$this->assertEquals($data, $responseData);
	}

	public function testGetShareAsNonRecipient(): void {
		$this->registry->registerSourceType(new NodeShareSourceType());
		$this->registry->registerRecipientType(new UserShareRecipientType());
		$this->registry->registerFeature(new NoteShareFeature());

		$sourceNode = Server::get(IRootFolder::class)->getUserFolder($this->user1->getUID())->newFile('foo.txt', 'bar');

		$data = [
			'creator' => $this->user1->getUID(),
			'source_type' => NodeShareSourceType::class,
			'sources' => [(string)$sourceNode->getId()],
			'recipient_type' => UserShareRecipientType::class,
			'recipients' => [$this->user2->getUID()],
			'properties' => [NoteShareFeature::class => ['text' => ['abc']]],
		];
		$response = $this->controller->createShare($data);
		$this->assertEquals(Http::STATUS_CREATED, $response->getStatus());
		$responseData = $response->getData();
		$this->assertIsString($responseData['id']);
		$id = $responseData['id'];
		unset($responseData['id']);
		$this->assertEquals($data, $responseData);

		self::loginAsUser($this->user3->getUID());

		$response = $this->controller->getShare($id);
		$this->assertEquals(Http::STATUS_NOT_FOUND, $response->getStatus());
		$this->assertEquals('Share ' . $id . ' not found.', $response->getData());
	}

	public function testGetShareFiltered(): void {
		$this->registry->registerSourceType(new NodeShareSourceType());
		$this->registry->registerRecipientType(new UserShareRecipientType());
		$this->registry->registerFeature(new ExpirationShareFeature());

		$sourceNode = Server::get(IRootFolder::class)->getUserFolder($this->user1->getUID())->newFile('foo.txt', 'bar');

		$data = [
			'creator' => $this->user1->getUID(),
			'source_type' => NodeShareSourceType::class,
			'sources' => [(string)$sourceNode->getId()],
			'recipient_type' => UserShareRecipientType::class,
			'recipients' => [$this->user2->getUID()],
			'properties' => [ExpirationShareFeature::class => ['date' => [(new DateTimeImmutable())->add(new DateInterval('PT1S'))->format(DateTimeInterface::ATOM)]]],
		];
		$response = $this->controller->createShare($data);
		$this->assertEquals(Http::STATUS_CREATED, $response->getStatus());
		$responseData = $response->getData();
		$this->assertIsString($responseData['id']);
		$id = $responseData['id'];
		unset($responseData['id']);
		$this->assertEquals($data, $responseData);

		$response = $this->controller->getShare($id);
		$this->assertEquals(Http::STATUS_OK, $response->getStatus());
		$responseData = $response->getData();
		$this->assertIsString($responseData['id']);
		unset($responseData['id']);
		$this->assertEquals($data, $responseData);

		// Wait for the share to expire
		sleep(2);

		$response = $this->controller->getShare($id);
		$this->assertEquals(Http::STATUS_NOT_FOUND, $response->getStatus());
		$this->assertEquals('Share ' . $id . ' not found.', $response->getData());
	}

	public function testGetShares(): void {
		$this->registry->registerSourceType(new NodeShareSourceType());
		$this->registry->registerRecipientType(new UserShareRecipientType());
		$this->registry->registerFeature(new NoteShareFeature());

		$sourceNode = Server::get(IRootFolder::class)->getUserFolder($this->user1->getUID())->newFile('foo.txt', 'bar');

		$data1 = [
			'creator' => $this->user1->getUID(),
			'source_type' => NodeShareSourceType::class,
			'sources' => [(string)$sourceNode->getId()],
			'recipient_type' => UserShareRecipientType::class,
			'recipients' => [$this->user2->getUID()],
			'properties' => [NoteShareFeature::class => ['text' => ['abc']]],
		];
		$response = $this->controller->createShare($data1);
		$this->assertEquals(Http::STATUS_CREATED, $response->getStatus());
		$responseData = $response->getData();
		$this->assertIsString($responseData['id']);
		unset($responseData['id']);
		$this->assertEquals($data1, $responseData);

		$data2 = [
			'creator' => $this->user1->getUID(),
			'source_type' => NodeShareSourceType::class,
			'sources' => [(string)$sourceNode->getId()],
			'recipient_type' => UserShareRecipientType::class,
			'recipients' => [$this->user2->getUID()],
			'properties' => [NoteShareFeature::class => ['text' => ['abc']]],
		];
		$response = $this->controller->createShare($data2);
		$this->assertEquals(Http::STATUS_CREATED, $response->getStatus());
		$responseData = $response->getData();
		$this->assertIsString($responseData['id']);
		unset($responseData['id']);
		$this->assertEquals($data2, $responseData);

		$response = $this->controller->getShares();
		$this->assertEquals(Http::STATUS_OK, $response->getStatus());
		$responseData = $response->getData();
		$this->assertIsString($responseData[0]['id']);
		$this->assertIsString($responseData[1]['id']);
		unset($responseData[0]['id'], $responseData[1]['id']);
		$this->assertEquals([$data1, $data2], $responseData);

		$response = $this->controller->getShares(NodeShareSourceType::class);
		$this->assertEquals(Http::STATUS_OK, $response->getStatus());
		$responseData = $response->getData();
		$this->assertIsString($responseData[0]['id']);
		$this->assertIsString($responseData[1]['id']);
		unset($responseData[0]['id'], $responseData[1]['id']);
		$this->assertEquals([$data1, $data2], $responseData);

		$response = $this->controller->getShares('invalid');
		$this->assertEquals(Http::STATUS_OK, $response->getStatus());
		$this->assertEquals([], $response->getData());
	}

	public function testUpdateShare(): void {
		$this->registry->registerSourceType(new NodeShareSourceType());
		$this->registry->registerRecipientType(new UserShareRecipientType());
		$this->registry->registerFeature(new NoteShareFeature());

		$sourceNode = Server::get(IRootFolder::class)->getUserFolder($this->user1->getUID())->newFile('foo.txt', 'bar');

		$data = [
			'creator' => $this->user1->getUID(),
			'source_type' => NodeShareSourceType::class,
			'sources' => [(string)$sourceNode->getId()],
			'recipient_type' => UserShareRecipientType::class,
			'recipients' => [$this->user2->getUID()],
			'properties' => [NoteShareFeature::class => ['text' => ['abc']]],
		];
		$response = $this->controller->createShare($data);
		$this->assertEquals(Http::STATUS_CREATED, $response->getStatus());
		$responseData = $response->getData();
		$this->assertIsString($responseData['id']);
		$id = $responseData['id'];
		unset($responseData['id']);
		$this->assertEquals($data, $responseData);

		$sourceNode = Server::get(IRootFolder::class)->getUserFolder($this->user1->getUID())->newFile('bar.txt', 'foo');

		$data['sources'] = [(string)$sourceNode->getId()];
		$data['recipients'] = [$this->user3->getUID()];
		$data['properties'] = [NoteShareFeature::class => ['text' => ['def']]];
		$response = $this->controller->updateShare($id, $data);
		$this->assertEquals(Http::STATUS_OK, $response->getStatus());
		$responseData = $response->getData();
		$this->assertIsString($responseData['id']);
		unset($responseData['id']);
		$this->assertEquals($data, $responseData);

		$response = $this->controller->getShare($id);
		$this->assertEquals(Http::STATUS_OK, $response->getStatus());
		$responseData = $response->getData();
		$this->assertIsString($responseData['id']);
		unset($responseData['id']);
		$this->assertEquals($data, $responseData);
	}

	public function testDeleteShare(): void {
		$this->registry->registerSourceType(new NodeShareSourceType());
		$this->registry->registerRecipientType(new UserShareRecipientType());
		$this->registry->registerFeature(new NoteShareFeature());

		$sourceNode = Server::get(IRootFolder::class)->getUserFolder($this->user1->getUID())->newFile('foo.txt', 'bar');

		$data = [
			'creator' => $this->user1->getUID(),
			'source_type' => NodeShareSourceType::class,
			'sources' => [(string)$sourceNode->getId()],
			'recipient_type' => UserShareRecipientType::class,
			'recipients' => [$this->user2->getUID()],
			'properties' => [NoteShareFeature::class => ['text' => ['abc']]],
		];
		$response = $this->controller->createShare($data);
		$this->assertEquals(Http::STATUS_CREATED, $response->getStatus());
		$responseData = $response->getData();
		$this->assertIsString($responseData['id']);
		$id = $responseData['id'];
		unset($responseData['id']);
		$this->assertEquals($data, $responseData);

		$response = $this->controller->getShare($id);
		$this->assertEquals(Http::STATUS_OK, $response->getStatus());
		$responseData = $response->getData();
		$this->assertIsString($responseData['id']);
		unset($responseData['id']);
		$this->assertEquals($data, $responseData);

		$response = $this->controller->deleteShare($id);
		$this->assertEquals(Http::STATUS_NO_CONTENT, $response->getStatus());
		$this->assertEquals([], $response->getData());

		$response = $this->controller->getShare($id);
		$this->assertEquals(Http::STATUS_NOT_FOUND, $response->getStatus());
		$this->assertEquals('Share ' . $id . ' not found.', $response->getData());
	}
}
