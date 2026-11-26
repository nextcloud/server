<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OC\User;

use Override;

final class DatabaseBackend extends AbstractBackend {
	#[Override]
	public function createUser(): int|false {
		// FIXME
		return false;
	}

	#[Override]
	public function setPassword(int $accountId, string $hashedPassword): bool {
		// FIXME
		return false;
	}

	#[Override]
	public function setDisplayName(): bool {
		// FIXME
		return false;
	}

	#[Override]
	public function countUsers(): int|false {
		$qb = $this->connection->getQueryBuilder();
		$result = $qb
			->select($qb->func()->count('*'))
			->from('user_accounts')
			->where($qb->expr()->eq('backend_id', $qb->createNamedParameter($this->id)))
			->executeQuery();
		return $result->fetchOne();
	}
}
