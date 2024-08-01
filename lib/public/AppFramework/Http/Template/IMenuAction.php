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
	 * @since 14.0.0
	 * @return string
	 */
	public function getLabel(): string;

	/**
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
	 * @since 14.0.0
	 * @return string
	 */
	public function render(): string;
}
