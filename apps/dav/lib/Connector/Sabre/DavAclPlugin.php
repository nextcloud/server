<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\DAV\Connector\Sabre;

use Sabre\CalDAV\Calendar;
use Sabre\CalDAV\CalendarHome;
use Sabre\CalDAV\Principal\User;
use Sabre\CalDAV\Xml\Request\CalendarMultiGetReport;
use Sabre\CardDAV\AddressBook;
use Sabre\CardDAV\AddressBookHome;
use Sabre\CardDAV\Xml\Request\AddressBookMultiGetReport;
use Sabre\DAV\Exception as DavException;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\INode;
use Sabre\DAV\PropFind;
use Sabre\DAV\Server;
use Sabre\DAVACL\IACL;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;

/**
 * Class DavAclPlugin is a wrapper around \Sabre\DAVACL\Plugin that returns 404
 * responses in case the resource to a response has been forbidden instead of
 * a 403. This is used to prevent enumeration of valid resources.
 *
 * @see https://github.com/owncloud/core/issues/22578
 * @package OCA\DAV\Connector\Sabre
 */
class DavAclPlugin extends \Sabre\DAVACL\Plugin {
	public function __construct() {
		$this->hideNodesFromListings = true;
		$this->allowUnauthenticatedAccess = false;
	}

	#[\Override]
	public function initialize(Server $server) {
		parent::initialize($server);
		// Before the CalDAV and CardDAV plugins resolve the hrefs
		$server->on('report', [$this, 'beforeReport'], 50);
	}

	#[\Override]
	public function checkPrivileges($uri, $privileges, $recursion = self::R_PARENT, $throwExceptions = true) {
		$access = parent::checkPrivileges($uri, $privileges, $recursion, false);
		if ($access === false && $throwExceptions) {
			$node = $this->server->tree->getNodeForPath($uri);
			if ($node instanceof IACL && $this->getCurrentUserPrincipal() === $node->getOwner()) {
				throw new Forbidden('Access denied');
			}

			if (!$this->hidesUnreadablePaths($uri)) {
				throw new NotFound($this->getNotFoundMessage($uri));
			}
			$hiddenPath = $this->getTopmostUnreadablePath($uri);
			throw new NotFound($this->getHiddenNotFoundMessage($uri, $hiddenPath, $this->server->tree->getNodeForPath($hiddenPath)));
		}

		return $access;
	}

	#[\Override]
	public function propFind(PropFind $propFind, INode $node) {
		if ($node instanceof Node) {
			// files don't use dav acls
			return;
		}

		// If the node is neither readable nor writable then fail unless its of
		// the standard user-principal
		if (!($node instanceof User)) {
			$path = $propFind->getPath();
			$readPermissions = $this->checkPrivileges($path, '{DAV:}read', self::R_PARENT, false);
			$writePermissions = $this->checkPrivileges($path, '{DAV:}write', self::R_PARENT, false);
			if ($readPermissions === false && $writePermissions === false) {
				$this->checkPrivileges($path, '{DAV:}read', self::R_PARENT, true);
				$this->checkPrivileges($path, '{DAV:}write', self::R_PARENT, true);
			}
		}

		return parent::propFind($propFind, $node);
	}

	#[\Override]
	public function beforeMethod(RequestInterface $request, ResponseInterface $response) {
		$path = $request->getPath();

		$this->hideUnreadableIfConditions($request);
		if ($request->getMethod() === 'COPY' || $request->getMethod() === 'MOVE') {
			$this->hideUnreadableDestination($request);
		}

		// prevent the plugin from causing an unneeded overhead for file requests
		if (str_starts_with($path, 'files/')) {
			return;
		}

		$this->hideUnreadablePath($path);

		parent::beforeMethod($request, $response);

		if (!$this->hidesUnreadablePaths($path)) {
			return;
		}

		[$parentName] = \Sabre\Uri\split($path);
		if ($request->getMethod() === 'REPORT') {
			// is calendars/users/bob or addressbooks/users/bob readable?
			$this->checkPrivileges($parentName, '{DAV:}read');
		} elseif ($request->getMethod() === 'MKCALENDAR' || $request->getMethod() === 'MKCOL') {
			// is calendars/users/bob or addressbooks/users/bob writable?
			$this->checkPrivileges($parentName, '{DAV:}write');
		}
	}

	/**
	 * Called for the request path and for every href that is expanded.
	 *
	 * @param string $path Sabre documents it as array, but passes the href
	 * @psalm-suppress ImplementedParamTypeMismatch
	 * @psalm-suppress InvalidArgument
	 * @throws NotFound
	 */
	#[\Override]
	protected function expandProperties($path, array $requestedProperties, $depth) {
		$this->hideUnreadablePath(trim($path, '/'));
		return parent::expandProperties($path, $requestedProperties, $depth);
	}

