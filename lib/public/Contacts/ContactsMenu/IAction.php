<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Contacts\ContactsMenu;

use JsonSerializable;

/**
 * Apps should use the IActionFactory to create new action objects
 *
 * @since 12.0
 */
interface IAction extends JsonSerializable {
	/**
	 * @param string $icon absolute URI to an icon
	 * @since 12.0
	 */
	public function setIcon(string $icon);

	/**
	 * @return string localized action name, e.g. 'Call'
	 * @since 12.0
	 */
	public function getName(): string;

	/**
	 * @param string $name localized action name, e.g. 'Call'
	 * @since 12.0
	 */
	public function setName(string $name);

	/**
	 * @param int $priority priorize actions, high order ones are shown on top
	 * @since 12.0
	 */
	public function setPriority(int $priority);

	/**
	 * @return int priority to priorize actions, high order ones are shown on top
	 * @since 12.0
	 */
	public function getPriority(): int;

	/**
	 * @param string $appId
	 * @since 23.0.0
	 */
	public function setAppId(string $appId);

	/**
	 * @return string
	 * @since 23.0.0
	 */
	public function getAppId(): string;
}
