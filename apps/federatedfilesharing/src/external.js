/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2014-2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import axios, { isAxiosError } from '@nextcloud/axios'
import { showError, showInfo } from '@nextcloud/dialogs'
import { subscribe } from '@nextcloud/event-bus'
import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'
import { showRemoteShareDialog } from './services/dialogService.ts'
import logger from './services/logger.ts'

window.OCA.Sharing = window.OCA.Sharing ?? {}

/**
 * Shows "add external share" dialog.
 *
 * @param {object} share the share
 * @param {string} share.remote remote server URL
 * @param {string} share.owner owner name
 * @param {string} share.name name of the shared folder
 * @param {string} share.token authentication token
 * @param {boolean} passwordProtected true if the share is password protected
 * @param {Function} callback the callback
 */
window.OCA.Sharing.showAddExternalDialog = function(share, passwordProtected, callback) {
	const owner = share.ownerDisplayName || share.owner
	const name = share.name

	// Clean up the remote URL for display
	const remote = share.remote
		.replace(/^https?:\/\//, '') // remove http:// or https://
		.replace(/\/$/, '') // remove trailing slash

	showRemoteShareDialog(name, owner, remote, passwordProtected)
		.then((result, password) => callback(result, { ...share, password }))
		// eslint-disable-next-line n/no-callback-literal
		.catch(() => callback(false, share))
}

window.addEventListener('DOMContentLoaded', () => {
	processIncomingShareFromUrl()

	if (loadState('federatedfilesharing', 'notificationsEnabled', true) !== true) {
		// No notification app, display the modal
		processSharesToConfirm()
	}

	subscribe('notifications:action:executed', ({ action, notification }) => {
		if (notification.app === 'files_sharing' && notification.object_type === 'remote_share' && action.type === 'POST') {
			// User accepted a remote share reload
			reloadFilesList()
		}
	})
})

/**
 * Reload the files list to show accepted shares
 */
function reloadFilesList() {
	if (!window?.OCP?.Files?.Router?.goToRoute) {
		// No router, just reload the page
		window.location.reload()
		return
	}

	// Let's redirect to the root as any accepted share would be there
	window.OCP.Files.Router.goToRoute(
		null,
		{ ...window.OCP.Files.Router.params, fileid: undefined },
		{ ...window.OCP.Files.Router.query, dir: '/', openfile: undefined },
	)
}

/**
 * Process incoming remote share that might have been passed
 * through the URL
 */
function processIncomingShareFromUrl() {
	const params = window.OC.Util.History.parseUrlQuery()

	// manually add server-to-server share
	if (params.remote && params.token && params.name) {

		const callbackAddShare = (result, share) => {
			if (result === false) {
				return
			}

			axios.post(
				generateUrl('apps/federatedfilesharing/askForFederatedShare'),
				{
					remote: share.remote,
					token: share.token,
					owner: share.owner,
					ownerDisplayName: share.ownerDisplayName || share.owner,
					name: share.name,
					password: share.password || '',
				},
			).then(({ data }) => {
				if (Object.hasOwn(data, 'legacyMount')) {
					reloadFilesList()
				} else {
					showInfo(data.message)
				}
			}).catch((error) => {
				logger.error('Error while processing incoming share', { error })

				if (isAxiosError(error) && error.response.data.message) {
					showError(error.response.data.message)
				} else {
					showError(t('federatedfilesharing', 'Incoming share could not be processed'))
				}
			})
		}

		// clear hash, it is unlikely that it contain any extra parameters
		location.hash = ''
		params.passwordProtected = parseInt(params.protected, 10) === 1
		window.OCA.Sharing.showAddExternalDialog(
			params,
			params.passwordProtected,
			callbackAddShare,
		)
	}
}

/**
 * Retrieve a list of remote shares that need to be approved
 */
async function processSharesToConfirm() {
	// check for new server-to-server shares which need to be approved
	const { data: shares } = await axios.get(generateUrl('/apps/files_sharing/api/externalShares'))
	for (let index = 0; index < shares.length; ++index) {
		window.OCA.Sharing.showAddExternalDialog(
			shares[index],
			false,
			function(result, share) {
				if (result) {
					// Accept
					axios.post(generateUrl('/apps/files_sharing/api/externalShares'), { id: share.id })
						.then(() => reloadFilesList())
				} else {
					// Delete
					axios.delete(generateUrl('/apps/files_sharing/api/externalShares/' + share.id))
				}
			},
		)
	}
}
