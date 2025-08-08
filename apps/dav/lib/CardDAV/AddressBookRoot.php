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
use Sabre\CardDAV\Backend;
use Sabre\DAVACL\PrincipalBackend;

class AddressBookRoot extends \Sabre\CardDAV\AddressBookRoot {

	public function __construct(
		PrincipalBackend\BackendInterface $principalBackend,
		Backend\BackendInterface $carddavBackend,
		private PluginManager $pluginManager,
		private ?IUser $user,
		private ?IGroupManager $groupManager,
		string $principalPrefix = 'principals',
	) {
		parent::__construct($principalBackend, $carddavBackend, $principalPrefix);
	}

	/**
	 * Returns the name of the node.
	 */
	public function getName(): string {
		if ($this->principalPrefix === 'principals') {
			return parent::getName();
		}
		$parts = explode('/', $this->principalPrefix);
		return $parts[1];
	}
	
	/**
	 * Returns a node for a principal.
	 */
	public function getChildForPrincipal(array $principal): \Sabre\DAV\INode {
		return new UserAddressBooks(
			$this->carddavBackend,
			$principal['uri'],
			$this->pluginManager,
			$this->user,
			$this->groupManager,
		);
	}
}
