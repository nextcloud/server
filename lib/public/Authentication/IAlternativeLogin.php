<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Authentication;

/**
 * @since 20.0.0
 */
interface IAlternativeLogin {
	/**
	 * Label shown on the login option
	 * @return string
	 * @since 20.0.0
	 */
	public function getLabel(): string;

	/**
	 * Relative link to the login option
	 * @return string
	 * @since 20.0.0
	 */
	public function getLink(): string;

	/**
	 * CSS classes added to the alternative login option on the login screen
	 * @return string
	 * @since 20.0.0
	 */
	public function getClass(): string;

	/**
	 * Load necessary resources to present the login option, e.g. style-file to style the getClass()
	 * @since 20.0.0
	 */
	public function load(): void;
}
