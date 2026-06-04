/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { spawnDialog } from '@nextcloud/vue/functions/dialog'
import RemoteShareDialog from '../components/RemoteShareDialog.vue'

/**
 * Open a dialog to ask the user whether to add a remote share.
 *
 * @param name The name of the share
 * @param owner The owner of the share
 * @param remote The remote address
 * @param passwordRequired True if the share is password protected
 */
export async function showRemoteShareDialog(
	name: string,
	owner: string,
	remote: string,
	passwordRequired = false,
): Promise<string | void> {
	const [status, password] = await spawnDialog(RemoteShareDialog, {
		name,
		owner,
		remote,
		passwordRequired,
	})

	if (passwordRequired && status) {
		return password as string
	} else if (status) {
		return
	} else {
		throw new Error('Dialog was cancelled')
	}
}
