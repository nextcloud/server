<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Authentication\WebAuthn;

use OC\Authentication\WebAuthn\CredentialRepository;
use OC\Authentication\WebAuthn\Db\PublicKeyCredentialEntity;
use OC\Authentication\WebAuthn\Db\PublicKeyCredentialMapper;
use OCP\AppFramework\Db\DoesNotExistException;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Uid\Uuid;
use Test\TestCase;
use Webauthn\CredentialRecord;
use Webauthn\TrustPath\EmptyTrustPath;

class CredentialRepositoryTest extends TestCase {
	private PublicKeyCredentialMapper&MockObject $mapper;
	private CredentialRepository $repository;

	protected function setUp(): void {
		parent::setUp();

		$this->mapper = $this->createMock(PublicKeyCredentialMapper::class);
		$this->repository = new CredentialRepository($this->mapper);
	}

	/**
	 * Data as stored by webauthn-lib 4.x (PublicKeyCredentialSource::jsonSerialize)
	 */
	public function testMapLegacyCredentialSource(): void {
		$entity = new PublicKeyCredentialEntity();
		$entity->setData(json_encode([
			'publicKeyCredentialId' => 'Y3JlZGVudGlhbC1pZA',
			'type' => 'public-key',
			'transports' => ['usb'],
			'attestationType' => 'none',
			'trustPath' => ['type' => 'Webauthn\\TrustPath\\EmptyTrustPath'],
			'aaguid' => '00000000-0000-0000-0000-000000000000',
			'credentialPublicKey' => 'cHVibGljLWtleQ',
			'userHandle' => 'amFuZQ',
			'counter' => 42,
			'otherUI' => [],
			'backupEligible' => false,
			'backupStatus' => false,
			'uvInitialized' => true,
		]));

		$record = $this->repository->mapToCredentialRecord($entity);

		$this->assertSame('credential-id', $record->publicKeyCredentialId);
		$this->assertSame('public-key', $record->type);
		$this->assertSame(['usb'], $record->transports);
		$this->assertSame('none', $record->attestationType);
		$this->assertInstanceOf(EmptyTrustPath::class, $record->trustPath);
		$this->assertSame('public-key', $record->credentialPublicKey);
		$this->assertSame('jane', $record->userHandle);
		$this->assertSame(42, $record->counter);
		$this->assertTrue($record->uvInitialized);
	}

	public function testSaveAndFindRoundTrip(): void {
		$record = $this->createRecord('jane');

		$this->mapper->method('findOneByCredentialId')
			->willThrowException(new DoesNotExistException(''));
		$this->mapper->expects($this->once())
			->method('insertOrUpdate')
			->willReturnArgument(0);

		$entity = $this->repository->saveCredentialSource($record, 'My key', true);

		$this->assertSame('My key', $entity->getName());
		$this->assertSame('jane', $entity->getUid());
		$this->assertSame(base64_encode('credential-id'), $entity->getPublicKeyCredentialId());
		$this->assertTrue($entity->getUserVerification());
		$this->assertEquals($record, $this->repository->mapToCredentialRecord($entity));
	}

	public function testSaveKeepsExistingEntity(): void {
		$oldEntity = new PublicKeyCredentialEntity();
		$oldEntity->setId(17);
		$oldEntity->setUid('jane');
		$oldEntity->setName('My key');
		$oldEntity->setUserVerification(true);

		$this->mapper->method('findOneByCredentialId')
			->with('credential-id')
			->willReturn($oldEntity);
		$this->mapper->expects($this->once())
			->method('insertOrUpdate')
			->willReturnArgument(0);

		$entity = $this->repository->saveCredentialSource($this->createRecord('jane'));

		$this->assertSame(17, $entity->getId());
		$this->assertSame('My key', $entity->getName());
		$this->assertTrue($entity->getUserVerification());
	}

	public function testSaveRejectsUidMismatch(): void {
		$oldEntity = new PublicKeyCredentialEntity();
		$oldEntity->setId(17);
		$oldEntity->setUid('john');

		$this->mapper->method('findOneByCredentialId')
			->willReturn($oldEntity);
		$this->mapper->expects($this->never())
			->method('insertOrUpdate');

		$this->expectException(\InvalidArgumentException::class);
		$this->repository->saveCredentialSource($this->createRecord('jane'));
	}

	public function testFindOneByCredentialIdNotFound(): void {
		$this->mapper->method('findOneByCredentialId')
			->willThrowException(new DoesNotExistException(''));

		$this->assertNull($this->repository->findOneByCredentialId('credential-id'));
	}

	private function createRecord(string $uid): CredentialRecord {
		return CredentialRecord::create(
			'credential-id',
			'public-key',
			['usb'],
			'none',
			EmptyTrustPath::create(),
			Uuid::fromString('00000000-0000-0000-0000-000000000000'),
			'public-key',
			$uid,
			0,
		);
	}
}
