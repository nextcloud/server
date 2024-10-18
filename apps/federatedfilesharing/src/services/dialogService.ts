/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { spawnDialog } from '@nextcloud/dialogs'
import RemoteShareDialog from '../components/RemoteShareDialog.vue'

/**
 * Open a dialog to ask the user whether to add a remote share.
 *
 * @param name The name of the share
 * @param owner The owner of the share
 * @param remote The remote address
 * @param passwordRequired True if the share is password protected
 */
export function showRemoteShareDialog(
	name: string,
	owner: string,
	remote: string,
	passwordRequired = false,
): Promise<string|void> {
	const { promise, reject, resolve } = Promise.withResolvers<string|void>()

	spawnDialog(RemoteShareDialog, { name, owner, remote, passwordRequired }, (status, password) => {
		if (passwordRequired && status) {
			resolve(password as string)
		} else if (status) {
			resolve(undefined)
		} else {
			reject()
		}
	})

	return promise
}
