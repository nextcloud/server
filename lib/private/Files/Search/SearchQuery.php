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
	/** @var ISearchOperator */
	private $searchOperation;
	/** @var integer */
	private $limit;
	/** @var integer */
	private $offset;
	/** @var ISearchOrder[] */
	private $order;
	/** @var ?IUser */
	private $user;
	private $limitToHome;
	/** @var array */
	private $selectFields;

	/**
	 * SearchQuery constructor.
	 *
	 * @param ISearchOperator $searchOperation
	 * @param int $limit
	 * @param int $offset
	 * @param array $order
	 * @param ?IUser $user
	 * @param bool $limitToHome
	 * @param list<string> $selectFields
	 */
	public function __construct(
		ISearchOperator $searchOperation,
		int $limit,
		int $offset,
		array $order,
		?IUser $user = null,
		bool $limitToHome = false,
		array $selectFields = [],
	) {
		$this->searchOperation = $searchOperation;
		$this->limit = $limit;
		$this->offset = $offset;
		$this->order = $order;
		$this->user = $user;
		$this->limitToHome = $limitToHome;
		$this->selectFields = $selectFields;
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

	#[\Override]
	public function getSelectFields(): array {
		return $this->selectFields;
	}
}
