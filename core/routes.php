<?php

declare(strict_types=1);

use OC\Route\Router;

/**
 * SPDX-FileCopyrightText: 2016-2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2012-2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
/** @var Router $this */
$this->create('heartbeat', '/heartbeat')->get();