	/**
	 * Answers requests below a collection the current user cannot read the same
	 * way, whether the requested node exists or not. Sabre only checks privileges
	 * on existing nodes.
	 *
	 * @throws NotFound
	 */
	private function hideUnreadablePath(string $path): void {
		if (!$this->hidesUnreadablePaths($path)) {
			return;
		}

		$principal = $this->getCurrentUserPrincipal();
		if ($principal === null) {
			return;
		}

		$existingPath = $path;
		while ($existingPath !== '' && !$this->server->tree->nodeExists($existingPath)) {
			[$existingPath] = \Sabre\Uri\split($existingPath);
		}
		if ($existingPath === '' || $this->checkPrivileges($existingPath, '{DAV:}read', self::R_PARENT, false)) {
			return;
		}

		$hiddenPath = $this->getTopmostUnreadablePath($path);
		$hiddenNode = $this->server->tree->getNodeForPath($hiddenPath);
		if ($hiddenNode instanceof IACL && $hiddenNode->getOwner() === $principal) {
			return;
		}

		throw new NotFound($this->getHiddenNotFoundMessage($path, $hiddenPath, $hiddenNode));
	}

	private function hidesUnreadablePaths(string $path): bool {
		return str_starts_with($path, 'addressbooks/') || str_starts_with($path, 'calendars/');
	}

	/**
	 * Returns the topmost collection on $path the current user cannot read,
	 * or $path itself if all of them are readable.
	 */
	private function getTopmostUnreadablePath(string $path): string {
		$hiddenPath = '';
		foreach (explode('/', $path) as $segment) {
			$hiddenPath = ltrim($hiddenPath . '/' . $segment, '/');
			if (!$this->checkPrivileges($hiddenPath, '{DAV:}read', self::R_PARENT, false)) {
				break;
			}
		}
		return $hiddenPath;
	}

	/**
	 * Returns the not-found message for $path as if $hiddenPath did not exist.
	 * Homes exist for every user, so for them the child on the path is reported
	 * missing instead.
	 */
	private function getHiddenNotFoundMessage(string $path, string $hiddenPath, INode $hiddenNode): string {
		if ($hiddenPath !== $path && ($hiddenNode instanceof CalendarHome || $hiddenNode instanceof AddressBookHome)) {
			$child = explode('/', substr($path, strlen($hiddenPath) + 1))[0];
			return $this->getNotFoundMessageForChild($hiddenNode, $child);
		}
		return $this->getNotFoundMessage($hiddenPath);
	}

	/**
	 * @throws NotFound
	 */
	public function beforeReport(string $reportName, mixed $report, string $path): void {
		if (!($report instanceof CalendarMultiGetReport) && !($report instanceof AddressBookMultiGetReport)) {
			return;
		}

		// Hrefs in an existing, readable collection are left to the tree
		$readableParents = [];
		foreach ($report->hrefs as $href) {
			try {
				$hrefPath = $this->server->calculateUri($href);
			} catch (Forbidden $e) {
				// hrefs outside of the base URI are rejected when the report is handled
				continue;
			}
			if (!$this->hidesUnreadablePaths($hrefPath)) {
				continue;
			}

			[$parent] = \Sabre\Uri\split($hrefPath);
			$readableParents[$parent] ??= $this->server->tree->nodeExists($parent)
				&& $this->checkPrivileges($parent, '{DAV:}read', self::R_PARENT, false);
			if (!$readableParents[$parent]) {
				$this->hideUnreadablePath($hrefPath);
			}
		}
	}

	/**
	 * @throws NotFound
	 */
	private function hideUnreadableIfConditions(RequestInterface $request): void {
		if ($request->getHeader('If') === null) {
			return;
		}

		try {
			$conditions = $this->server->getIfConditions($request);
		} catch (Forbidden $e) {
			// URIs outside of the base URI are rejected when the conditions are checked
			return;
		}

		foreach ($conditions as $condition) {
			if ($condition['uri'] !== $request->getPath()) {
				$this->hideUnreadablePath($condition['uri']);
			}
		}
	}

	/**
	 * @throws NotFound
	 */
	private function hideUnreadableDestination(RequestInterface $request): void {
		$destination = $request->getHeader('Destination');
		if ($destination === null) {
			return;
		}

		try {
			$destinationPath = $this->server->calculateUri($destination);
		} catch (DavException $e) {
			// Invalid destinations are rejected when the method is handled
			return;
		}

		$this->hideUnreadablePath($destinationPath);
	}

	/**
	 * Returns the message of the exception the parent collection throws
	 * when the node at $uri does not exist.
	 */
	private function getNotFoundMessage(string $uri): string {
		[$parentUri, $name] = \Sabre\Uri\split($uri);
		$parent = $this->server->tree->getNodeForPath((string)$parentUri);
		return $this->getNotFoundMessageForChild($parent, $name);
	}

	private function getNotFoundMessageForChild(INode $parent, string $name): string {
		if ($parent instanceof Calendar) {
			return 'Calendar object not found';
		}
		if ($parent instanceof AddressBook) {
			return 'Card not found';
		}
		if ($parent instanceof AddressBookHome) {
			return sprintf("Addressbook with name '%s' could not be found", $name);
		}
		return sprintf("Node with name '%s' could not be found", $name);
	}
}
