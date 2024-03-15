<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Anna Larch <anna.larch@gmx.net>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCA\DAV\CardDAV;

use OCA\DAV\AppInfo\PluginManager;
use OCP\IGroupManager;
use OCP\IUser;

class AddressBookRoot extends \Sabre\CardDAV\AddressBookRoot {

	/** @var PluginManager */
	private $pluginManager;
	private ?IUser $user;
	private ?IGroupManager $groupManager;

	/**
	 * @param \Sabre\DAVACL\PrincipalBackend\BackendInterface $principalBackend
	 * @param \Sabre\CardDAV\Backend\BackendInterface $carddavBackend
	 * @param string $principalPrefix
	 */
	public function __construct(\Sabre\DAVACL\PrincipalBackend\BackendInterface $principalBackend,
		\Sabre\CardDAV\Backend\BackendInterface $carddavBackend,
		PluginManager $pluginManager,
		?IUser $user,
		?IGroupManager $groupManager,
		string $principalPrefix = 'principals') {
		parent::__construct($principalBackend, $carddavBackend, $principalPrefix);
		$this->pluginManager = $pluginManager;
		$this->user = $user;
		$this->groupManager = $groupManager;
	}

	/**
	 * This method returns a node for a principal.
	 *
	 * The passed array contains principal information, and is guaranteed to
	 * at least contain a uri item. Other properties may or may not be
	 * supplied by the authentication backend.
	 *
	 * @param array $principal
	 *
	 * @return \Sabre\DAV\INode
	 */
	public function getChildForPrincipal(array $principal) {
		return new UserAddressBooks($this->carddavBackend, $principal['uri'], $this->pluginManager, $this->user, $this->groupManager);
	}

	public function getName() {
		if ($this->principalPrefix === 'principals') {
			return parent::getName();
		}
		// Grabbing all the components of the principal path.
		$parts = explode('/', $this->principalPrefix);

		// We are only interested in the second part.
		return $parts[1];
	}
}
