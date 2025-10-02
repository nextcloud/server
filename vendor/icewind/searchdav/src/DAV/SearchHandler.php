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

namespace SearchDAV\DAV;

use Sabre\DAV\Exception\BadRequest;
use Sabre\DAV\INode;
use Sabre\DAV\PropFind;
use Sabre\DAV\Server;
use Sabre\HTTP\ResponseInterface;
use SearchDAV\Backend\ISearchBackend;
use SearchDAV\Backend\SearchPropertyDefinition;
use SearchDAV\Backend\SearchResult;
use SearchDAV\Query\Operator;
use SearchDAV\Query\Order;
use SearchDAV\Query\Query;
use SearchDAV\XML\BasicSearch;

class SearchHandler {
	/** @var ISearchBackend */
	private $searchBackend;

	/** @var PathHelper */
	private $pathHelper;

	/** @var Server */
	private $server;

	/**
	 * @param ISearchBackend $searchBackend
	 * @param PathHelper $pathHelper
	 * @param Server $server
	 */
	public function __construct(ISearchBackend $searchBackend, PathHelper $pathHelper, Server $server) {
		$this->searchBackend = $searchBackend;
		$this->pathHelper = $pathHelper;
		$this->server = $server;
	}

	public function handleSearchRequest($xml, ResponseInterface $response): bool {
		if (!isset($xml['{DAV:}basicsearch'])) {
			$response->setStatus(400);
			$response->setBody('Unexpected xml content for search request, expected basicsearch');
			return false;
		}
		/** @var BasicSearch $query */
		$query = $xml['{DAV:}basicsearch'];
		if (!$query->select) {
			$response->setStatus(400);
			$response->setBody('Parse error: Missing {DAV:}select from {DAV:}basicsearch');
			return false;
		}
		$response->setStatus(207);
		$response->setHeader('Content-Type', 'application/xml; charset="utf-8"');
		$allProps = [];
		foreach ($query->from as $scope) {
			$scope->path = $this->pathHelper->getPathFromUri($scope->href);
			$props = $this->searchBackend->getPropertyDefinitionsForScope($scope->href, $scope->path);
			foreach ($props as $prop) {
				$allProps[$prop->name] = $prop;
			}
		}
		try {
			$results = $this->searchBackend->search($this->getQueryForXML($query, $allProps));
		} catch (BadRequest $e) {
			$response->setStatus(400);
			$response->setBody($e->getMessage());
			return false;
		}
		$data = $this->server->generateMultiStatus(iterator_to_array($this->getPropertiesIteratorResults(
			$results,
			$query->select
		)), false);
		$response->setBody($data);
		return false;
	}

	/**
	 * @param BasicSearch $xml
	 * @param SearchPropertyDefinition[] $allProps
	 * @return Query
	 * @throws BadRequest
	 */
	private function getQueryForXML(BasicSearch $xml, array $allProps): Query {
		$orderBy = array_map(function (\SearchDAV\XML\Order $order) use ($allProps) {
			if (!isset($allProps[$order->property])) {
				throw new BadRequest('requested order by property is not a valid property for this scope');
			}
			$prop = $allProps[$order->property];
			if (!$prop->sortable) {
				throw new BadRequest('requested order by property is not sortable');
			}
			return new Order($prop, $order->order);
		}, $xml->orderBy);
		$select = array_map(function ($propName) use ($allProps) {
			if (!isset($allProps[$propName])) {
				return null;
			}
			$prop = $allProps[$propName];
			if (!$prop->selectable) {
				throw new BadRequest('requested property is not selectable');
			}
			return $prop;
		}, $xml->select);
		$select = array_filter($select);

		$where = $xml->where ? $this->transformOperator($xml->where, $allProps) : null;

		return new Query($select, $xml->from, $where, $orderBy, $xml->limit);
	}

	/**
	 * @param \SearchDAV\XML\Operator $operator
	 * @param SearchPropertyDefinition[] $allProps
	 * @return Operator
	 * @throws BadRequest
	 */
	private function transformOperator(\SearchDAV\XML\Operator $operator, array $allProps): Operator {
		$arguments = array_map(function ($argument) use ($allProps) {
			if (is_string($argument)) {
				if (!isset($allProps[$argument])) {
					throw new BadRequest('requested search property is not a valid property for this scope');
				}
				$prop = $allProps[$argument];
				if (!$prop->searchable) {
					throw new BadRequest('requested search property is not searchable');
				}
				return $prop;
			} else {
				if ($argument instanceof \SearchDAV\XML\Operator) {
					return $this->transformOperator($argument, $allProps);
				} else {
					return $argument;
				}
			}
		}, $operator->arguments);

		return new Operator($operator->type, $arguments);
	}

	/**
	 * Returns a list of properties for a given path
	 *
	 * The path that should be supplied should have the baseUrl stripped out
	 * The list of properties should be supplied in Clark notation. If the list is empty
	 * 'allprops' is assumed.
	 *
	 * If a depth of 1 is requested child elements will also be returned.
	 *
	 * @param SearchResult[] $results
	 * @param string[] $propertyNames
	 * @param int $depth
	 * @return \Iterator<array>
	 */
	private function getPropertiesIteratorResults(array $results, array $propertyNames = [], int $depth = 0): \Iterator {
		$propFindType = $propertyNames ? PropFind::NORMAL : PropFind::ALLPROPS;

		$this->searchBackend->preloadPropertyFor(array_map(function (SearchResult $result): INode {
			return $result->node;
		}, $results), $propertyNames);

		foreach ($results as $result) {
			$node = $result->node;
			$propFind = new PropFind($result->href, $propertyNames, $depth, $propFindType);
			$r = $this->server->getPropertiesByNode($propFind, $node);
			if ($r) {
				$result = $propFind->getResultForMultiStatus();
				$result['href'] = $propFind->getPath();

				// WebDAV recommends adding a slash to the path, if the path is
				// a collection.
				// Furthermore, iCal also demands this to be the case for
				// principals. This is non-standard, but we support it.
				$resourceType = $this->server->getResourceTypeForNode($node);
				if (in_array('{DAV:}collection', $resourceType) || in_array('{DAV:}principal', $resourceType)) {
					$result['href'] .= '/';
				}
				yield $result;
			}
		}
	}
}
