<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Theming;

use OCP\AppFramework\Attribute\Consumable;

/**
 * Public api to access default strings and urls for your templates
 */
#[Consumable('33.0.0')]
interface IDefaults {
	/**
	 * Get base URL for the organisation behind your instance
	 * @since 33.0.0
	 */
	public function getBaseUrl(): string;

	/**
	 * Link to the desktop sync client
	 * @since 33.0.0
	 */
	public function getSyncClientUrl(): string;

	/**
	 * Link to the iOS client
	 * @since 33.0.0
	 */
	public function getiOSClientUrl(): string;

	/**
	 * Link to the Android client
	 * @since 33.0.0
	 */
	public function getAndroidClientUrl(): string;

	/**
	 * link to the Android client on F-Droid
	 * @since 33.0.0
	 */
	public function getFDroidClientUrl(): string;

	/**
	 * base URL to the documentation of your ownCloud instance
	 * @since 33.0.0
	 */
	public function getDocBaseUrl(): string;

	/**
	 * name of your Nextcloud instance (e.g. MyPrivateCloud)
	 * @since 33.0.0
	 */
	public function getName(): string;

	/**
	 * Name of the software product (defaults to Nextcloud)
	 *
	 * @since 33.0.0
	 */
	public function getProductName(): string;

	/**
	 * Entity behind your instance
	 * @since 33.0.0
	 */
	public function getEntity(): string;

	/**
	 * Slogan
	 * @since 33.0.0
	 */
	public function getSlogan(?string $lang = null): string;

	/**
	 * Footer, short version
	 * @since 33.0.0
	 */
	public function getShortFooter(): string;

	/**
	 * footer, long version
	 * @since 33.0.0
	 */
	public function getLongFooter(): string;

	/**
	 * Returns the AppId for the App Store for the iOS Client
	 * @since 33.0.0
	 */
	public function getiTunesAppId(): string;

	/**
	 * Themed logo url
	 *
	 * @param bool $useSvg Whether to point to the SVG image or a fallback
	 * @since 33.0.0
	 */
	public function getLogo(bool $useSvg = true): string;

	/**
	 * Returns primary color
	 * @since 33.0.0
	 */
	public function getColorPrimary(): string;

	/**
	 * Return the default color primary
	 * @since 33.0.0
	 */
	public function getDefaultColorPrimary(): string;

	/**
	 * @return string URL to doc with key
	 * @since 33.0.0
	 */
	public function buildDocLinkToKey(string $key): string;

	/**
	 * Returns the title
	 * @since 33.0.0
	 */
	public function getTitle(): string;

	/**
	 * Returns primary color
	 * @since 33.0.0
	 */
	public function getTextColorPrimary(): string;

	/**
	 * Returns primary color
	 * @since 33.0.0
	 */
	public function getDefaultTextColorPrimary(): string;
}
