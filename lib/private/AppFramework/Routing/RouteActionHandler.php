<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\AppFramework\Routing;

use OC\AppFramework\App;
use OC\AppFramework\DependencyInjection\DIContainer;

class RouteActionHandler {
	/**
	 * @param string $controllerName
	 * @param string $actionName
	 */
	public function __construct(
		private DIContainer $container,
		private $controllerName,
		private $actionName,
	) {
	}

	public function __invoke($params) {
		App::main($this->controllerName, $this->actionName, $this->container, $params);
	}
}
