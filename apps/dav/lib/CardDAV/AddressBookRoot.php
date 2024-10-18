<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\CardDAV;

use OCA\DAV\AppInfo\PluginManager;
use OCP\IGroupManager;
use OCP\IUser;

class AddressBookRoot extends \Sabre\CardDAV\AddressBookRoot {

	/**
	 * @param \Sabre\DAVACL\PrincipalBackend\BackendInterface $principalBackend
	 * @param \Sabre\CardDAV\Backend\BackendInterface $carddavBackend
	 * @param string $principalPrefix
	 */
	public function __construct(
		\Sabre\DAVACL\PrincipalBackend\BackendInterface $principalBackend,
		\Sabre\CardDAV\Backend\BackendInterface $carddavBackend,
		private PluginManager $pluginManager,
		private ?IUser $user,
		private ?IGroupManager $groupManager,
		string $principalPrefix = 'principals',
	) {
		parent::__construct($principalBackend, $carddavBackend, $principalPrefix);
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
