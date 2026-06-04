<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCP\Diagnostics;

/**
 * Interface IEvent
 *
 * @since 8.0.0
 */
interface IEvent {
	/**
	 * @return string
	 * @since 8.0.0
	 */
	public function getId();

	/**
	 * @return string
	 * @since 8.0.0
	 */
	public function getDescription();

	/**
	 * @return float
	 * @since 8.0.0
	 */
	public function getStart();

	/**
	 * @return float
	 * @since 8.0.0
	 */
	public function getEnd();

	/**
	 * @return float
	 * @since 8.0.0
	 */
	public function getDuration();
}
