<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Dashboard;

/**
 * Interface IWidget
 *
 * @since 20.0.0
 */
interface IWidget {
	/**
	 * Get a unique identifier for the widget
	 *
	 * To ensure uniqueness, it is recommended to user the app id or start with the
	 * app id followed by a dash.
	 *
	 * @return string Unique id that identifies the widget, e.g. the app id. Only use alphanumeric characters, dash and underscore
	 * @since 20.0.0
	 */
	public function getId(): string;

	/**
	 * @return string User facing title of the widget
	 * @since 20.0.0
	 */
	public function getTitle(): string;

	/**
	 * @return int Initial order for widget sorting
	 * @since 20.0.0
	 */
	public function getOrder(): int;

	/**
	 * CSS class that shows the widget icon (should be colored black or not have a color)
	 *
	 * The icon will be inverted automatically in mobile clients and when using dark mode.
	 * Therefore, it is NOT recommended to use a css class that sets the background with:
	 * `var(--icon-…)` as those will adapt to dark/bright mode in the web and still be inverted
	 * resulting in a dark icon on dark background.
	 *
	 * @return string css class that displays an icon next to the widget title
	 * @since 20.0.0
	 */
	public function getIconClass(): string;

	/**
	 * @return string|null The absolute url to the apps own view
	 * @since 20.0.0
	 */
	public function getUrl(): ?string;

	/**
	 * Execute widget bootstrap code like loading scripts and providing initial state
	 * @since 20.0.0
	 */
	public function load(): void;
}
