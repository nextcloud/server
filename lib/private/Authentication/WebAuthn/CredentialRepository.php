<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Authentication\WebAuthn;

use OC\Authentication\WebAuthn\Db\PublicKeyCredentialEntity;
use OC\Authentication\WebAuthn\Db\PublicKeyCredentialMapper;
use OCP\AppFramework\Db\IMapperException;
use Webauthn\AttestationStatement\AttestationStatementSupportManager;
use Webauthn\AttestationStatement\NoneAttestationStatementSupport;
use Webauthn\CredentialRecord;
use Webauthn\Denormalizer\WebauthnSerializerFactory;
use Webauthn\PublicKeyCredentialUserEntity;

class CredentialRepository {
	private WebauthnSerializerFactory $serializerFactory;

	public function __construct(
		private PublicKeyCredentialMapper $credentialMapper,
	) {
		$attestationStatementSupportManager = AttestationStatementSupportManager::create();
		$attestationStatementSupportManager->add(NoneAttestationStatementSupport::create());
		$this->serializerFactory = new WebauthnSerializerFactory($attestationStatementSupportManager);
	}

	public function findOneByCredentialId(string $publicKeyCredentialId): ?CredentialRecord {
		try {
			$entity = $this->credentialMapper->findOneByCredentialId($publicKeyCredentialId);
			return $this->mapToCredentialRecord($entity);
		} catch (IMapperException) {
			return  null;
		}
	}

	/**
	 * @return CredentialRecord[]
	 */
	public function findAllForUserEntity(PublicKeyCredentialUserEntity $publicKeyCredentialUserEntity): array {
		$uid = $publicKeyCredentialUserEntity->id;
		$entities = $this->credentialMapper->findAllForUid($uid);

		return array_map($this->mapToCredentialRecord(...), $entities);
	}

	public function saveCredentialSource(CredentialRecord $credentialRecord, ?string $name = null, bool $userVerification = false): PublicKeyCredentialEntity {
		$oldEntity = null;

		try {
			$oldEntity = $this->credentialMapper->findOneByCredentialId($credentialRecord->publicKeyCredentialId);
		} catch (IMapperException $e) {
		}

		$defaultName = false;
		if ($name === null) {
			$defaultName = true;
			$name = 'default';
		}

		$credentialId = base64_encode($credentialRecord->publicKeyCredentialId);
		$entity = new PublicKeyCredentialEntity();
		$entity->setName($name);
		$entity->setUid($credentialRecord->userHandle);
		$entity->setUserVerification($userVerification);
		$entity->setPublicKeyCredentialId($credentialId);
		$entity->setData($this->serializeCredentialRecord($credentialRecord));

		if ($oldEntity) {
			$entity->setId($oldEntity->getId());
			if ($defaultName) {
				$entity->setName($oldEntity->getName());
			}

			// Don't downgrade UV just because it was skipped during a login due to another key
			if ($oldEntity->getUserVerification()) {
				$entity->setUserVerification(true);
			}
		}

		return $this->credentialMapper->insertOrUpdate($entity);
	}

	public function mapToCredentialRecord(PublicKeyCredentialEntity $entity): CredentialRecord {
		$serializer = $this->serializerFactory->create();
		return $serializer->deserialize(
			$entity->getData(),
			CredentialRecord::class,
			'json',
		);
	}

	private function serializeCredentialRecord(CredentialRecord $credentialRecord): string {
		$serializer = $this->serializerFactory->create();
		return $serializer->serialize($credentialRecord, 'json');
	}
}
