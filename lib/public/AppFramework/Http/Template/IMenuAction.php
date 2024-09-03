<?php
/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\AppFramework\Http\Template;

/**
 * Interface IMenuAction
 *
 * @since 14.0
 */
interface IMenuAction {
	/**
	 * @since 14.0.0
	 * @return string
	 */
	public function getId(): string;

	/**
	 * The translated label of the menu item.
	 *
	 * @since 14.0.0
	 * @return string
	 */
	public function getLabel(): string;

	/**
	 * The link this menu item points to.
	 *
	 * @since 14.0.0
	 * @return string
	 */
	public function getLink(): string;

	/**
	 * @since 14.0.0
	 * @return int
	 */
	public function getPriority(): int;

	/**
	 * Custom render function.
	 * The returned HTML will be wrapped within a listitem element (`<li>...</li>`).
	 *
	 * @since 14.0.0
	 * @return string
	 */
	public function render(): string;
}
