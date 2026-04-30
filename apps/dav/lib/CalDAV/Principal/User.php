<?php
/**
 * @copyright Copyright (c) 2017, Christoph Seitz <christoph.seitz@posteo.de>
 *
 * @author Christoph Seitz <christoph.seitz@posteo.de>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
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
