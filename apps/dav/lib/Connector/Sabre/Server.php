<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\Connector\Sabre;

use OC\DB\Connection;
use Override;
use Sabre\DAV\Exception;
use Sabre\DAV\INode;
use Sabre\DAV\PropFind;
use Sabre\DAV\Version;
use TypeError;

/**
 * Class \OCA\DAV\Connector\Sabre\Server
 *
 * This class overrides some methods from @see \Sabre\DAV\Server.
 *
 * @see \Sabre\DAV\Server
 */
class Server extends \Sabre\DAV\Server {
	/** @var CachingTree $tree */

	/**
	 * Tracks queries done by plugins.
	 * @var array<string, array<int, array<string, array{nodes:int,
	 *     queries:int}>>> The keys represent: event name, depth and plugin name
	 */
	private array $pluginQueries = [];

	public bool $debugEnabled = false;

	/**
	 * @var array<string, array<int, callable>>
	 */
	private array $originalListeners = [];

	/**
	 * @var array<string, array<int, callable>>
	 */
	private array $wrappedListeners = [];

	/**
	 * @see \Sabre\DAV\Server
	 */
	public function __construct($treeOrNode = null) {
		parent::__construct($treeOrNode);
		self::$exposeVersion = false;
		$this->enablePropfindDepthInfinity = true;
	}

	#[Override]
	public function once(
		string $eventName,
		callable $callBack,
		int $priority = 100,
	): void {
		$this->debugEnabled ? $this->monitorPropfindQueries(
			parent::once(...),
			...\func_get_args(),
		) : parent::once(...\func_get_args());
	}

	#[Override]
	public function on(
		string $eventName,
		callable $callBack,
		int $priority = 100,
	): void {
		$this->debugEnabled ? $this->monitorPropfindQueries(
			parent::on(...),
			...\func_get_args(),
		) : parent::on(...\func_get_args());
	}

	/**
	 * Wraps the handler $callBack into a query-monitoring function and calls
	 * $parentFn to register it.
	 */
	private function monitorPropfindQueries(
		callable $parentFn,
		string $eventName,
		callable $callBack,
		int $priority = 100,
	): void {
		$pluginName = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3)[2]['class'] ?? 'unknown';
		// The NotifyPlugin needs to be excluded as it emits the
		// `preloadCollection` event, which causes many plugins run queries.
		/** @psalm-suppress TypeDoesNotContainType */
		if ($pluginName === PropFindPreloadNotifyPlugin::class || ($eventName !== 'propFind'
				&& $eventName !== 'preloadCollection')) {
			$parentFn($eventName, $callBack, $priority);
			return;
		}

		$wrappedCallback
			= $this->getMonitoredCallback($callBack, $pluginName, $eventName);
		$this->originalListeners[$eventName][] = $callBack;
		$this->wrappedListeners[$eventName][] = $wrappedCallback;

