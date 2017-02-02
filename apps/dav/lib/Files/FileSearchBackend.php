<?php
/**
 * @copyright Copyright (c) 2017 Robin Appelman <robin@icewind.nl>
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

namespace OCA\DAV\Files;

use OCA\DAV\Connector\Sabre\Directory;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\Tree;
use SearchDAV\Backend\ISearchBackend;
use SearchDAV\Backend\SearchPropertyDefinition;

class FileSearchBackend implements ISearchBackend {
	/** @var Tree */
	private $tree;

	/**
	 * FileSearchBackend constructor.
	 *
	 * @param Tree $tree
	 */
	public function __construct(Tree $tree) {
		$this->tree = $tree;
	}

	/**
	 * Search endpoint will be remote.php/dav/files
	 *
	 * @return string
	 */
	public function getArbiterPath() {
		return 'files';
	}

	public function isValidScope($href, $depth, $path) {
		// only allow scopes inside the dav server
		if (is_null($path)) {
			return false;
		}

		try {
			$node = $this->tree->getNodeForPath($path);
			return $node instanceof Directory;
		} catch (NotFound $e) {
			return false;
		}
	}

	public function getPropertyDefinitionsForScope($href, $path) {
		// all valid scopes support the same schema

		return [
			new SearchPropertyDefinition('{DAV:}getcontentlength', true, true, true, SearchPropertyDefinition::DATATYPE_NONNEGATIVE_INTEGER),
			new SearchPropertyDefinition('{DAV:}getcontenttype', true, true, true),
			new SearchPropertyDefinition('{DAV:}displayname', true, true, true),
			new SearchPropertyDefinition('{http://ns.nextcloud.com:}fileid', false, true, true, SearchPropertyDefinition::DATATYPE_NONNEGATIVE_INTEGER),
		];
	}
}
