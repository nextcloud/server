<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Profiler;

use OC\AppFramework\Http\Request;
use OCP\AppFramework\Http\Response;
use OCP\DataCollector\AbstractDataCollector;

class RoutingDataCollector extends AbstractDataCollector {
	private string $appName;
	private string $controllerName;
	private string $actionName;

	public function __construct(string $appName, string $controllerName, string $actionName) {
		$this->appName = $appName;
		$this->controllerName = $controllerName;
		$this->actionName = $actionName;
	}

	public function collect(Request $request, Response $response, ?\Throwable $exception = null): void {
		$this->data = [
			'appName' => $this->appName,
			'controllerName' => $this->controllerName,
			'actionName' => $this->actionName,
		];
	}

	public function getName(): string {
		return 'router';
	}
}
