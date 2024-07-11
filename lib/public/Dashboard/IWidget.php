<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2020 Julius Härtl <jus@bitgrid.net>
 *
 * @author Julius Härtl <jus@bitgrid.net>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
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
