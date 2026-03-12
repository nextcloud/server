<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

require_once __DIR__ . '/lib/versioncheck.php';

use OC\Kernel\HttpKernel;
use OCP\AppFramework\Http\IOutput;
use OCP\IRequest;

require_once __DIR__ . '/lib/private/Kernel/Kernel.php';
require_once __DIR__ . '/lib/private/Kernel/HttpKernel.php';

$kernel = (new HttpKernel())
	->boot();

$request = $kernel->getServer()->get(IRequest::class);
$output = $kernel->getServer()->get(IOutput::class);
$response = $kernel->handle($request);
$kernel->deliverResponse($request, $response, $output);
