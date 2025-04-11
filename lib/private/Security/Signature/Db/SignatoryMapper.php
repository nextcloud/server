<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Security\Signature\Db;

use NCU\Security\Signature\Exceptions\SignatoryNotFoundException;
use NCU\Security\Signature\Model\Signatory;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\Exception;
use OCP\IDBConnection;

/**
 * @template-extends QBMapper<Signatory>
 */
class SignatoryMapper extends QBMapper {
	public const TABLE = 'sec_signatory';

	public function __construct(
		IDBConnection $db,
	) {
		parent::__construct($db, self::TABLE, Signatory::class);
	}

	/**
	 *
	 */
	public function getByHost(string $host, string $account = ''): Signatory {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from($this->getTableName())
			->where($qb->expr()->eq('host', $qb->createNamedParameter($host)))
			->andWhere($qb->expr()->eq('account', $qb->createNamedParameter($account)));

		try {
			return $this->findEntity($qb);
		} catch (DoesNotExistException) {
			throw new SignatoryNotFoundException('no signatory found');
		}
	}

	/**
	 */
	public function getByKeyId(string $keyId): Signatory {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from($this->getTableName())
			->where($qb->expr()->eq('key_id_sum', $qb->createNamedParameter($this->hashKeyId($keyId))));

		try {
			return $this->findEntity($qb);
		} catch (DoesNotExistException) {
			throw new SignatoryNotFoundException('no signatory found');
		}
	}

	/**
	 * @param string $keyId
	 *
	 * @return int
	 * @throws Exception
	 */
	public function deleteByKeyId(string $keyId): int {
		$qb = $this->db->getQueryBuilder();
		$qb->delete($this->getTableName())
			->where($qb->expr()->eq('key_id_sum', $qb->createNamedParameter($this->hashKeyId($keyId))));

		return $qb->executeStatement();
	}

	/**
	 * @param Signatory $signatory
	 *
	 * @return int
	 */
	public function updateMetadata(Signatory $signatory): int {
		$qb = $this->db->getQueryBuilder();
		$qb->update($this->getTableName())
			->set('metadata', $qb->createNamedParameter(json_encode($signatory->getMetadata())))
			->set('last_updated', $qb->createNamedParameter(time()));
		$qb->where($qb->expr()->eq('key_id_sum', $qb->createNamedParameter($this->hashKeyId($signatory->getKeyId()))));

		return $qb->executeStatement();
	}

	/**
	 * @param Signatory $signator
	 */
	public function updatePublicKey(Signatory $signatory): int {
		$qb = $this->db->getQueryBuilder();
		$qb->update($this->getTableName())
			->set('signatory', $qb->createNamedParameter($signatory->getPublicKey()))
			->set('last_updated', $qb->createNamedParameter(time()));
		$qb->where($qb->expr()->eq('key_id_sum', $qb->createNamedParameter($this->hashKeyId($signatory->getKeyId()))));

		return $qb->executeStatement();
	}

	/**
	 * returns a hash version for keyId for better index in the database
	 *
	 * @param string $keyId
	 *
	 * @return string
	 */
	private function hashKeyId(string $keyId): string {
		return hash('sha256', $keyId);
	}
}
