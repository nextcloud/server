<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OC\Core\Sharing\Recipient;

use OC\Core\AppInfo\Application;
use OCP\Collaboration\Collaborators\ISearch;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\L10N\IFactory;
use OCP\Server;
use OCP\Share\IShare;
use OCP\Sharing\Icon\ShareIconSVG;
use OCP\Sharing\Icon\ShareIconURL;
use OCP\Sharing\Recipient\IShareRecipientType;
use OCP\Sharing\Recipient\IShareRecipientTypeSearch;
use OCP\Sharing\Recipient\ShareRecipient;
use OCP\Sharing\ShareAccessContext;
use RuntimeException;

final class GroupShareRecipientType implements IShareRecipientType, IShareRecipientTypeSearch {
	#[\Override]
	public function getDisplayName(): string {
		return Server::get(IFactory::class)->get(Application::APP_ID)->t('Group');
	}

	#[\Override]
	public function validateRecipient(IUser $owner, string $recipient): bool {
		return Server::get(IGroupManager::class)->groupExists($recipient);
	}

	#[\Override]
	public function getRecipients(?IUser $currentUser, mixed $arguments): array {
		if (!$currentUser instanceof IUser) {
			return [];
		}

		return Server::get(IGroupManager::class)->getUserGroupIds($currentUser);
	}

	#[\Override]
	public function getRecipientDisplayName(string $recipient): ?string {
		$displayName = Server::get(IGroupManager::class)->getDisplayName($recipient);
		if ($displayName === '') {
			return null;
		}

		return $displayName;
	}

	#[\Override]
	public function getRecipientIcon(string $recipient): null|ShareIconSVG|ShareIconURL {
		return null;
	}

	/**
	 * @return list<ShareRecipient>
	 */
	#[\Override]
	public function searchRecipients(ShareAccessContext $accessContext, string $query, int $limit, int $offset): array {
		// TODO: Return nothing if no user present?

		// TODO: Maybe enable lookup?
		/** @var array{array{groups: list<array>, exact: array{groups: list<array>}}, bool} $results */
		$results = Server::get(ISearch::class)->search($query, [IShare::TYPE_GROUP], false, $limit, $offset);
		$results = array_merge($results[0]['exact']['groups'], $results[0]['groups']);

		return array_map(static function (array $result): ShareRecipient {
			if (!isset($result['value'])) {
				throw new RuntimeException('The value is missing.');
			}

			if (!is_array($result['value'])) {
				throw new RuntimeException('The value is not an array.');
			}

			if (!isset($result['value']['shareWith'])) {
				throw new RuntimeException('The shareWith is missing.');
			}

			if (!is_string($result['value']['shareWith'])) {
				throw new RuntimeException('The shareWith is not a string.');
			}

			if ($result['value']['shareWith'] === '') {
				throw new RuntimeException('The shareWith is empty.');
			}

			return new ShareRecipient(
				self::class,
				$result['value']['shareWith'],
			);
		}, $results);
	}
}
