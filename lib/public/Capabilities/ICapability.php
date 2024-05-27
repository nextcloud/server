<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCP\Capabilities;

/**
 * Minimal interface that has to be implemented for a class to be considered
 * a capability.
 *
 * In an application use \OCP\AppFramework\Bootstrap\IRegistrationContext::registerCapability
 * to register capabilities.
 *
 * @since 8.2.0
 */
interface ICapability {
	/**
	 * Function an app uses to return the capabilities
	 *
	 * ```php
	 * return [
	 *     'myapp' => [
	 *         'awesomefeature' => true,
	 *         'featureversion' => 3,
	 *     ],
	 *     'morecomplex' => [
	 *         'a' => [1, 2],
	 *     ],
	 * ];
	 * ```
	 *
	 * @return array<string, array<string, mixed>> Indexed array containing the app's capabilities
	 * @since 8.2.0
	 */
	public function getCapabilities();
}
