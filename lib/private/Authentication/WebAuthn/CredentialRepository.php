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
use Webauthn\PublicKeyCredentialSource;
use Webauthn\PublicKeyCredentialSourceRepository;
use Webauthn\PublicKeyCredentialUserEntity;

class CredentialRepository implements PublicKeyCredentialSourceRepository {
	/** @var PublicKeyCredentialMapper */
	private $credentialMapper;

	public function __construct(PublicKeyCredentialMapper $credentialMapper) {
		$this->credentialMapper = $credentialMapper;
	}

	public function findOneByCredentialId(string $publicKeyCredentialId): ?PublicKeyCredentialSource {
		try {
			$entity = $this->credentialMapper->findOneByCredentialId($publicKeyCredentialId);
			return $entity->toPublicKeyCredentialSource();
		} catch (IMapperException $e) {
			return  null;
		}
	}

	/**
	 * @return PublicKeyCredentialSource[]
	 */
	public function findAllForUserEntity(PublicKeyCredentialUserEntity $publicKeyCredentialUserEntity): array {
		$uid = $publicKeyCredentialUserEntity->getId();
		$entities = $this->credentialMapper->findAllForUid($uid);

		return array_map(function (PublicKeyCredentialEntity $entity) {
			return $entity->toPublicKeyCredentialSource();
		}, $entities);
	}

	public function saveAndReturnCredentialSource(PublicKeyCredentialSource $publicKeyCredentialSource, ?string $name = null, bool $userVerification = false): PublicKeyCredentialEntity {
		$oldEntity = null;

		try {
			$oldEntity = $this->credentialMapper->findOneByCredentialId($publicKeyCredentialSource->getPublicKeyCredentialId());
		} catch (IMapperException $e) {
		}

		$defaultName = false;
		if ($name === null) {
			$defaultName = true;
			$name = 'default';
		}

		$entity = PublicKeyCredentialEntity::fromPublicKeyCrendentialSource($name, $publicKeyCredentialSource, $userVerification);

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

	public function saveCredentialSource(PublicKeyCredentialSource $publicKeyCredentialSource, ?string $name = null): void {
		$this->saveAndReturnCredentialSource($publicKeyCredentialSource, $name);
	}
}
