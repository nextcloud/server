<?php
/**
 * @author Joas Schilling <nickvergessen@owncloud.com>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCP\Notification;

/**
 * Interface IAction
 *
 * @package OCP\Notification
 * @since 9.0.0
 */
interface IAction {
	/**
	 * @param string $label
	 * @return $this
	 * @throws \InvalidArgumentException if the label is invalid
	 * @since 9.0.0
	 */
	public function setLabel($label);

	/**
	 * @return string
	 * @since 9.0.0
	 */
	public function getLabel();

	/**
	 * @param string $label
	 * @return $this
	 * @throws \InvalidArgumentException if the label is invalid
	 * @since 9.0.0
	 */
	public function setParsedLabel($label);

	/**
	 * @return string
	 * @since 9.0.0
	 */
	public function getParsedLabel();

	/**
	 * @param $primary bool
	 * @return $this
	 * @throws \InvalidArgumentException if $primary is invalid
	 * @since 9.0.0
	 */
	public function setPrimary($primary);

	/**
	 * @return bool
	 * @since 9.0.0
	 */
	public function isPrimary();

	/**
	 * @param string $link
	 * @param string $requestType
	 * @return $this
	 * @throws \InvalidArgumentException if the link is invalid
	 * @since 9.0.0
	 */
	public function setLink($link, $requestType);

	/**
	 * @return string
	 * @since 9.0.0
	 */
	public function getLink();

	/**
	 * @return string
	 * @since 9.0.0
	 */
	public function getRequestType();

	/**
	 * @return bool
	 * @since 9.0.0
	 */
	public function isValid();

	/**
	 * @return bool
	 * @since 9.0.0
	 */
	public function isValidParsed();
}
