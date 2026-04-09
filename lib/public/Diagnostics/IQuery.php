<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCP\Diagnostics;

/**
 * Interface IQuery
 *
 * @since 8.0.0
 */
interface IQuery {
	/**
	 * @return string
	 * @since 8.0.0
	 */
	public function getSql();

	/**
	 * @return array
	 * @since 8.0.0
	 */
	public function getParams();

	/**
	 * @return float
	 * @since 8.0.0
	 */
	public function getDuration();

	/**
	 * @return float
	 * @since 11.0.0
	 */
	public function getStartTime();

	/**
	 * @return array
	 * @since 11.0.0
	 */
	public function getStacktrace();
	/**
	 * @return array
	 * @since 12.0.0
	 */
	public function getStart();
}
