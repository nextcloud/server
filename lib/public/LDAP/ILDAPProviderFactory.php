<?php

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\LDAP;

use OCP\IServerContainer;

/**
 * Interface ILDAPProviderFactory
 *
 * This class is responsible for instantiating and returning an ILDAPProvider
 * instance.
 *
 * @since 11.0.0
 */
interface ILDAPProviderFactory {
	/**
	 * Constructor for the LDAP provider factory
	 *
	 * @param IServerContainer $serverContainer server container
	 * @since 11.0.0
	 */
	public function __construct(IServerContainer $serverContainer);

	/**
	 * creates and returns an instance of the ILDAPProvider
	 *
	 * @return ILDAPProvider
	 * @since 11.0.0
	 */
	public function getLDAPProvider();

	/**
	 * Check if an ldap provider is available
	 *
	 * @return bool
	 * @since 21.0.0
	 */
	public function isAvailable(): bool;
}
