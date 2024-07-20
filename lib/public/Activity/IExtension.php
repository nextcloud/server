<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCP\Activity;

/**
 * Interface IExtension
 *
 * @since 8.0.0
 */
interface IExtension {
	/**
	 * @since 8.0.0
	 */
	public const METHOD_STREAM = 'stream';

	/**
	 * @since 8.0.0
	 */
	public const METHOD_MAIL = 'email';

	/**
	 * @since 20.0.0
	 */
	public const METHOD_NOTIFICATION = 'notification';

	/**
	 * @since 8.0.0
	 */
	public const PRIORITY_VERYLOW = 10;

	/**
	 * @since 8.0.0
	 */
	public const PRIORITY_LOW = 20;

	/**
	 * @since 8.0.0
	 */
	public const PRIORITY_MEDIUM = 30;

	/**
	 * @since 8.0.0
	 */
	public const PRIORITY_HIGH = 40;

	/**
	 * @since 8.0.0
	 */
	public const PRIORITY_VERYHIGH = 50;
}
