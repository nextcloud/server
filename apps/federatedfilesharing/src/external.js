/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2014-2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { loadState } from '@nextcloud/initial-state'
import { generateUrl } from '@nextcloud/router'

window.OCA.Sharing = window.OCA.Sharing || {}

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
	const remote = share.remote
	const owner = share.ownerDisplayName || share.owner
	const name = share.name

	// Clean up the remote URL for display
	const remoteClean = remote
		.replace(/^https?:\/\//, '') // remove http:// or https://
		.replace(/\/$/, '') // remove trailing slash

	if (!passwordProtected) {
		window.OC.dialogs.confirm(
			t(
				'files_sharing',
				'Do you want to add the remote share {name} from {owner}@{remote}?',
				{ name, owner, remote: remoteClean },
			),
			t('files_sharing', 'Remote share'),
			function(result) {
				callback(result, share)
			},
			true,
		).then(this._adjustDialog)
	} else {
		window.OC.dialogs.prompt(
			t(
				'files_sharing',
				'Do you want to add the remote share {name} from {owner}@{remote}?',
				{ name, owner, remote: remoteClean },
			),
			t('files_sharing', 'Remote share'),
			function(result, password) {
				share.password = password
				callback(result, share)
			},
			true,
			t('files_sharing', 'Remote share password'),
			true,
		).then(this._adjustDialog)
	}
}

window.OCA.Sharing._adjustDialog = function() {
	const $dialog = $('.oc-dialog:visible')
	const $buttons = $dialog.find('button')
	// hack the buttons
	$dialog.find('.ui-icon').remove()
	$buttons.eq(1).text(t('core', 'Cancel'))
	$buttons.eq(2).text(t('files_sharing', 'Add remote share'))
}

const reloadFilesList = function() {
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
const processIncomingShareFromUrl = function() {
	const params = window.OC.Util.History.parseUrlQuery()

	// manually add server-to-server share
	if (params.remote && params.token && params.name) {

		const callbackAddShare = function(result, share) {
			const password = share.password || ''
			if (result) {
				$.post(
					generateUrl('apps/federatedfilesharing/askForFederatedShare'),
					{
						remote: share.remote,
						token: share.token,
						owner: share.owner,
						ownerDisplayName: share.ownerDisplayName || share.owner,
						name: share.name,
						password,
					},
				).done(function(data) {
					if (data.hasOwnProperty('legacyMount')) {
						reloadFilesList()
					} else {
						window.OC.Notification.showTemporary(data.message)
					}
				}).fail(function(data) {
					window.OC.Notification.showTemporary(JSON.parse(data.responseText).message)
				})
			}
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
const processSharesToConfirm = function() {
	// check for new server-to-server shares which need to be approved
	$.get(generateUrl('/apps/files_sharing/api/externalShares'), {}, function(shares) {
		let index
		for (index = 0; index < shares.length; ++index) {
			window.OCA.Sharing.showAddExternalDialog(
				shares[index],
				false,
				function(result, share) {
					if (result) {
						// Accept
						$.post(generateUrl('/apps/files_sharing/api/externalShares'), { id: share.id })
							.then(function() {
								reloadFilesList()
							})
					} else {
						// Delete
						$.ajax({
							url: generateUrl('/apps/files_sharing/api/externalShares/' + share.id),
							type: 'DELETE',
						})
					}
				},
			)
		}
	})
}

processIncomingShareFromUrl()

if (loadState('federatedfilesharing', 'notificationsEnabled', true) !== true) {
	// No notification app, display the modal
	processSharesToConfirm()
}

$('body').on('window.OCA.Notification.Action', function(e) {
	if (e.notification.app === 'files_sharing' && e.notification.object_type === 'remote_share' && e.action.type === 'POST') {
		// User accepted a remote share reload
		reloadFilesList()
	}
})
