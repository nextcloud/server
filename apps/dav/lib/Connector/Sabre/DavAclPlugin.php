<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\Connector\Sabre;

use OCA\DAV\CalDAV\CachedSubscription;
use OCA\DAV\CalDAV\Calendar;
use OCA\DAV\CardDAV\AddressBook;
use Sabre\CalDAV\Principal\User;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\INode;
use Sabre\DAV\PropFind;
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

	public function checkPrivileges($uri, $privileges, $recursion = self::R_PARENT, $throwExceptions = true) {
		$access = parent::checkPrivileges($uri, $privileges, $recursion, false);
		if ($access === false && $throwExceptions) {
			/** @var INode $node */
			$node = $this->server->tree->getNodeForPath($uri);

			switch (get_class($node)) {
				case AddressBook::class:
					$type = 'Addressbook';
					break;
				case Calendar::class:
				case CachedSubscription::class:
					$type = 'Calendar';
					break;
				default:
					$type = 'Node';
					break;
			}

			if ($this->getCurrentUserPrincipal() === $node->getOwner()) {
				throw new Forbidden('Access denied');
			} else {
				throw new NotFound(
					sprintf(
						"%s with name '%s' could not be found",
						$type,
						$node->getName()
					)
				);
			}
			
		}

		return $access;
	}

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

	public function beforeMethod(RequestInterface $request, ResponseInterface $response) {
		$path = $request->getPath();

		// prevent the plugin from causing an unneeded overhead for file requests
		if (str_starts_with($path, 'files/')) {
			return;
		}

		parent::beforeMethod($request, $response);

		if (!str_starts_with($path, 'addressbooks/') && !str_starts_with($path, 'calendars/')) {
			return;
		}

		[$parentName] = \Sabre\Uri\split($path);
		if ($request->getMethod() === 'REPORT') {
			// is calendars/users/bob or addressbooks/users/bob readable?
			$this->checkPrivileges($parentName, '{DAV:}read');
		} elseif ($request->getMethod() === 'MKCALENDAR' || $request->getMethod() === 'MKCOL') {
			// is calendars/users/bob or addressbooks/users/bob writeable?
			$this->checkPrivileges($parentName, '{DAV:}write');
		}
	}
}
