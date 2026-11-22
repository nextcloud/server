<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace NCU\Sharing\Recipient;

use Exception;
use OCP\AppFramework\Attribute\Implementable;
use OCP\Contacts\IManager;
use OCP\Server;
use Psr\Log\LoggerInterface;

/**
 * @experimental 35.0.0
 */
#[Implementable(since: '35.0.0')]
trait TShareRecipientTypeDisplayNameAddressBook {
	/**
	 * @param non-empty-string $recipient
	 * @param 'CLOUD'|'EMAIL' $property
	 * @return ?non-empty-string
	 * @experimental 35.0.0
	 */
	public function getRecipientDisplayNameFromAddressBook(string $query, string $property): ?string {
		try {
			// FIXME: If we inject the contacts manager it gets initialized before any address books are registered
			/** @var list<array<string, mixed>> $results */
			$results = Server::get(IManager::class)->search($query, [$property], [
				'limit' => 1,
				'enumeration' => false,
				'strict_search' => true,
			]);
		} catch (Exception $exception) {
			Server::get(LoggerInterface::class)->error($exception->getMessage(), ['exception' => $exception]);

			return null;
		}

		foreach ($results as $result) {
			if (!is_array($result[$property])) {
				continue;
			}

			/** @var mixed $value */
			foreach ($result[$property] as $value) {
				if ($value === $query) {
					$displayName = $result['FN'];
					if (!is_string($displayName) || $displayName === '') {
						continue;
					}

					return $displayName;
				}
			}
		}

		return null;
	}
}
