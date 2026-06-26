<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\LDAP;

use OCP\AppFramework\Attribute\Consumable;
use OCP\IServerContainer;

/**
 * Interface ILDAPProviderFactory
 *
 * This class is responsible for instantiating and returning an ILDAPProvider
 * instance.
 *
 * @since 11.0.0
 */
#[Consumable(since: '11.0.0')]
interface ILDAPProviderFactory {
	/**
	 * Constructor for the LDAP provider factory
	 *
	 * @since 11.0.0
	 */
	public function __construct(IServerContainer $serverContainer);

	/**
	 * creates and returns an instance of the ILDAPProvider
	 *
	 * @since 11.0.0
	 */
	public function getLDAPProvider(): ILDAPProvider;

	/**
	 * Check if an ldap provider is available
	 *
	 * @since 21.0.0
	 */
	public function isAvailable(): bool;
}
