/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { translate as t } from '@nextcloud/l10n'

export const unlimitedQuota = {
	id: 'none',
	label: t('settings', 'Unlimited'),
}

export const defaultQuota = {
	id: 'default',
	label: t('settings', 'Default quota'),
}

/**
 * Return `true` if the logged in user does not have permissions to view the
 * data of `user`
 *
 * @param user The user to check
 * @param user.id Id of the user
 */
export function isObfuscated(user: { id: string, [key: string]: unknown }) {
	const keys = Object.keys(user)
	return keys.length === 1 && keys.at(0) === 'id'
}
