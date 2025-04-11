<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCP\Diagnostics;

use Doctrine\DBAL\Logging\SQLLogger;

/**
 * Interface IQueryLogger
 *
 * @since 8.0.0
 */
interface IQueryLogger extends SQLLogger {
	/**
	 * Mark the start of a query providing query SQL statement, its parameters and types.
	 * This method should be called as close to the DB as possible and after
	 * query is finished finalized with stopQuery() method.
	 *
	 * @param string $sql
	 * @param array|null $params
	 * @param array|null $types
	 * @since 8.0.0
	 */
	public function startQuery($sql, ?array $params = null, ?array $types = null);

	/**
	 * Mark the end of the current active query. Ending query should store \OCP\Diagnostics\IQuery to
	 * be returned with getQueries() method.
	 *
	 * @return mixed
	 * @since 8.0.0
	 */
	public function stopQuery();

	/**
	 * This method should return all \OCP\Diagnostics\IQuery objects stored using
	 * startQuery()/stopQuery() methods.
	 *
	 * @return \OCP\Diagnostics\IQuery[]
	 * @since 8.0.0
	 */
	public function getQueries();

	/**
	 * Activate the module for the duration of the request. Deactivated module
	 * does not create and store \OCP\Diagnostics\IQuery objects.
	 * Only activated module should create and store objects to be
	 * returned with getQueries() call.
	 *
	 * @since 12.0.0
	 */
	public function activate();
}
