<?php
/**
 * ownCloud
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

/**
 * @deprecated use \OCP\Search\Result instead
 */
class OC_Search_Result extends \OCP\Search\Result {
	/**
	 * Create a new search result
	 * @param string $id unique identifier from application: '[app_name]/[item_identifier_in_app]'
	 * @param string $name displayed text of result
	 * @param string $link URL to the result within its app
	 * @param string $type @deprecated because it is now set in \OC\Search\Result descendants
	 */
	public function __construct($id = null, $name = null, $link = null, $type = null) {
		$this->id = $id;
		$this->name = $name;
		$this->link = $link;
		$this->type = $type;
	}
}
