<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\AppFramework\Bootstrap;

/**
 * @since 20.0.0
 */
interface IBootstrap {
	/**
	 * @param IRegistrationContext $context
	 *
	 * @since 20.0.0
	 */
	public function register(IRegistrationContext $context): void;

	/**
	 * Boot the application
	 *
	 * At this stage you can assume that all services are registered and the DI
	 * container(s) are ready to be queried.
	 *
	 * @param IBootContext $context
	 *
	 * @since 20.0.0
	 */
	public function boot(IBootContext $context): void;
}
