<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Authentication\WebAuthn\Db;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\QBMapper;
use OCP\IDBConnection;

/**
 * @template-extends QBMapper<PublicKeyCredentialEntity>
 */
class PublicKeyCredentialMapper extends QBMapper {
	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'webauthn', PublicKeyCredentialEntity::class);
	}

	public function findOneByCredentialId(string $publicKeyCredentialId): PublicKeyCredentialEntity {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
			->from($this->getTableName())
			->where(
				$qb->expr()->eq('public_key_credential_id', $qb->createNamedParameter(base64_encode($publicKeyCredentialId)))
			);

		return $this->findEntity($qb);
	}

	/**
	 * @return PublicKeyCredentialEntity[]
	 */
	public function findAllForUid(string $uid): array {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
			->from($this->getTableName())
			->where(
				$qb->expr()->eq('uid', $qb->createNamedParameter($uid))
			);

		return $this->findEntities($qb);
	}

	/**
	 * @param string $uid
	 * @param int $id
	 *
	 * @return PublicKeyCredentialEntity
	 * @throws DoesNotExistException
	 */
	public function findById(string $uid, int $id): PublicKeyCredentialEntity {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
			->from($this->getTableName())
			->where($qb->expr()->andX(
				$qb->expr()->eq('id', $qb->createNamedParameter($id)),
				$qb->expr()->eq('uid', $qb->createNamedParameter($uid))
			));

		return $this->findEntity($qb);
	}

	/**
	 * @throws \OCP\DB\Exception
	 */
	public function deleteByUid(string $uid) {
		$qb = $this->db->getQueryBuilder();

		$qb->delete($this->getTableName())
			->where(
				$qb->expr()->eq('uid', $qb->createNamedParameter($uid))
			);
		$qb->executeStatement();
	}
}
