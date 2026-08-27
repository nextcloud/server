/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/**
 * Carries the revoked token ids. The password form and the token list are mounted as
 * separate Vue apps, so the event bus is the only link between them.
 */
export const AUTH_TOKENS_REVOKED_EVENT = 'settings:auth-tokens:revoked'
