<?php
/**
 * @copyright Copyright (c) 2018 Robin Appelman <robin@icewind.nl>
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

namespace OCA\DAV\Paginate;

use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;

class PaginatePlugin extends ServerPlugin {
	const PAGINATE_HEADER = 'x-nc-paginate';
	const PAGINATE_TOTAL_HEADER = 'x-nc-paginate-total';
	const PAGINATE_TOKEN_HEADER = 'x-nc-paginate-token';
	const PAGINATE_OFFSET_HEADER = 'x-nc-paginate-offset';
	const PAGINATE_COUNT_HEADER = 'x-nc-paginate-count';

	/** @var int */
	private $pageSize;

	/** @var Server */
	private $server;

	/** @var PaginateCache */
	private $cache;

	public function __construct(PaginateCache $cache, int $pageSize = 100) {
		$this->cache = $cache;
		$this->pageSize = $pageSize;
	}

	function initialize(Server $server) {
		$this->server = $server;
		$server->on('beforeMultiStatus', [$this, 'onMultiStatus']);
		$server->on('method:SEARCH', [$this, 'onMethod'], 1);
		$server->on('method:PROPFIND', [$this, 'onMethod'], 1);
		$server->on('method:REPORT', [$this, 'onMethod'], 1);
	}

	public function getFeatures() {
		return ['nc-paginate'];
	}

	function onMultiStatus(&$fileProperties) {
		$request = $this->server->httpRequest;
		if (is_array($fileProperties)) {
			$fileProperties = new \ArrayIterator($fileProperties);
		}
		if (
			$request->hasHeader(self::PAGINATE_HEADER) &&
			!$request->hasHeader(self::PAGINATE_TOKEN_HEADER)
		) {
			$url = $request->getUrl();

			$pageSize = (int)$request->getHeader(self::PAGINATE_COUNT_HEADER) ?: $this->pageSize;
			$copyIterator = new LimitedCopyIterator($fileProperties, $pageSize);
			list($token, $count) = $this->cache->store($url, $copyIterator);

			$fileProperties = $copyIterator->getFirstItems();
			$this->server->httpResponse->addHeader(self::PAGINATE_HEADER, 'true');
			$this->server->httpResponse->addHeader(self::PAGINATE_TOKEN_HEADER, $token);
			$this->server->httpResponse->addHeader(self::PAGINATE_TOTAL_HEADER, $count);
		}
	}

	function onMethod(RequestInterface $request, ResponseInterface $response) {
		if (
			$request->hasHeader(self::PAGINATE_TOKEN_HEADER) &&
			$request->hasHeader(self::PAGINATE_OFFSET_HEADER)
		) {
			$url = $this->server->httpRequest->getUrl();
			$token = $request->getHeader(self::PAGINATE_TOKEN_HEADER);
			$offset = (int)$request->getHeader(self::PAGINATE_OFFSET_HEADER);
			$count = (int)$request->getHeader(self::PAGINATE_COUNT_HEADER) ?: $this->pageSize;

			$items = $this->cache->get($url, $token, $offset, $count);

			$response->setStatus(207);
			$response->addHeader(self::PAGINATE_HEADER, 'true');
			$response->setHeader('Content-Type', 'application/xml; charset=utf-8');
			$response->setHeader('Vary', 'Brief,Prefer');

			$prefer = $this->server->getHTTPPrefer();
			$minimal = $prefer['return'] === 'minimal';

			$data = $this->server->generateMultiStatus($items, $minimal);
			$response->setBody($data);
			return false;
		}
	}

}