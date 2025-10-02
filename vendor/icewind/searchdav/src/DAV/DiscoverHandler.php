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
use Sabre\DAV\Xml\Response\MultiStatus;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;
use SearchDAV\Backend\ISearchBackend;
use SearchDAV\Backend\SearchPropertyDefinition;
use SearchDAV\XML\BasicSearch;
use SearchDAV\XML\BasicSearchSchema;
use SearchDAV\XML\PropDesc;
use SearchDAV\XML\QueryDiscoverResponse;
use SearchDAV\XML\Scope;

class DiscoverHandler {
	/** @var ISearchBackend */
	private $searchBackend;

	/** @var PathHelper */
	private $pathHelper;

	/** @var QueryParser */
	private $queryParser;

	/**
	 * @param ISearchBackend $searchBackend
	 * @param PathHelper $pathHelper
	 * @param QueryParser $queryParser
	 */
	public function __construct(ISearchBackend $searchBackend, PathHelper $pathHelper, QueryParser $queryParser) {
		$this->searchBackend = $searchBackend;
		$this->pathHelper = $pathHelper;
		$this->queryParser = $queryParser;
	}

	public function handelDiscoverRequest($xml, RequestInterface $request, ResponseInterface $response): bool {
		if (!isset($xml['{DAV:}basicsearch'])) {
			$response->setStatus(400);
			$response->setBody('Unexpected xml content for query-schema-discovery, expected basicsearch');
			return false;
		}
		/** @var BasicSearch $query */
		$query = $xml['{DAV:}basicsearch'];
		$scopes = $query->from;
		$results = array_map(function (Scope $scope) {
			$scope->path = $this->pathHelper->getPathFromUri($scope->href);
			if ($this->searchBackend->isValidScope($scope->href, $scope->depth, $scope->path)) {
				$searchProperties = $this->searchBackend->getPropertyDefinitionsForScope($scope->href, $scope->path);
				$searchSchema = $this->getBasicSearchForProperties($searchProperties);
				return new QueryDiscoverResponse($scope->href, $searchSchema, 200);
			} else {
				return new QueryDiscoverResponse($scope->href, null, 404); // TODO something other than 404? 403 maybe
			}
		}, $scopes);
		$multiStatus = new MultiStatus($results);
		$response->setStatus(207);
		$response->setHeader('Content-Type', 'application/xml; charset="utf-8"');
		$response->setBody($this->queryParser->write('{DAV:}multistatus', $multiStatus, $request->getUrl()));
		return false;
	}

	private function hashDefinition(SearchPropertyDefinition $definition): string {
		return $definition->dataType
			. (($definition->searchable) ? '1' : '0')
			. (($definition->sortable) ? '1' : '0')
			. (($definition->selectable) ? '1' : '0');
	}

	/**
	 * @param SearchPropertyDefinition[] $propertyDefinitions
	 * @return BasicSearchSchema
	 */
	private function getBasicSearchForProperties(array $propertyDefinitions): BasicSearchSchema {
		/** @var PropDesc[] $groups */
		$groups = [];
		foreach ($propertyDefinitions as $propertyDefinition) {
			$key = $this->hashDefinition($propertyDefinition);
			if (!isset($groups[$key])) {
				$groups[$key] = new PropDesc($propertyDefinition);
			}
			$groups[$key]->properties[] = $propertyDefinition->name;
		}

		return new BasicSearchSchema(array_values($groups));
	}
}
