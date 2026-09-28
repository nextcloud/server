<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\FullTextSearch\Service;

use OCP\FullTextSearch\Model\IIndex;

/**
 * Interface IIndexService
 *
 * @since 15.0.0
 *
 */
interface IIndexService {
	/**
	 * Create an Index
	 *
	 * @since 15.0.1
	 *
	 * @param string $providerId
	 * @param string $documentId
	 * @param string $userId
	 * @param int $status
	 * @return IIndex
	 */
	public function createIndex(string $providerId, string $documentId, string $userId, int $status): IIndex;


	/**
	 * Retrieve an Index from the database, based on the Id of the Provider
	 * and the Id of the Document
	 *
	 * @since 15.0.0
	 *
	 * @param string $providerId
	 * @param string $documentId
	 *
	 * @return IIndex
	 */
	public function getIndex(string $providerId, string $documentId): IIndex;


	/**
	 * Update the status of an Index. status is a bit flag, setting $reset to
	 * true will reset the status to the value defined in the parameter.
	 *
	 * @since 15.0.0
	 *
	 * @param string $providerId
	 * @param string $documentId
	 * @param int $status
	 * @param bool $reset
	 */
	public function updateIndexStatus(string $providerId, string $documentId, int $status, bool $reset = false);


	/**
	 * Update the status of an array of Index. status is a bit flag, setting $reset to
	 * true will reset the status to the value defined in the parameter.
	 *
	 * @since 15.0.0
	 *
	 * @param string $providerId
	 * @param array $documentIds
	 * @param int $status
	 * @param bool $reset
	 */
	public function updateIndexesStatus(string $providerId, array $documentIds, int $status, bool $reset = false);


	/**
	 * Update an array of Index.
	 *
	 * @since 15.0.0
	 *
	 * @param array $indexes
	 */
	public function updateIndexes(array $indexes);
}
