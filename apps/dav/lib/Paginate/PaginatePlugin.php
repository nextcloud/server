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
	public const PAGINATE_HEADER = 'X-NC-Paginate';
	public const PAGINATE_TOTAL_HEADER = 'X-NC-Paginate-Total';
	public const PAGINATE_TOKEN_HEADER = 'X-NC-Paginate-Token';
	public const PAGINATE_OFFSET_HEADER = 'X-NC-Paginate-Offset';
	public const PAGINATE_COUNT_HEADER = 'X-NC-Paginate-Count';

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
		$url = $request->getUrl();
		if (
			$request->hasHeader(self::PAGINATE_HEADER) &&
			(!$request->hasHeader(self::PAGINATE_TOKEN_HEADER) || !$this->cache->exists($url, $request->getHeader(self::PAGINATE_TOKEN_HEADER)))
		) {
			$pageSize = (int)$request->getHeader(self::PAGINATE_COUNT_HEADER) ?: $this->pageSize;
			$offset = (int)$request->getHeader(self::PAGINATE_OFFSET_HEADER);
			$copyIterator = new LimitedCopyIterator($fileProperties, $pageSize, $offset);
			['token' => $token, 'count' => $count] = $this->cache->store($url, $copyIterator);

			$fileProperties = $copyIterator->getRequestedItems();
			$this->server->httpResponse->addHeader(self::PAGINATE_HEADER, 'true');
			$this->server->httpResponse->addHeader(self::PAGINATE_TOKEN_HEADER, $token);
			$this->server->httpResponse->addHeader(self::PAGINATE_TOTAL_HEADER, (string)$count);
			$request->setHeader(self::PAGINATE_TOKEN_HEADER, $token);
		}
	}

	public function onMethod(RequestInterface $request, ResponseInterface $response) {
		$url = $this->server->httpRequest->getUrl();
		if (
			$request->hasHeader(self::PAGINATE_TOKEN_HEADER) &&
			$request->hasHeader(self::PAGINATE_OFFSET_HEADER) &&
			$this->cache->exists($url, $request->getHeader(self::PAGINATE_TOKEN_HEADER))
		) {
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
