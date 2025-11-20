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
	public function __construct(
		private ISearchOperator $searchOperation,
		private int $limit,
		private int $offset,
		private array $order,
		private ?IUser $user = null,
		private bool $limitToHome = false,
	) {
	}

	/**
	 * @return ISearchOperator
	 */
	public function getSearchOperation() {
		return $this->searchOperation;
	}

	/**
	 * @return int
	 */
	public function getLimit() {
		return $this->limit;
	}

	/**
	 * @return int
	 */
	public function getOffset() {
		return $this->offset;
	}

	/**
	 * @return ISearchOrder[]
	 */
	public function getOrder() {
		return $this->order;
	}

	/**
	 * @return ?IUser
	 */
	public function getUser() {
		return $this->user;
	}

	public function limitToHome(): bool {
		return $this->limitToHome;
	}
}
