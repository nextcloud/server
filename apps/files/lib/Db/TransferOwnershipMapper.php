<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files\Db;

use OCP\AppFramework\ORM\Repository;

/**
 * @template-extends Repository<TransferOwnership>
 */
class TransferOwnershipMapper extends Repository {
	public const string entityClass = TransferOwnership::class;

	public function getById(int $id): TransferOwnership {
		return $this->findOneBy(['id' => $id]);
	}
}
