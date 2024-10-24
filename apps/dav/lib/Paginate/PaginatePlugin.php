<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\DAV\Paginate;

use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;

class PaginatePlugin extends ServerPlugin {
	public const PAGINATE_HEADER = 'x-nc-paginate';
	public const PAGINATE_TOTAL_HEADER = 'x-nc-paginate-total';
	public const PAGINATE_TOKEN_HEADER = 'x-nc-paginate-token';
	public const PAGINATE_OFFSET_HEADER = 'x-nc-paginate-offset';
	public const PAGINATE_COUNT_HEADER = 'x-nc-paginate-count';

	/** @var Server */
	private $server;

	public function __construct(
		private PaginateCache $cache,
		private int $pageSize = 100,
	) {
	}

	public function initialize(Server $server): void {
		$this->server = $server;
		$server->on('beforeMultiStatus', [$this, 'onMultiStatus']);
		$server->on('method:SEARCH', [$this, 'onMethod'], 1);
		$server->on('method:PROPFIND', [$this, 'onMethod'], 1);
		$server->on('method:REPORT', [$this, 'onMethod'], 1);
	}

	public function getFeatures(): array {
		return ['nc-paginate'];
	}

	public function onMultiStatus(&$fileProperties): void {
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
			['token' => $token, 'count' => $count] = $this->cache->store($url, $copyIterator);

			$fileProperties = $copyIterator->getFirstItems();
			$this->server->httpResponse->addHeader(self::PAGINATE_HEADER, 'true');
			$this->server->httpResponse->addHeader(self::PAGINATE_TOKEN_HEADER, $token);
			$this->server->httpResponse->addHeader(self::PAGINATE_TOTAL_HEADER, $count);
		}
	}

	public function onMethod(RequestInterface $request, ResponseInterface $response) {
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
