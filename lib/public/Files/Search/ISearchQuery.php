<?php
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Files\Search;

use OCP\IUser;

/**
 * @since 12.0.0
 */
interface ISearchQuery {
	/**
	 * @return ISearchOperator
	 * @since 12.0.0
	 */
	public function getSearchOperation();

	/**
	 * Get the maximum number of results to return
	 *
	 * @return integer
	 * @since 12.0.0
	 */
	public function getLimit();

	/**
	 * Get the offset for returned results
	 *
	 * @return integer
	 * @since 12.0.0
	 */
	public function getOffset();

	/**
	 * The fields and directions to order by
	 *
	 * @return ISearchOrder[]
	 * @since 12.0.0
	 */
	public function getOrder();

	/**
	 * The user that issued the search
	 *
	 * @return ?IUser
	 * @since 12.0.0
	 */
	public function getUser();

	/**
	 * Whether or not the search should be limited to the users home storage
	 *
	 * @return bool
	 * @since 18.0.0
	 */
	public function limitToHome(): bool;
}
