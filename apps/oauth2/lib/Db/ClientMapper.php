<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\OAuth2\Db;

use OCA\OAuth2\Exceptions\ClientNotFoundException;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\ORM\Repository;

/**
 * @template-extends Repository<Client>
 * @psalm-suppress ClassMustBeFinal For unit tests
 */
class ClientMapper extends Repository {
	public const string entityClass = Client::class;

	/**
	 * @throws ClientNotFoundException
	 */
	public function getByIdentifier(string $clientIdentifier): Client {
		try {
			return $this->findOneBy([
				'clientIdentifier' => $clientIdentifier,
			]);
		} catch (DoesNotExistException $doesNotExistException) {
			throw new ClientNotFoundException('Could not find client ' . $clientIdentifier, $doesNotExistException->getCode(), previous: $doesNotExistException);
		}
	}

	/**
	 * @param int $id internal id of the client
	 * @throws ClientNotFoundException
	 */
	public function getByUid(int $id): Client {
		try {
			return $this->findOneBy([
				'id' => $id,
			]);
		} catch (DoesNotExistException $doesNotExistException) {
			throw new ClientNotFoundException('could not find client with id ' . $id, $doesNotExistException->getCode(), previous: $doesNotExistException);
		}
	}

	/**
	 * @return \Generator<Client>
	 */
	public function getClients(): \Generator {
		return $this->yieldAll();
	}
}
