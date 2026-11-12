<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace NCU\Sharing\Event;

use NCU\Sharing\Exception\ShareNotFoundException;
use NCU\Sharing\ISharingBackend;
use NCU\Sharing\Share;
use NCU\Sharing\ShareAccessContext;
use OCP\AppFramework\Attribute\Consumable;
use OCP\AppFramework\Attribute\Listenable;
use OCP\EventDispatcher\Event;
use OCP\Server;

/**
 * @experimental 35.0.0
 */
#[Consumable(since: '35.0.0')]
#[Listenable(since: '35.0.0')]
final class SharesUpdatedEvent extends Event {
	/** @var non-empty-list<Share> $shares */
	private ?array $shares = null;

	/**
	 * @param non-empty-list<non-empty-string> $shareIds
	 * @experimental 35.0.0
	 */
	public function __construct(
		public readonly array $shareIds,
	) {
		parent::__construct();
	}

	/**
	 * @return non-empty-list<Share>
	 * @experimental 35.0.0
	 */
	public function getShares(): array {
		if ($this->shares !== null) {
			return $this->shares;
		}

		$shares = Server::get(ISharingBackend::class)->getShares(new ShareAccessContext(overrideChecks: true), $this->shareIds, null, null, null, null, null, null);
		if ($shares === [] || count($shares) !== count($this->shareIds)) {
			throw new ShareNotFoundException();
		}

		return $this->shares = $shares;
	}
}
