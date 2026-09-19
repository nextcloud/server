<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\FederatedFileSharing\Migration;

use Closure;
use OC\Authentication\Token\IProvider;
use OCP\Authentication\Token\IToken;
use OCP\DB\ISchemaWrapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
use OCP\Share\IShare;

class Version1013Date20260919111500 extends SimpleMigrationStep {
	public function __construct(
		private readonly IDBConnection $db,
		private readonly IProvider $tokenProvider,
	) {
	}

	#[\Override]
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		return null;
	}

	#[\Override]
	public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
		$qb = $this->db->getQueryBuilder();
		$result = $qb->select('token', 'uid_initiator')
			->from('share')
			->where($qb->expr()->in(
				'share_type',
				$qb->createNamedParameter(
					[IShare::TYPE_REMOTE, IShare::TYPE_REMOTE_GROUP],
					IQueryBuilder::PARAM_INT_ARRAY,
				),
			))
			->executeQuery();

		$revoked = 0;
		while ($row = $result->fetchAssociative()) {
			$sharedSecret = (string)$row['token'];
			$uid = (string)$row['uid_initiator'];
			try {
				$token = $this->tokenProvider->getToken($sharedSecret);
			} catch (\Exception) {
				continue;
			}

			if ($token->getType() !== IToken::PERMANENT_TOKEN || $token->getUID() !== $uid) {
				continue;
			}

			$this->tokenProvider->invalidateToken($sharedSecret);
			$revoked++;
		}
		$result->closeCursor();

		$output->info(sprintf('Revoked %d federated share tokens registered as user tokens.', $revoked));
	}
}
