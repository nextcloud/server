<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\FederatedFileSharing\Migration;

use Closure;
use OC\Authentication\Token\PublicKeyTokenProvider;
use OCP\Authentication\Exceptions\InvalidTokenException;
use OCP\Authentication\Token\IToken;
use OCP\DB\ISchemaWrapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\IUserManager;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
use OCP\Server;
use OCP\Share\IShare;

/**
 * Ensure all existing federated share tokens are registered in oc_authtoken
 * as permanent tokens, which is required for the OCM token exchange flow.
 *
 * Shares created before this fork used TokenHandler (15-char tokens) and never
 * registered in oc_authtoken. Those legacy short tokens are left untouched so
 * that the receiving instance can continue to authenticate via Basic auth with
 * the original token. They will never participate in the token exchange flow,
 * but they will keep working until the share is re-created with a new token.
 *
 * Shares created by this fork (32-char tokens) that are somehow missing from
 * oc_authtoken are silently repaired.
 */
class Version1012Date20260306120000 extends SimpleMigrationStep {
	#[\Override]
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		return null;
	}

	#[\Override]
	public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
		$db = Server::get(IDBConnection::class);
		$tokenProvider = Server::get(PublicKeyTokenProvider::class);
		$userManager = Server::get(IUserManager::class);

		$qb = $db->getQueryBuilder();
		$result = $qb->select('id', 'token', 'uid_initiator')
			->from('share')
			->where($qb->expr()->in(
				'share_type',
				$qb->createNamedParameter(
					[IShare::TYPE_REMOTE, IShare::TYPE_REMOTE_GROUP],
					IQueryBuilder::PARAM_INT_ARRAY
				)
			))
			->executeQuery();

		$registered = 0;
		$skipped = 0;

		while ($row = $result->fetchAssociative()) {
			$shareId = (int)$row['id'];
			$token = (string)$row['token'];
			$uid = (string)$row['uid_initiator'];

			if (strlen($token) < PublicKeyTokenProvider::TOKEN_MIN_LENGTH) {
				// Old short token from TokenHandler — leave it as-is.
				// Replacing it would invalidate the token stored on the receiving instance,
				// breaking Basic-auth access to those shares. These shares keep working via
				// Basic auth and are simply not eligible for the OCM token exchange flow.
				$skipped++;
				continue;
			}

			// Long token — check if it's already in oc_authtoken.
			try {
				$tokenProvider->getToken($token);
				$skipped++;
				continue;
			} catch (InvalidTokenException) {
				// Not registered yet — fall through to create it.
			}

			$user = $userManager->get($uid);
			$name = $user?->getDisplayName() ?? $uid;

			try {
				$tokenProvider->generateToken(
					$token,
					$uid,
					$uid,
					null,
					$name,
					IToken::PERMANENT_TOKEN,
				);
				$registered++;
			} catch (\Exception $e) {
				$output->warning(sprintf(
					'Could not register auth token for share %d (uid=%s): %s',
					$shareId,
					$uid,
					$e->getMessage()
				));
			}
		}

		$result->closeCursor();

		$output->info(sprintf(
			'Federated share token migration: %d registered, %d skipped (already up-to-date or legacy short token).',
			$registered,
			$skipped
		));
	}
}
