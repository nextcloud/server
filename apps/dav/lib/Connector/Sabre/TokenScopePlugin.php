<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Connector\Sabre;

use OC\Lockdown\LockdownManager;
use OCA\DAV\CalDAV\Plugin as CalDAVPlugin;
use OCA\DAV\TokenScopes;
use OCA\Files\TokenScopes as FilesTokenScopes;
use Sabre\CardDAV\Plugin as CardDAVPlugin;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\INode;
use Sabre\DAV\PropFind;
use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;
use Sabre\HTTP\RequestInterface;

/**
 * Limits app passwords with scopes to the DAV collections their scopes open
 */
class TokenScopePlugin extends ServerPlugin {
	private const READ_METHODS = ['GET', 'HEAD', 'OPTIONS', 'PROPFIND', 'REPORT', 'SEARCH'];

	/** Collections for files and their tags, plus bulk upload. Writes are guarded by the mount permissions */
	private const FILES_COLLECTIONS = ['files', 'uploads', 'bulk', 'trashbin', 'versions', 'groupfolders', 'systemtags', 'systemtags-relations', 'systemtags-assigned'];

	private Server $server;

	/**
	 * @param string $prefix prepended to every path, for servers whose tree is a single collection
	 */
	public function __construct(
		private LockdownManager $lockdownManager,
		private string $prefix = '',
	) {
	}

	#[\Override]
	public function initialize(Server $server): void {
		$this->server = $server;
		// After authentication (10), before the ACL plugin (20)
		$server->on('beforeMethod:*', [$this, 'beforeMethod'], 15);
	}

	public function beforeMethod(RequestInterface $request): void {
		if (!$this->lockdownManager->isScoped()) {
			return;
		}

		$method = $request->getMethod();
		$destination = $method === 'COPY' || $method === 'MOVE' ? $request->getHeader('Destination') : null;
		// COPY only reads its source
		if (!$this->allows($request->getPath(), $method === 'COPY' ? 'GET' : $method)
			|| ($destination !== null && !$this->allows($this->server->calculateUri($destination), $method))) {
			throw new Forbidden('Not allowed by the app password scopes');
		}

		// Registered here so unscoped requests skip the per-node check. Priority 0 runs before any handler that loads data
		$this->server->on('propFind', [$this, 'propFind'], 0);
	}

	/**
	 * Returning false hides the node from listings, multiget and expand-property
	 */
	public function propFind(PropFind $propFind, INode $node): ?bool {
		return $this->allows($propFind->getPath(), 'PROPFIND') ? null : false;
	}

	public function allows(string $path, string $method): bool {
		$segments = preg_split('#/+#', $this->prefix . $path, -1, PREG_SPLIT_NO_EMPTY);
		$write = !in_array($method, self::READ_METHODS, true);

		return match ($segments[0] ?? '') {
			'' => !$write,
			CalDAVPlugin::CALENDAR_ROOT, CalDAVPlugin::SYSTEM_CALENDAR_ROOT => $this->allowsGroupware($segments, $method, $write, TokenScopes::CALENDAR_READ, TokenScopes::CALENDAR_WRITE),
			CardDAVPlugin::ADDRESSBOOK_ROOT => $this->allowsGroupware($segments, $method, $write, TokenScopes::CONTACTS_READ, TokenScopes::CONTACTS_WRITE),
			'principals' => $write
				// Proxy membership grants access to all calendars, which counts as sharing
				? !in_array(end($segments), ['calendar-proxy-read', 'calendar-proxy-write'], true) && $this->lockdownManager->hasScope(TokenScopes::CALENDAR_WRITE)
				: $this->lockdownManager->hasScope(TokenScopes::CALENDAR_READ) || $this->lockdownManager->hasScope(TokenScopes::CONTACTS_READ),
			'avatars' => $method === 'GET' || $method === 'HEAD',
			default => in_array($segments[0], self::FILES_COLLECTIONS, true) && $this->lockdownManager->hasScope(FilesTokenScopes::FILES_READ),
		};
	}

	/**
	 * @param list<string> $segments
	 */
	private function allowsGroupware(array $segments, string $method, bool $write, string $readScope, string $writeScope): bool {
		// POST shares and publishes calendars and address books, except on the scheduling outbox
		if ($method === 'POST') {
			return $segments[0] === CalDAVPlugin::CALENDAR_ROOT && count($segments) === 3 && $segments[2] === 'outbox'
				&& $this->lockdownManager->hasScope($writeScope);
		}

		return $this->lockdownManager->hasScope($write ? $writeScope : $readScope);
	}
}
