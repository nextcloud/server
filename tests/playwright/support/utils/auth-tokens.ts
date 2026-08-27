/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { runOcc } from '@nextcloud/e2e-test-server/docker'

/**
 * Create an app password so there is something to revoke.
 *
 * Without --password-from-env the token carries no login password. Fine for tests that
 * only revoke it and never authenticate with it.
 *
 * @param userId - Login to create the token for
 * @param name - Device name shown in the token list
 */
export async function addAppPassword(userId: string, name: string): Promise<void> {
	await runOcc(['user:auth-tokens:add', userId, '--name', name])
}
