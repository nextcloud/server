<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Kernel;

use OCP\AppFramework\Http\Response;
use OCP\IRequest;

interface IHttpKernel {
	/**
	 * Handle the request
	 */
	public function handle(IRequest $request, bool $catch = true): Response;
}
