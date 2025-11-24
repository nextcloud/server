<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Files\Search;

use OCP\Files\Search\ISearchOperator;
use OCP\Files\Search\ISearchOrder;
use OCP\Files\Search\ISearchQuery;
use OCP\IUser;

class SearchQuery implements ISearchQuery {
	/**
	 * @param ISearchOrder[] $order
	 */
	public function __construct(
		private ISearchOperator $searchOperation,
		private int $limit,
		private int $offset,
		private array $order,
		private ?IUser $user = null,
		private bool $limitToHome = false,
	) {
	}

	public function getSearchOperation(): ISearchOperator {
		return $this->searchOperation;
	}

	public function getLimit(): int {
		return $this->limit;
	}

	public function getOffset(): int {
		return $this->offset;
	}

	/**
	 * @return ISearchOrder[]
	 */
	public function getOrder(): array {
		return $this->order;
	}

	public function getUser(): ?IUser {
		return $this->user;
	}

	public function limitToHome(): bool {
		return $this->limitToHome;
	}
}
