/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

export const redirect = targetURL => { window.location = targetURL }

/**
 * Reloads the current page
 *
 * @deprecated 17.0.0 use window.location.reload directly
 */
export const reload = () => { window.location.reload() }
