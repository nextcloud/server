<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Theming;

/**
 * Interface ITheme
 *
 * @since 25.0.0
 */
interface ITheme {

	public const TYPE_THEME = 1;
	public const TYPE_FONT = 2;

	/**
	 * Unique theme id
	 * Will be used to search for ID.png in the img folder
	 *
	 * @since 25.0.0
	 */
	public function getId(): string;

	/**
	 * Theme type
	 * TYPE_THEME or TYPE_FONT
	 *
	 * @since 25.0.0
	 */
	public function getType(): int;

	/**
	 * The theme translated title
	 *
	 * @since 25.0.0
	 */
	public function getTitle(): string;

	/**
	 * The theme enable checkbox translated label
	 *
	 * @since 25.0.0
	 */
	public function getEnableLabel(): string;

	/**
	 * The theme translated description
	 *
	 * @since 25.0.0
	 */
	public function getDescription(): string;

	/**
	 * Get the meta attribute matching the theme
	 * e.g. https://html.spec.whatwg.org/multipage/semantics.html#meta-color-scheme
	 * @return array{name?: string, content?: string}[]
	 * @since 29.0.0
	 */
	public function getMeta(): array;

	/**
	 * Get the media query triggering this theme
	 * Optional, ignored if falsy
	 *
	 * @return string
	 * @since 25.0.0
	 */
	public function getMediaQuery(): string;

	/**
	 * Return the list of changed css variables
	 *
	 * @return array
	 * @since 25.0.0
	 */
	public function getCSSVariables(): array;

	/**
	 * Return the custom css necessary for that app
	 * ⚠️ Warning, should be used slightly.
	 * Theoretically, editing the variables should be enough.
	 *
	 * @return string
	 * @since 25.0.0
	 */
	public function getCustomCss(): string;
}
