<?php
/**
 * @copyright 2017 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
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
