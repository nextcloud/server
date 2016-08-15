<?php
/**
 *
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OCP\Share;

/**
 * Interface ILinkShareProvider
 *
 * @package OCP\Share
 * @since 9.2.0
 */
interface ILinkShareProvider extends IShareProvider {

	/**
	 * Link shares are special and personal in the sense that if they are
	 * reshares the owner can't do much with them. So link shares do have a
	 * parent share. This function is to find all shares that have $parent
	 * as a parent
	 *
	 * @param \OCP\Share\IShare $parent
	 * @return \OCP\Share\IShare[]
	 * @since 9.2.0
	 */
	public function getChildren(\OCP\Share\IShare $parent);
}
