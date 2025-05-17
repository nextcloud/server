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
 * @returns A promise that resolves only when the user accepts.  
 *          The resolved value carries the password if one was provided.
 */
export function showRemoteShareDialog(
	name: string,
	owner: string,
	remote: string,
	passwordRequired = false,
): Promise<{ accepted: true; password?: string }> {
	const { promise, reject, resolve } = Promise.withResolvers<{ accepted: true; password?: string }>()

	spawnDialog(
		RemoteShareDialog,
		{ name, owner, remote, passwordRequired },
		(status: boolean, password?: string) => {
			if (status) {
				resolve({ accepted: true, password })
			} else {
				reject()
			}
		},
	)

	return promise
}
