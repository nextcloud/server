<?php
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\CalDAV\Principal;

/**
 * Class User
 *
 * @package OCA\DAV\CalDAV\Principal
 */
class User extends \Sabre\CalDAV\Principal\User {

	/**
	 * Returns a list of ACE's for this node.
	 *
	 * Each ACE has the following properties:
	 *   * 'privilege', a string such as {DAV:}read or {DAV:}write. These are
	 *     currently the only supported privileges
	 *   * 'principal', a url to the principal who owns the node
	 *   * 'protected' (optional), indicating that this ACE is not allowed to
	 *      be updated.
	 *
	 * @return array
	 */
	public function getACL() {
		$acl = parent::getACL();
		$acl[] = [
			'privilege' => '{DAV:}read',
			'principal' => '{DAV:}authenticated',
			'protected' => true,
		];
		return $acl;
	}

	/**
	 * Returns a specific child node, referenced by its name.
	 *
	 * @param string $name
	 *
	 * @return \Sabre\DAV\INode
	 */
	public function getChild($name) {
		$principal = $this->principalBackend->getPrincipalByPath($this->getPrincipalURL() . '/' . $name);
		if (!$principal) {
			throw new \Sabre\DAV\Exception\NotFound("Node with name $name was not found");
		}
		if ($name === 'calendar-proxy-read') {
			return new ProxyRead($this->principalBackend, $this->principalProperties);
		}

		if ($name === 'calendar-proxy-write') {
			return new ProxyWrite($this->principalBackend, $this->principalProperties);
		}

		throw new \Sabre\DAV\Exception\NotFound("Node with name $name was not found");
	}

	/**
	 * Returns an array with all the child nodes.
	 *
	 * @return \Sabre\DAV\INode[]
	 */
	public function getChildren() {
		$r = [];
		if ($this->principalBackend->getPrincipalByPath($this->getPrincipalURL() . '/calendar-proxy-read')) {
			$r[] = new ProxyRead($this->principalBackend, $this->principalProperties);
		}
		if ($this->principalBackend->getPrincipalByPath($this->getPrincipalURL() . '/calendar-proxy-write')) {
			$r[] = new ProxyWrite($this->principalBackend, $this->principalProperties);
		}

		return $r;
	}
}
