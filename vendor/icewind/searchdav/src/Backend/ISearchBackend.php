<?php declare(strict_types=1);
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

namespace SearchDAV\Backend;

use Sabre\DAV\INode;
use SearchDAV\Query\Query;

interface ISearchBackend {
	/**
	 * Get the path of the search arbiter of this backend
	 *
	 * The search arbiter is the URI that the client will send its SEARCH requests to
	 * Note that this is not required to be the same as the search scopes which determine what to search in
	 *
	 * The returned value should be a path relative the root of the dav server.
	 *
	 * For example, if you want to support SEARCH requests on `https://example.com/dav.php/search`
	 * with the sabre/dav server listening on `/dav.php` you should return `search` as arbiter path.
	 *
	 * @return string
	 */
	public function getArbiterPath(): string;

	/**
	 * Whether the search backend supports search requests on this scope
	 *
	 * The scope defines the resource that it being searched, such as a folder or address book.
	 *
	 * Note that a search arbiter has no inherit limitations on which scopes it can support and scopes
	 * that reside on a different dav server entirely might be considered valid by an implementation.
	 *
	 * One example use case for this would be a service that provides additional indexing on a 3rd party service.
	 *
	 * @param string $href an absolute uri of the search scope
	 * @param string|integer $depth 0, 1 or 'infinite'
	 * @param string|null $path the path of the search scope relative to the dav server, or null if the scope is outside the dav server
	 * @return bool
	 */
	public function isValidScope(string $href, $depth, ?string $path): bool;

	/**
	 * List the available properties that can be used in search
	 *
	 * This is used to tell the search client what properties can be queried, used to filter and used to sort.
	 *
	 * Since sabre's PropFind handling mechanism is used to return the properties to the client, it's required that all
	 * properties which are listed as selectable have a PropFind handler set.
	 *
	 * @param string $href an absolute uri of the search scope
	 * @param string|null $path the path of the search scope relative to the dav server, or null if the scope is outside the dav server
	 * @return SearchPropertyDefinition[]
	 */
	public function getPropertyDefinitionsForScope(string $href, ?string $path): array;

	/**
	 * Preform the search request
	 *
	 * The search results consist of the uri for the found resource and an INode describing the resource
	 * To return the properties requested by the query sabre's existing PropFind method is used, thus the search implementation
	 * is not required to collect these properties and is free to ignore the `select` part of the query
	 *
	 * @param Query $query
	 * @return SearchResult[]
	 */
	public function search(Query $query): array;

	/**
	 * Called by the search plugin once the nodes to be returned have been found.
	 * This can be used to more efficiently load the requested properties for the results.
	 *
	 * @param INode[] $nodes
	 * @param string[] $requestProperties
	 */
	public function preloadPropertyFor(array $nodes, array $requestProperties): void;
}
