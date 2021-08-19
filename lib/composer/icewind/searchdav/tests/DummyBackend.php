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

namespace SearchDAV\Test;

use Sabre\DAV\INode;
use Sabre\DAV\SimpleFile;
use SearchDAV\Backend\ISearchBackend;
use SearchDAV\Backend\SearchResult;
use SearchDAV\Query\Query;
use SearchDAV\XML\BasicSearch;
use SearchDAV\Backend\SearchPropertyDefinition;

class DummyBackend implements ISearchBackend {
	public function getArbiterPath() {
		return '';
	}

	public function isValidScope($href, $depth, $path) {
		return true;
	}

	public function getPropertyDefinitionsForScope($href, $path) {
		return [
			new SearchPropertyDefinition('{DAV:}getcontentlength', true, true, true, SearchPropertyDefinition::DATATYPE_NONNEGATIVE_INTEGER),
			new SearchPropertyDefinition('{DAV:}getcontenttype', true, true, true),
			new SearchPropertyDefinition('{DAV:}displayname', true, true, true),
			new SearchPropertyDefinition('{http://ns.nextcloud.com:}fileid', false, true, true, SearchPropertyDefinition::DATATYPE_NONNEGATIVE_INTEGER),
		];
	}

	public function search(Query $query) {
		return [
			new SearchResult(new SimpleFile('foo.txt', 'foobar', 'text/plain'), '/bar/foo.txt')
		];
	}
}