		$parentFn($eventName, $wrappedCallback, $priority);
	}

	public function removeListener(
		string $eventName,
		callable $listener,
	): bool {
		$listenerIndex = null;
		if (isset($this->wrappedListeners[$eventName], $this->originalListeners[$eventName])) {
			$key = array_search(
				$listener,
				$this->originalListeners[$eventName],
				true
			);
			if ($key !== false) {
				$listenerIndex = $key;
				$listener = $this->wrappedListeners[$eventName][$listenerIndex];
			}
		}
		$removed = parent::removeListener($eventName, $listener);

		if ($removed && $listenerIndex !== null) {
			unset($this->originalListeners[$eventName][$listenerIndex], $this->wrappedListeners[$eventName][$listenerIndex]);
		}

		return $removed;
	}

	public function removeAllListeners(?string $eventName = null): void {
		parent::removeAllListeners($eventName);

		if ($eventName === null) {
			$this->originalListeners = [];
			$this->wrappedListeners = [];
		} else {
			unset($this->wrappedListeners[$eventName], $this->originalListeners[$eventName]);
		}
	}

	/**
	 * Returns a callable that wraps $callBack with code that monitors and
	 * records queries per plugin.
	 */
	private function getMonitoredCallback(
		callable $callBack,
		string $pluginName,
		string $eventName,
	): callable {
		return function (PropFind $propFind, INode $node) use (
			$callBack,
			$pluginName,
			$eventName,
		): bool {
			$connection = \OCP\Server::get(Connection::class);
			$queriesBefore = $connection->getStats()['executed'];
			$result = $callBack($propFind, $node);
			$queriesAfter = $connection->getStats()['executed'];
			$this->trackPluginQueries(
				$pluginName,
				$eventName,
				$queriesAfter - $queriesBefore,
				$propFind->getDepth()
			);

			// many callbacks don't care about returning a bool
			return $result ?? true;
		};
	}

	/**
	 * Tracks the queries executed by a specific plugin.
	 */
	private function trackPluginQueries(
		string $pluginName,
		string $eventName,
		int $queriesExecuted,
		int $depth,
	): void {
		// report only nodes which cause queries to the DB
		if ($queriesExecuted === 0) {
			return;
		}

		$this->pluginQueries[$eventName][$depth][$pluginName]['nodes']
			= ($this->pluginQueries[$eventName][$depth][$pluginName]['nodes'] ?? 0) + 1;

		$this->pluginQueries[$eventName][$depth][$pluginName]['queries']
			= ($this->pluginQueries[$eventName][$depth][$pluginName]['queries'] ?? 0) + $queriesExecuted;
	}

	/**
	 *
	 * @return void
	 */
	public function start() {
		try {
			// If nginx (pre-1.2) is used as a proxy server, and SabreDAV as an
			// origin, we must make sure we send back HTTP/1.0 if this was
			// requested.
			// This is mainly because nginx doesn't support Chunked Transfer
			// Encoding, and this forces the webserver SabreDAV is running on,
			// to buffer entire responses to calculate Content-Length.
			$this->httpResponse->setHTTPVersion($this->httpRequest->getHTTPVersion());

			// Setting the base url
			$this->httpRequest->setBaseUrl($this->getBaseUri());
			$this->invokeMethod($this->httpRequest, $this->httpResponse);
		} catch (\Throwable $e) {
			try {
				$this->emit('exception', [$e]);
			} catch (\Exception) {
			}

			if ($e instanceof TypeError) {
				/*
				 * The TypeError includes the file path where the error occurred,
				 * potentially revealing the installation directory.
				 */
				$e = new TypeError('A type error occurred. For more details, please refer to the logs, which provide additional context about the type error.');
			}

			$DOM = new \DOMDocument('1.0', 'utf-8');
			$DOM->formatOutput = true;

			$error = $DOM->createElementNS('DAV:', 'd:error');
			$error->setAttribute('xmlns:s', self::NS_SABREDAV);
			$DOM->appendChild($error);

			$h = function ($v) {
				return htmlspecialchars((string)$v, ENT_NOQUOTES, 'UTF-8');
			};

			if (self::$exposeVersion) {
				$error->appendChild($DOM->createElement('s:sabredav-version', $h(Version::VERSION)));
			}

			$error->appendChild($DOM->createElement('s:exception', $h(get_class($e))));
			$error->appendChild($DOM->createElement('s:message', $h($e->getMessage())));
			if ($this->debugExceptions) {
				$error->appendChild($DOM->createElement('s:file', $h($e->getFile())));
				$error->appendChild($DOM->createElement('s:line', $h($e->getLine())));
				$error->appendChild($DOM->createElement('s:code', $h($e->getCode())));
				$error->appendChild($DOM->createElement('s:stacktrace', $h($e->getTraceAsString())));
			}

			if ($this->debugExceptions) {
				$previous = $e;
				while ($previous = $previous->getPrevious()) {
					$xPrevious = $DOM->createElement('s:previous-exception');
					$xPrevious->appendChild($DOM->createElement('s:exception', $h(get_class($previous))));
					$xPrevious->appendChild($DOM->createElement('s:message', $h($previous->getMessage())));
					$xPrevious->appendChild($DOM->createElement('s:file', $h($previous->getFile())));
					$xPrevious->appendChild($DOM->createElement('s:line', $h($previous->getLine())));
					$xPrevious->appendChild($DOM->createElement('s:code', $h($previous->getCode())));
					$xPrevious->appendChild($DOM->createElement('s:stacktrace', $h($previous->getTraceAsString())));
					$error->appendChild($xPrevious);
				}
			}

			if ($e instanceof Exception) {
				$httpCode = $e->getHTTPCode();
				$e->serialize($this, $error);
				$headers = $e->getHTTPHeaders($this);
			} else {
				$httpCode = 500;
				$headers = [];
			}
			$headers['Content-Type'] = 'application/xml; charset=utf-8';

			$this->httpResponse->setStatus($httpCode);
			$this->httpResponse->setHeaders($headers);
			$this->httpResponse->setBody($DOM->saveXML());
			$this->sapi->sendResponse($this->httpResponse);
		}
	}

	/**
	 * Returns queries executed by registered plugins.
	 * @return array<string, array<int, array<string, array{nodes:int,
	 *     queries:int}>>> The keys represent: event name, depth and plugin name
	 */
	public function getPluginQueries(): array {
		return $this->pluginQueries;
	}
}
