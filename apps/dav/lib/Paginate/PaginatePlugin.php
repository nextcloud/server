<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\DAV\Paginate;

use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;
use Sabre\DAV\Xml\Element\Response;
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
			$request->hasHeader(self::PAGINATE_HEADER)
			&& (!$request->hasHeader(self::PAGINATE_TOKEN_HEADER) || !$this->cache->exists($url, $request->getHeader(self::PAGINATE_TOKEN_HEADER)))
		) {
			$pageSize = (int)$request->getHeader(self::PAGINATE_COUNT_HEADER) ?: $this->pageSize;
			$offset = (int)$request->getHeader(self::PAGINATE_OFFSET_HEADER);

			$copyIterator = new LimitedCopyIterator($fileProperties, $pageSize, $offset);
			// wrap the iterator with another that renders XML, this way we
			// cache XML, but we keep the first $pageSize elements as objects
			// to use for the response of the first page.
			$rendererGenerator = $this->getXmlRendererGenerator($copyIterator);
			['token' => $token, 'count' => $count] = $this->cache->store($url, $rendererGenerator);

			$fileProperties = $copyIterator->getRequestedItems();
			$this->server->httpResponse->addHeader(self::PAGINATE_HEADER, 'true');
			$this->server->httpResponse->addHeader(self::PAGINATE_TOKEN_HEADER, $token);
			$this->server->httpResponse->addHeader(self::PAGINATE_TOTAL_HEADER, (string)$count);
			$request->setHeader(self::PAGINATE_TOKEN_HEADER, $token);
		}
	}

	/**
	 * Returns a generator that yields rendered XML entries for the provided
	 * $fileProperties, as they would appear in the MultiStatus response.
	 */
	private function getXmlRendererGenerator(iterable $fileProperties): \Generator {
		$writer = $this->server->xml->getWriter();
		$prefer = $this->server->getHTTPPrefer();
		$minimal = $prefer['return'] === 'minimal';
		$writer->contextUri = $this->server->getBaseUri();

		$writer->openMemory();
		$writer->startDocument();
		$writer->startElement('{DAV:}multistatus');

		// throw away the beginning of the document
		$writer->flush();

		foreach ($fileProperties as $entry) {
			$href = $entry['href'];
			unset($entry['href']);
			if ($minimal) {
				unset($entry[404]);
			}
			$response = new Response(
				ltrim($href, '/'),
				$entry
			);
			$writer->write([
				'name' => '{DAV:}response',
				'value' => $response,
			]);

			// flushing does not remove the > for the previous element
			// (multistatus)
			yield ltrim($writer->flush(), '>');
		}
	}

	public function onMethod(RequestInterface $request, ResponseInterface $response) {
		$url = $this->server->httpRequest->getUrl();
		if (
			$request->hasHeader(self::PAGINATE_TOKEN_HEADER)
			&& $request->hasHeader(self::PAGINATE_OFFSET_HEADER)
			&& $this->cache->exists($url, $request->getHeader(self::PAGINATE_TOKEN_HEADER))
		) {
			$token = $request->getHeader(self::PAGINATE_TOKEN_HEADER);
			$offset = (int)$request->getHeader(self::PAGINATE_OFFSET_HEADER);
			$count = (int)$request->getHeader(self::PAGINATE_COUNT_HEADER) ?: $this->pageSize;

			$items = $this->cache->get($url, $token, $offset, $count);

			$response->setStatus(207);
			$response->addHeader(self::PAGINATE_HEADER, 'true');
			$response->setHeader('Content-Type', 'application/xml; charset=utf-8');
			$response->setHeader('Vary', 'Brief,Prefer');

			// as we cached strings of XML, rebuild the multistatus response
			// and output the RAW entries, as stored in the cache
			$writer = $this->server->xml->getWriter();
			$writer->contextUri = $this->server->getBaseUri();
			$writer->openMemory();
			$writer->startDocument();
			$writer->startElement('{DAV:}multistatus');
			foreach ($items as $item) {
				$writer->writeRaw($item);
			}
			$writer->endElement();
			$writer->endDocument();

			$response->setBody($writer->flush());

			return false;
		}
	}
}
