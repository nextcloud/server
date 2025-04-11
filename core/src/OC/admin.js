/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

const isAdmin = !!window._oc_isadmin

/**
 * Returns whether the current user is an administrator
 *
 * @return {boolean} true if the user is an admin, false otherwise
 * @since 9.0.0
 */
export const isUserAdmin = () => isAdmin
