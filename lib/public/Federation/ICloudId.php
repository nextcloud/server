<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Federation;

/**
 * Parsed federated cloud id
 *
 * @since 12.0.0
 */
interface ICloudId {
	/**
	 * The remote cloud id
	 *
	 * @return string
	 * @since 12.0.0
	 */
	public function getId(): string;

	/**
	 * Get a clean representation of the cloud id for display
	 *
	 * @return string
	 * @since 12.0.0
	 */
	public function getDisplayId(): string;

	/**
	 * The username on the remote server
	 *
	 * @return string
	 * @since 12.0.0
	 */
	public function getUser(): string;

	/**
	 * The base address of the remote server
	 *
	 * @return string
	 * @since 12.0.0
	 */
	public function getRemote(): string;
}
