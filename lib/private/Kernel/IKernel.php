<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Kernel;

use OCP\AppFramework\Http\Response;
use OCP\IRequest;
use Psr\Container\ContainerInterface;

interface IKernel {
	public function boot(): void;

	public function getServerRoot(): string;

	public function isCli(): bool;

	public function getContainer(): ContainerInterface;
}
