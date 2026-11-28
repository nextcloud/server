/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/**
 * Run `action`, retrying it with an exponential backoff while it throws.
 * Meant for fixture setup (logins, account creation) that can hit transient
 * server errors on a busy test server.
 *
 * @param description - What the action does, used to log the retries
 * @param action - The action to run
 * @param attempts - How often to try in total
 */
export async function retryWithBackoff<T>(description: string, action: () => Promise<T>, attempts = 3): Promise<T> {
	for (let attempt = 1; ; attempt++) {
		try {
			return await action()
		} catch (error) {
			if (attempt >= attempts) {
				throw error
			}
			const delay = 800 * 2 ** (attempt - 1)
			console.info(`${description} failed (attempt ${attempt} of ${attempts}), retrying in ${delay} ms`, error)
			await new Promise((resolve) => setTimeout(resolve, delay))
		}
	}
}
