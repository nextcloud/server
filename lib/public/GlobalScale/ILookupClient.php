<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\GlobalScale;

/**
 * Interface needed by a client to communicate with a remote lookup server to
 * fulfill features provided by globalscale.
 *
 * @since 36.0.0
 */
interface ILookupClient {
	public function info(): array;
	public function search(): array;
	public function registerUser(): bool;
	public function unregisterUser(): bool;
	public function updateUsers(): array;
	public function dropUsers(): void;
}
