<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace NCU\Sharing\Recipient;

use NCU\Sharing\ShareAccessContext;
use OCP\AppFramework\Attribute\Implementable;
use OCP\Collaboration\Collaborators\ISearch;
use OCP\IUser;
use OCP\IUserSession;
use OCP\Server;
use OCP\Share\IShare;
use RuntimeException;

/**
 * @experimental 34.0.0
 */
#[Implementable(since: '34.0.0')]
abstract class AShareRecipientTypeSearchCollaborator implements IShareRecipientTypeSearch {
	private ?IUserSession $userSession = null;

	private ?ISearch $search = null;

	private function getUserSession(): IUserSession {
		return $this->userSession ??= Server::get(IUserSession::class);
	}

	private function getSearch(): ISearch {
		return $this->search ??= Server::get(ISearch::class);
	}

	/**
	 * @return IShare::TYPE_*
	 * @experimental 35.0.0
	 */
	abstract public function getCollaboratorType(): int;

	/**
	 * @experimental 35.0.0
	 */
	abstract public function getCollaboratorKey(): string;

	/**
	 * Search for recipients.
	 *
	 * @param positive-int $limit
	 * @param non-negative-int $offset
	 * @return list<ShareRecipient>
	 * @experimental 35.0.0
	 */
	#[\Override]
	public function searchRecipients(ShareAccessContext $accessContext, string $query, int $limit, int $offset): array {
		// Many collaborator plugins require a user session, so we abort early to avoid crashes.
		if (!$accessContext->currentUser instanceof IUser) {
			return [];
		}

		if ($this->getUserSession()->getUser()?->getUID() !== $accessContext->currentUser->getUID()) {
			// Avoid mixing up users
			return [];
		}

		// TODO: Maybe enable lookup?
		// TODO: Maybe merge search requests from different recipient types backed by the collaborators API.
		[$searchResults] = $this->getSearch()->filteredSearch($query, [$this->getCollaboratorType()], false, 'unified-sharing', null, $limit, $offset);

		$results = [];
		if (($exactResults = $searchResults['exact']) !== null) {
			if (!is_array($exactResults)) {
				throw new RuntimeException('The exact results are not an array.');
			}

			if (($exactCollaboratorResults = $exactResults[$this->getCollaboratorKey()] ?? null) !== null) {
				if (!is_array($exactCollaboratorResults) || !array_is_list($exactCollaboratorResults)) {
					throw new RuntimeException('The exact collaborator results are not an array.');
				}

				$results[] = $exactCollaboratorResults;
			}
		}

		if (($collaboratorResults = $searchResults[$this->getCollaboratorKey()] ?? null) !== null) {
			if (!is_array($collaboratorResults) || !array_is_list($collaboratorResults)) {
				throw new RuntimeException('The collaborator results are not an array.');
			}

			$results[] = $collaboratorResults;
		}

		if ($results === []) {
			return [];
		}

		return array_map(function (array $result): ShareRecipient {
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
				// Must be $this and not self, so the inheriting class is used.
				static::class,
				$result['value']['shareWith'],
				null,
			);
		}, array_merge(...$results));
	}
}
