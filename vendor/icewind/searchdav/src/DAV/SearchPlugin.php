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

use Sabre\DAV\INode;
use Sabre\DAV\PropFind;
use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;
use Sabre\Xml\ParseException;
use SearchDAV\Backend\ISearchBackend;
use SearchDAV\XML\SupportedQueryGrammar;

class SearchPlugin extends ServerPlugin {
	const SEARCHDAV_NS = 'https://github.com/icewind1991/SearchDAV/ns';

	/** @var ISearchBackend */
	private $searchBackend;

	/** @var QueryParser */
	private $queryParser;

	/** @var PathHelper */
	private $pathHelper;

	/** @var SearchHandler */
	private $search;

	/** @var DiscoverHandler */
	private $discover;

	public function __construct(ISearchBackend $searchBackend) {
		$this->searchBackend = $searchBackend;
		$this->queryParser = new QueryParser();
	}

	public function initialize(Server $server): void {
		$this->pathHelper = new PathHelper($server);
		$this->search = new SearchHandler($this->searchBackend, $this->pathHelper, $server);
		$this->discover = new DiscoverHandler($this->searchBackend, $this->pathHelper, $this->queryParser);
		$server->on('method:SEARCH', [$this, 'searchHandler']);
		$server->on('afterMethod:OPTIONS', [$this, 'optionHandler']);
		$server->on('propFind', [$this, 'propFindHandler']);
	}

	public function propFindHandler(PropFind $propFind, INode $node): void {
		if ($propFind->getPath() === $this->searchBackend->getArbiterPath()) {
			$propFind->handle('{DAV:}supported-query-grammar-set', new SupportedQueryGrammar());
		}
	}

	/**
	 * SEARCH is allowed for users files
	 *
	 * @param string $path
	 * @return string[]
	 */
	public function getHTTPMethods($path): array {
		$path = $this->pathHelper->getPathFromUri($path);
		if ($this->searchBackend->getArbiterPath() === $path) {
			return ['SEARCH'];
		} else {
			return [];
		}
	}

	public function optionHandler(RequestInterface $request, ResponseInterface $response): void {
		if ($request->getPath() === $this->searchBackend->getArbiterPath()) {
			$response->addHeader('DASL', '<DAV:basicsearch>');
		}
	}

	public function searchHandler(RequestInterface $request, ResponseInterface $response): bool {
		$contentType = $request->getHeader('Content-Type') ?? '';

		// Currently, we only support xml search queries
		if ((strpos($contentType, 'text/xml') === false) && (strpos($contentType, 'application/xml') === false)) {
			return true;
		}

		if ($request->getPath() !== $this->searchBackend->getArbiterPath()) {
			return true;
		}

		try {
			$xml = $this->queryParser->parse(
				$request->getBodyAsString(),
				$request->getUrl(),
				$documentType
			);
		} catch (ParseException $e) {
			$response->setStatus(400);
			$response->setBody('Parse error: ' . $e->getMessage());
			return false;
		}

		switch ($documentType) {
			case '{DAV:}searchrequest':
				return $this->search->handleSearchRequest($xml, $response);
			case '{DAV:}query-schema-discovery':
				return $this->discover->handelDiscoverRequest($xml, $request, $response);
			default:
				$response->setStatus(400);
				$response->setBody('Unexpected document type: ' . $documentType . ' for this Content-Type, expected {DAV:}searchrequest or {DAV:}query-schema-discovery');
				return false;
		}
	}
}
